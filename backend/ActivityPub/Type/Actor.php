<?php
namespace SeanMorris\Sycamore\ActivityPub\Type;
class Actor
{
	const CONTEXT = [
		"https://www.w3.org/ns/activitystreams"
		, "https://w3id.org/security/v1"
	];

	const TYPE = 'Person';

	public function __get($name)
	{
		return $this->{$name};
	}

	public static function getWebFinger($globalId)
	{
		[$userId, $server] = explode('@', $globalId);

		$urlFormat = 'https://%s/.well-known/webfinger?resource=acct:%s';

		$url = sprintf($urlFormat, $server, $globalId);

		$actorSource = file_get_contents($url);

		$headers = print_r($http_response_header, 1) . PHP_EOL;

		if($actor = json_decode($actorSource))
		{
			return $actor;
		}

		return FALSE;
	}

	public static function findProfileLink($fingerResult)
	{
		if(!isset($fingerResult->links))
		{
			return FALSE;
		}

		foreach($fingerResult->links as $link)
		{
			if($link->rel === 'self')
			{
				return $link->href;
			}
		}

		return FALSE;
	}

	public static function getExternalActor($url)
	{
		$context = stream_context_create($contextSource = ['http' => [
			'ignore_errors' => TRUE
			, 'header' => [
				'Accept: application/ld+json'
			]
		]]);

		$actorSource = file_get_contents($url, FALSE, $context);
		$headers = print_r($http_response_header, 1) . PHP_EOL;

		if($actor = json_decode($actorSource))
		{
			return $actor;
		}

		return FALSE;
	}
}
