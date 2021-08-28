<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Routable;

class Discovery implements Routable
{
	public function webfinger()
	{
		header('Access-Control-Allow-Origin: *');
		header('Content-Type: application/jrd+json; charset=utf-8');

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = 'https://';

		return json_encode([
			'subject' => 'acct:@sean@polite-rat-62.loca.lt',
			'links'   => [[
				'rel'  => 'self',
				'type' => 'application/activity+json',
				'href' => $scheme . $domain . '/ap/actor/sean',
			]]
		]);
	}
}
