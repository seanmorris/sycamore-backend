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
		$post    = $message->request->post();
		$eventId = $post['eventId'] ?? NULL;
		$amount  = $post['amount']  ?? NULL;

		$matrixSettings = Settings::read('matrix');

		if(!$matrixSettings->server)
		{
			return;
		}

		$matrix = new Matrix($matrixSettings->server);

		$roomId = '!FIoireJEFPfTCUfUrL:matrix.org';

		$message = [
			'm.relates_to'   => [
				'rel_type'   => 'm.annotation'
				, 'event_id' => $eventId
				, 'paid'     => $amount
				, 'key'      => '🍁'
			]
		];

		$matrix->login();

		$matrix->send(
			$roomId
			, json_encode($message)
			, 'm.reaction'
		);
	}
}
