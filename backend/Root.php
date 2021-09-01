<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

use \SeanMorris\Sycamore\Payment;
use \SeanMorris\Sycamore\Discovery;
use \SeanMorris\Sycamore\ActivityPub\PublicInbox;
use \SeanMorris\Sycamore\ActivityPub\Root as ActivityPubRoot;

class Root extends Controller
{
	public $routes = [
		'/.well-known/' => Discovery::CLASS
		, '/pay/'       => Payment::CLASS
		, '/ap/'        => ActivityPubRoot::CLASS
	];

	public function index($router)
	{
		header('Content-Type: text/plain');

		return \SeanMorris\Ids\Settings::read('default', 'domain')  . ' - It works!';
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
		// header('HTTP/1.1 200 OK');
		// header('Transfer-Encoding: chunked');
		// header('Content-Type: text/event-stream');
		// header('Cache-Control: no-cache');
		// header('Connection: keep-alive');

		$id = 0;

		$spec = [
			0 => ['pipe', 'r']
			, 1 => ['pipe', 'w']
			, 2 => ['pipe', 'w']
		];

		$pointer = proc_open('tail -n 0 -f /app/tmp/subtitles.stream', $spec, $pipes);

		while($line = fgets($pipes[1]))
		{
			// Log::debug('Event ' . $id);
			// yield(new \SeanMorris\Ids\Http\Event(, $id++));

			return $line;
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
