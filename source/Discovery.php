<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Routable;

class Discovery implements Routable
{
	// public $routes = ['/.well-known/' => 'webfinger'];

	public function index()
	{
		return 'it works!';
	}

	public function webfinger()
	{
		return json_encode([
			'subject' => 'acct:sean@sycamore-backend.herokuapp.com',
			'links'   => [[
				'type' => 'application/activity+json',
				'href' => 'https://sycamore-backend.herokuapp.com/actor',
				'rel'  => 'self',
			]]
		]);
	}
}
