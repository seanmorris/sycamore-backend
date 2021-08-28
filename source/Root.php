<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

use \SeanMorris\Sycamore\Discovery;
use \SeanMorris\Sycamore\Payment;
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
