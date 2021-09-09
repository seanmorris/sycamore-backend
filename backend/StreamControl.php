<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

use \SeanMorris\Ids\Http\Http403;
use \SeanMorris\Ids\Http\Http302;

class StreamControl extends Controller
{
	public function index($router)
	{
		header('Content-Type: text/plain');

		$redis = Settings::get('redis');

		return implode($redis->keys('*'), PHP_EOL);
	}

	public function publish($router)
	{
		$streamKey = $router->request()->post('name');

		$redis = Settings::get('redis');

		if(!$streamer = $redis->get('streamers::' . $streamKey))
		{
			throw new Http403('Forbidden');
		}

		$redis->hset('live', $streamer, time());

		$redis->xAdd('announce', '*', [
			'action'   => 'live-started'
			, 'stream' => $streamer
		]);

		throw new Http302('rtmp://127.0.0.1:1935/live/sean');
	}

	public function publishDone($router)
	{
		$streamKey = $router->request()->post('name');

		$redis = Settings::get('redis');

		if(!$streamer = $redis->get('streamers::' . $streamKey))
		{
			throw new Http403('Forbidden');
		}

		$redis->hdel('live', $streamer, time());

		$redis->xAdd('announce', '*', [
			'action'   => 'live-completed'
			, 'stream' => $streamer
		]);
	}
}
