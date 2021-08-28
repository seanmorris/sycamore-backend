<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;
use \SeanMorris\Sycamore\ActivityPub\Collection\Ordered;
use \SeanMorris\Sycamore\ActivityPub\Activity\Create;

class Inbox extends Ordered
{
	protected $collectionRoot = 'activity-pub::inbox::objects::';
	protected $canonical = '/ap/actor/sean/inbox';
	protected $actorName = 'sean';

	public function index($router)
	{
		$get = $router->request()->get();

		header('Content-Type: text/html');

		if($router->request()->method() === 'POST')
		{
			\SeanMorris\Ids\Log::debug($router->request()->headers());

			if(!$activitySource = file_get_contents('php://input'))
			{
				return FALSE;
			}

			\SeanMorris\Ids\Log::debug($activitySource);

			if(!$activity = Create::consume(json_decode($activitySource)))
			{
				return FALSE;
			}

			if(!$activity->object || !$activity->object->attributedTo)
			{
				return FALSE;
			}

			$rawSignature = $router->request()->headers('Signature');

			$sigPairs = explode(',', $rawSignature);

			$signature = [];

			$build = function($pair) use(&$signature) {parse_str($pair, $sig); $signature += $sig;};

			array_map($build, $sigPairs);

			$signature = array_map(
				function($v) { return trim($v, '"') ;}
				, $signature
			);

			\SeanMorris\Ids\Log::debug($activity);

			$actor = $this->getExternalActor($activity->actor);

			\SeanMorris\Ids\Log::debug('Actor: ', $actor);

			if(!$actor || !$actor->publicKey || !$actor->publicKey->publicKeyPem)
			{
				return FALSE;
			}

			$host = $router->request()->headers('Host');
			$hash = $router->request()->headers('Digest');
			$date = $router->request()->headers('Date');

			$requestTarget = sprintf('(request-target): post %s
host: %s
digest: %s
date: %s
', $this->canonical, $host, $hash, $now);
			$publicKey = $actor->publicKey->publicKeyPem;

			$sig = base64_decode(str_replace(' ', '+', $signature['signature']));

			\SeanMorris\Ids\Log::debug([
				'requestTarget' => $requestTarget
				, 'sig' => $sig
				, 'publicKey' => $publicKey
			]);

			$userVerified = openssl_verify($requestTarget, $sig, $publicKey, 'sha256WithRSAEncryption');

			\SeanMorris\Ids\Log::debug('userVerified', $userVerified);

			if($userVerified)
			{
				$activity->store($this->getCollectionName());
				return TRUE;
			}

			return FALSE;
		}

		return parent::index($router);
	}

	protected function getExternalActor($url)
	{
		$context     = stream_context_create($contextSource = ['http' => [
			'ignore_errors' => TRUE
			, 'header' => [
				'Accept: application/ld+json'
			]
		]]);
		$actorSource = file_get_contents($url, FALSE, $context);
		$headers     = print_r($http_response_header, 1) . PHP_EOL;

		\SeanMorris\Ids\Log::debug($actorSource);

		if($actor = json_decode($actorSource))
		{
			return $actor;
		}

		return FALSE;
	}
}
