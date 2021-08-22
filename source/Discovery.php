<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Routable;

class Discovery implements Routable
{
	public function webfinger()
	{
		header('Content-Type: application/jrd+json; charset=utf-8');

		return json_encode([
			'subject' => 'acct:sean@sycamore-backend.herokuapp.com',
			'links'   => [[
				'rel'  => 'self',
				'type' => 'application/activity+json',
				'href' => 'https://sycamore-backend.herokuapp.com/sean',
			]]
		]);
	}
}
