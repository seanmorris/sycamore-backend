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
			'environment' => 'sandbox'
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
				'processed', (object)['request' => $router->request, 'result' => $result]
			);

			return TRUE;
		}

		\SeanMorris\Sycamore\Listener\PaymentFailed::publish(
			'failed', (object)['request' => $router->request, 'result' => $result]
		);

		return FALSE;
	}
}
