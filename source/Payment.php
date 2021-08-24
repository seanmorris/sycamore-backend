<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Routable;
use \SeanMorris\Ids\Settings;

class Payment implements Routable
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

		// var_dump(Settings::get('redis'));

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
			, 'amount'           => '10.00'
		]);

		if($result->success)
		{
			return TRUE;
		}

		return FALSE;
	}
}
