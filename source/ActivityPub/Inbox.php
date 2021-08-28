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
			$hash = $router->request()->headers('Digest');
			$now = $router->request()->headers('Date');

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

			\SeanMorris\Ids\Log::debug($actor);

			if(!$actor || !$actor->publicKey || !$actor->publicKey->publicKeyPem)
			{
				return FALSE;
			}

			$host = 'sycamore-backend.herokuapp.com';

			$requestTarget = sprintf('(request-target): post /inbox
host: %s
date: %s
digest: %s', $host, $now, $hash);

			$publicKey = $actor->publicKey->publicKeyPem;

			$sig = base64_decode(str_replace(' ', '+', $signature['signature']));

			\SeanMorris\Ids\Log::debug($requestTarget, $sig, $publicKey, 'sha256WithRSAEncryption');

			$userVerified = openssl_verify($requestTarget, $sig, $publicKey, 'sha256WithRSAEncryption');

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
		$context     = stream_context_create($contextSource = ['http' => ['ignore_errors' => TRUE]]);
		$actorSource = file_get_contents($url, FALSE, $context);
		$headers     = print_r($http_response_header, 1) . PHP_EOL;

		if($actor = json_decode($actorSource))
		{
			return $actor;
		}

		return FALSE;
	}
}
