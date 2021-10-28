<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Settings;

class Matrix
{
	protected $server;

	public function __construct($server)
	{
		$this->server = $server;
	}

	public function login()
	{
		$redis = Settings::get('redis');

		if(!$matrixSession = $redis->get('matrix::session'))
		{
			$username = Settings::read('matrix', 'bot', 'username');
			$password = Settings::read('matrix', 'bot', 'password');

			$document = json_encode([
				'password' => $password
				, 'user'   => $username
				, 'type'   => 'm.login.password'
			]);

			if(!$username || !$password)
			{
				return FALSE;
			}

			$context = stream_context_create($contextSource = ['http' => [
				'ignore_errors' => TRUE
				, 'header'      => ['Content-Type: application/json']
				, 'content'     => $document
				, 'method'      => 'POST'
			]]);

			$url  = $this->server . '/_matrix/client/r0/login';

			$matrixSession = file_get_contents($url, FALSE, $context);

			$redis->set('matrix::session', $matrixSession);
		}

		return $matrixSession;
	}

	public function invite($roomId, $userId)
	{
		$redis = Settings::get('redis');

		if(!$matrixSession = $redis->get('matrix::session'))
		{
			return FALSE;
		}

		$matrixSession = json_decode($matrixSession);

		$document = json_encode([
			'msgtype' => 'sycamore-test'
			, 'body'  => $message
		] + $extraProperties);

		$context = stream_context_create($contextSource = ['http' => [
			'ignore_errors' => TRUE
			, 'header'      => ['Content-Type: application/json']
			, 'content'     => $document
			, 'method'      => 'POST'
		]]);

		$roomPath = '/_matrix/client/r0/rooms/' . $roomId;

		$invitePath = $roomPath . '/invite?access_token=' . $matrixSession->access_token;

		$url = $this->server . $invitePath;

		$inviteResult = file_get_contents($url, FALSE, $context);

		return $inviteResult;
	}

	public function send($roomId, $message, $type = 'm.room.message')
	{
		$redis = Settings::get('redis');

		if(!$matrixSession = $redis->get('matrix::session'))
		{
			return FALSE;
		}

		$matrixSession = json_decode($matrixSession);

		// $document = json_encode([
		// 	'msgtype' => 'sycamore-test'
		// 	, 'body'  =>
		// ] + $extraProperties);

		$context = stream_context_create($contextSource = ['http' => [
			'ignore_errors' => TRUE
			, 'header'      => ['Content-Type: application/json']
			, 'content'     => $message
			, 'method'      => 'POST'
		]]);

		$roomPath = '/_matrix/client/r0/rooms/' . $roomId;

		$sendPath = $roomPath . '/send/' . $type . '?access_token=' . $matrixSession->access_token;

		$url = $this->server . $sendPath;

		$sendResult = file_get_contents($url, FALSE, $context);

		return $sendResult;
	}
}
