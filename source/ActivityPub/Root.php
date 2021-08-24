<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class Root extends Controller
{
	public $routes = [
		'/outbox/'  => Outbox::CLASS
		, '/inbox/' => Inbox::CLASS
		, '/actor/' => ActorList::CLASS
	];

	public function index($router)
	{
		header('Content-Type: application/ld+json');

		return json_encode([
			'actors'  => '/actor'
			, 'actor' => '/actor/{NAME}'
		]);
	}
}
