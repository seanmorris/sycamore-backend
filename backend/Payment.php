<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\PressKit\Controller;
use \SeanMorris\Ids\Settings;

class Payment extends Controller
{
	public function token()
	{
		$merchantId = Settings::read('braintree', 'merchant', 'id');
		$privateKey = Settings::read('braintree', 'private', 'key');
		$publicKey  = Settings::read('braintree', 'public', 'key');

		$gateway = new \Braintree\Gateway([
			'environment'   => 'sandbox'
			, 'merchantId'  => $merchantId
			, 'privateKey'  => $privateKey
			, 'publicKey'   => $publicKey
		]);

		return $clientToken = $gateway->clientToken()->generate();
	}

	public function process($router)
	{
		$post = $router->request()->post();

		$merchantId = Settings::read('braintree', 'merchant', 'id');
		$privateKey = Settings::read('braintree', 'private', 'key');
		$publicKey  = Settings::read('braintree', 'public', 'key');

		$gateway = new \Braintree\Gateway([
			'environment'   => 'sandbox'
			, 'merchantId'  => $merchantId
			, 'privateKey'  => $privateKey
			, 'publicKey'   => $publicKey
		]);

		$result = $gateway->transaction()->sale([
			'paymentMethodNonce' => $post['nonce']
			, 'deviceData'       => $post['device']
			, 'options'          => ['submitForSettlement' => TRUE]
			, 'amount'           => $post['amount']
		]);

		if($result->success)
		{
			\SeanMorris\Sycamore\Listener\PaymentProcessed::publish(
				'processed', (object)[
					'request'  => $router->request
					, 'result' => $result
					, 'reason' => 'superchat'
				]
			);

			return TRUE;
		}

		\SeanMorris\Sycamore\Listener\PaymentFailed::publish(
			'failed', (object)['request' => $router->request, 'result' => $result]
		);

		return FALSE;
	}

	public function plans($router)
	{
		$merchantId = Settings::read('braintree', 'merchant', 'id');
		$privateKey = Settings::read('braintree', 'private', 'key');
		$publicKey  = Settings::read('braintree', 'public', 'key');

		$gateway = new \Braintree\Gateway([
			'environment'   => 'sandbox'
			, 'merchantId'  => $merchantId
			, 'privateKey'  => $privateKey
			, 'publicKey'   => $publicKey
		]);

		header('Content-type: application/json');

		$plans = array_map(
			[$this, 'mapPlan'], $gateway->plan()->all()
		);

		usort($plans, function($a,$b) {
			return $a->price <=> $b->price;
		});

		return json_encode($plans);
	}

	protected function mapPlan($plan)
	{
		return (object) [
			'id'            => $plan->id
			, 'name'        => $plan->name
			, 'description' => $plan->description
			, 'frequency'   => $plan->billingFrequency
			, 'currency'    => $plan->currencyIsoCode
			, 'price'       => $plan->price
		];
	}

	public function subscribe($router)
	{
		$merchantId = Settings::read('braintree', 'merchant', 'id');
		$privateKey = Settings::read('braintree', 'private', 'key');
		$publicKey  = Settings::read('braintree', 'public', 'key');

		$device = $router->request()->post('device');
		$nonce  = $router->request()->post('nonce');
		$plan   = $router->request()->post('plan');

		$gateway = new \Braintree\Gateway([
			'environment'  => 'sandbox'
			, 'merchantId' => $merchantId
			, 'privateKey' => $privateKey
			, 'publicKey'  => $publicKey
		]);

		$result = $gateway->subscription()->create([
			'paymentMethodToken' => $nonce
			, 'deviceData'       => $device
			, 'planId'           => $plan
		]);

		if($result->success)
		{
			\SeanMorris\Sycamore\Listener\PaymentProcessed::publish(
				'processed', (object) [
					'request'  => $router->request
					, 'result' => $result
					, 'reason' => 'subscribe'
					, 'detail' => []
				]
			);

			return TRUE;
		}

		\SeanMorris\Sycamore\Listener\PaymentFailed::publish(
			'failed', (object) [
				'request' => $router->request
				, 'result' => $result
				, 'reason' => 'subscribe'
				, 'detail' => []
			]
		);

		return FALSE;
	}
}
