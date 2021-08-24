<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class ActorRoute extends Controller
{
	public $routes = [
		'/outbox/'  => Outbox::CLASS
		, '/inbox/' => Inbox::CLASS
	];
}
