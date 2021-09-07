<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

use \SeanMorris\Sycamore\Payment;
use \SeanMorris\Sycamore\Discovery;
use \SeanMorris\Sycamore\Access;

use \SeanMorris\Sycamore\ActivityPub\Type\Actor;
use \SeanMorris\Sycamore\ActivityPub\PublicInbox;
use \SeanMorris\Sycamore\ActivityPub\Root as ActivityPubRoot;

class Root extends Controller
{
	public $routes = [
		'/^\.well-known$/' => Discovery::CLASS
		, '/^access$/'    => Access::CLASS
		, '/^pay$/'       => Payment::CLASS
		, '/^ap$/'        => ActivityPubRoot::CLASS
	];

	public function index($router)
	{
		header('Content-Type: text/plain');

		return \SeanMorris\Ids\Settings::read('default', 'domain')  . ' - It works!';
	}

	public function remote($router)
	{
		header('Content-Type: application/json');

		$subNode  = $router->path()->consumeNode() ?: 'index';
		$id = $router->request()->get('external');

		$url = $id . '?' . $_SERVER['QUERY_STRING'];

		$redis = Settings::get('redis');

		if($cached = json_decode($redis->get($url)))
		{
			return $cached;
		}

		$context = stream_context_create($contextSource = ['http' => [
			'ignore_errors' => TRUE
			, 'header' => [
				'Accept: application/ld+json'
			]
		]]);

		$response = file_get_contents($url, FALSE, $context);

		$response = json_decode($response);

		$redis->set($url, $response);
		$redis->expire($url, 60);

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = 'https://';

		$local = $scheme . $domain . '/remote?external=';

		$remoteKeys = ['id', 'next', 'prev', 'first', 'last', 'partOf', 'inbox', 'outbox', 'following', 'followers', 'featured', 'featuredTags', 'devices', 'inReplyTo'];

		$findIds = function($object) use(&$findIds, $local, $remoteKeys){
			foreach($object as $k => &$v)
			{
				if(is_object($v) || is_array($v))
				{
					$findIds($v);
					continue;
				}
				if(is_string($k) && in_array($k, $remoteKeys))
				{
					if(!$v) continue;

					$object->{'__remote_' . $k} = $v;
					$v = $local . urlencode($v);
				}
			}
		};

		$findIds($response);

		return str_replace('https://', $local, json_encode($response));
	}

	public function sean($router)
	{
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: /ap/actor/sean");
	}

	public function inbox($router)
	{
		$publicInbox = new PublicInbox;

		return $publicInbox->index($router);
	}

	public function caption()
	{
		header('HTTP/1.1 200 OK');
		header('Transfer-Encoding: chunked');
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
		header('Connection: keep-alive');

		$id = 0;

		$pointer = fopen('/app/tmp/subtitles.stream', 'r');

		$line = '';

		while(!feof($pointer))
		{
			$line = fgets($pointer);

			Log::debug('Event ' . $id);

			yield(new \SeanMorris\Ids\Http\Event($line, $id++));

			$line = '';
		}
	}

	// public function superchat($router)
	// {
	// 	header('Content-Type: text/plain');

	// 	$matrix = new Matrix('https://matrix.org');

	// 	$matrix->login();

	// 	return $matrix->send(
	// 		'!FIoireJEFPfTCUfUrL:matrix.org'
	// 		, 'sycamore-backend test'
	// 	);
	// }
}
