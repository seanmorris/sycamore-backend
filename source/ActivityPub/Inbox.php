<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\Sycamore\ActivityPub\Activity\Create;
use \SeanMorris\Sycamore\ActivityPub\Activity\Activity;
use \SeanMorris\Sycamore\ActivityPub\Collection\Ordered;

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

			$frozenActivity = json_decode($activitySource);

			\SeanMorris\Ids\Log::debug($activitySource, $frozenActivity);

			$activityType = Activity::getType($frozenActivity->type);

			if(!$activity = $activityType::consume($frozenActivity))
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
			$type = $router->request()->headers('Content-Type');

			$requestTarget = sprintf(
				'(request-target): post %s' . PHP_EOL
					. 'host: %s' . PHP_EOL
					. 'date: %s' . PHP_EOL
					. 'digest: %s' . PHP_EOL
					. 'content-type: %s'
				, $this->canonical
				, $host
				, $date
				, $hash
				, $type
			);

			$publicKey = $actor->publicKey->publicKeyPem;

			$sig = base64_decode(str_replace(' ', '+', $signature['signature']));

			\SeanMorris\Ids\Log::debug([
				'requestTarget' => $requestTarget
				, 'sig' => base64_encode($sig)
				, 'publicKey' => $publicKey
			]);

			$userVerified = openssl_verify($requestTarget, $sig, $publicKey, 'sha256WithRSAEncryption');

			\SeanMorris\Ids\Log::debug('userVerified', $userVerified);

			if($userVerified)
			{
				switch($activity->type)
				{
					case 'Create':
						$activity->store($this->getCollectionName());
						break;

					case 'Follow':
						$activity->store('activity-pub::followers::sean');
						break;

					case 'Accept':
						$activity->store('activity-pub::following::sean');
						$activity->store($this->getCollectionName());
						break;

					case 'Reject':
						$activity->store($this->getCollectionName());
						break;

				}
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

	public function supportedActivities()
	{
		$activities = Activity::types();

		$result = [];

		foreach($activities as $activity)
		{
			$reflect = new \ReflectionClass($activity);

			$result[$reflect->getShortName()] = $activity;
		}

		unset($result['Activity']);

		return json_encode($result);
	}
}
