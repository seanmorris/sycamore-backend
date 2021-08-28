<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\PressKit\Controller;

class ActorRoute extends Controller
{
	public $routes = [
		'/following/'   => Following::CLASS
		, '/followers/' => Followers::CLASS
		, '/outbox/'    => Outbox::CLASS
		, '/inbox/'     => Inbox::CLASS
	];
}
