<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\PressKit\Controller;

class Discovery extends Controller
{
	public function webfinger()
	{
		header('Access-Control-Allow-Origin: *');
		header('Content-Type: application/jrd+json; charset=utf-8');

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = 'https://';

		return json_encode([
			'subject' => 'acct:@sean@' . $domain,
			'links'   => [[
				'rel'  => 'self',
				'type' => 'application/ld+json',
				'href' => $scheme . $domain . '/ap/actor/sean',
			]]
		]);
	}

	public function nodeinfo($router)
	{
		header('Access-Control-Allow-Origin: *');
		header('Content-Type: application/jrd+json; charset=utf-8');

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = 'https://';

		$nextNode = $router->path()->consumeNode();

		if(!$nextNode)
		{
			return json_encode([
				'links'   => [[
					'rel'  => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
					'href' => $scheme . $domain . '/.well-known/nodeinfo/2.0',
				]]
			]);
		}

		if($nextNode !== '2.0')
		{
			return FALSE;
		}

		$infoFile = IDS_ROOT . '/data/global/nodeinfo.json';

		if(file_exists($infoFile))
		{
			return file_get_contents($infoFile);
		}
	}
}
