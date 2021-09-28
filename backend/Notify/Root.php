<?php
namespace SeanMorris\Sycamore\Notify;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class Root extends Controller
{
	public function _init($router)
	{
		$request = $router->request();
		$method  = $request->method();

		$corsMethods = ['GET','POST','HEAD','OPTIONS'];

		$corsHeaders = [
			'Content-Type'
			, 'Cookie'
			, 'Authorization'
			, 'X-Requested-With'
			, 'Cache-Control'
			, 'Last-Event-Id'
			, 'Pragma'
			, 'Referer'
			, 'Accept'
			, 'Ids-Input-Headers'
			, 'Ids-Output-Headers'
		];

		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Methods: ' . implode(', ', $corsMethods));
		header('Access-Control-Allow-Headers: ' . implode(', ', $corsHeaders));
	}

	public function events($router)
	{
		$start = time();
		$redis = Settings::get('redis');
		$request = $router->request();

		$streamNames = [
			'announce', //'notify::' . $username
		];

		session_start();

		if(!empty($_SESSION['current-user']))
		{
			$user = $_SESSION['current-user'];

			$streamNames[] = 'notify::' . $user->username;
		}

		session_write_close();

		header('HTTP/1.1 200 OK');
		header('Transfer-Encoding: chunked');
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
		header('Connection: keep-alive');

		$start = microtime(true);

		yield "Retry: 1000\n";

		$lastEventId = $request->headers('Last-Event-Id')
			?? $_GET['last-event-id']
			?? FALSE;

		if(!$lastEventId)
		{
			$lastEventId = '$';

			foreach($streamNames as $streamName)
			{
				if($messages = $redis->xRevRange($streamName, '+', '-', 100))
				{
					$messages = array_reverse($messages);

					foreach($messages as $id => $message)
					{
						yield(new \SeanMorris\Ids\Http\Event($message, $id));
					}
				}
			}

		}

		$heartbeat = \SeanMorris\Ids\Settings::read('subscribeHeartbeat');

		$lastBeat = $start;

		while(!\SeanMorris\Ids\Http\Http::disconnected() && (time() - $start) < 2)
		{
			foreach($streamNames as $streamName)
			{
				$moreMessages = $redis->xRead([$streamName => $lastEventId], 1, 1);

				if($moreMessages[$streamName] ?? false)
				{
					foreach($moreMessages[$streamName] as $id => $message)
					{
						yield new \SeanMorris\Ids\Http\Event($message, $id);

						$lastEventId = $id;
					}
				}
			}

			if($_GET['quick'] ?? FALSE)
			{
				break;
			}

			if($heartbeat && microtime(true) - $lastBeat >= $heartbeat)
			{
				$lastBeat = microtime(true);

				yield "\n";
			}

			$timeout = \SeanMorris\Ids\Settings::read('subscribeTimeout');

			if($timeout && microtime(true) - $start >= $timeout)
			{
				break;
			}
		}
	}
}
