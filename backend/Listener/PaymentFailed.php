<?php
namespace SeanMorris\Sycamore\Listener;
class PaymentFailed extends Payment
{
	public static function channel()
	{
		return 'failed';
	}

	public static function receive($message, $channel, $originalChannel)
	{
		$post = $message->request->post();
		$matrixUsername = $post['matrixUsername'] ?? NULL;
		$amountPaid     = $post['amount'] ?? NULL;

		var_dump($message, $matrixUsername, $amountPaid);

		die;
	}
}
