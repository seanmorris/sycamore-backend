<?php
namespace SeanMorris\Sycamore\Listener;

use \SeanMorris\Ids\Settings;
use \SeanMorris\Sycamore\Matrix;

class PaymentProcessed extends Payment
{
	public static function channel()
	{
		return 'processed';
	}

	public static function receive($message, $channel, $originalChannel)
	{
		$post = $message->request->post();
		$matrixUsername = $post['matrixUsername'] ?? NULL;
		$amountPaid     = $post['amount'] ?? NULL;

		$matrixSettings = \Settings::read('matrix');

		if(!$matrixSettings->server || !$matrixSettings->paidChannel)
		{
			return;
		}

		$matrix = new Matrix($matrixSettings->server);

		$matrix->invite($matrixSettings, $matrixUsername);
	}
}
