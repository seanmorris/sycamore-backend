<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SeanMorris\Sycamore\ActivityPub\Type\Actor;
use \SeanMorris\Sycamore\ActivityPub\Activity\Create;
use \SeanMorris\Sycamore\ActivityPub\Activity\Accept;
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
			Log::debug($router->request()->headers());

			if(!$activitySource = file_get_contents('php://input'))
			{
				throw new \SeanMorris\Ids\Http\Http406(
					'No data supplied.'
				);
			}

			$frozenActivity = json_decode($activitySource);

			Log::debug($activitySource, $frozenActivity);

			$activityType = Activity::getType($frozenActivity->type);

			$activity = $activityType::consume($frozenActivity);

			Log::debug($activityType, $activity);

			if(!$activity)
			{
				throw new \SeanMorris\Ids\Http\Http406(
					'Invalid or insufficient data supplied.'
				);
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

			$signedHeaders = explode(' ', $signature['headers']);

			array_shift($signedHeaders);

			Log::debug($signedHeaders);

			Log::debug($activity);

			$actor = NULL;

			if($activity->actor && $actor = Actor::getExternalActor($activity->actor))
			{
				if(!$actor->publicKey || !$actor->publicKey->publicKeyPem)
				{
					throw new \SeanMorris\Ids\Http\Http406(
						'Cannot locate public key.'
					);
				}
			}

			$host = $router->request()->headers('Host');
			$hash = $router->request()->headers('Digest');
			$date = $router->request()->headers('Date');
			$type = $router->request()->headers('Content-Type');

			$time = strtotime($date);

			if(abs($time - time()) > 30)
			{
				throw new \SeanMorris\Ids\Http\Http406(
					'Timestamp is out of range.'
				);
			}

			$requestTarget = '(request-target): post ' . $this->canonical;

			foreach($signedHeaders as $signedHeader)
			{
				$requestTarget .= PHP_EOL . $signedHeader . ': ' . $router->request()->headers(ucwords($signedHeader, '-'));
			}

			if($actor)
			{
				$publicKey = $actor->publicKey->publicKeyPem;

				$sig = base64_decode(str_replace(' ', '+', $signature['signature']));

				Log::debug([
					'requestTarget' => $requestTarget
					, 'sig' => base64_encode($sig)
					, 'publicKey' => $publicKey
				]);

				$userVerified = openssl_verify($requestTarget, $sig, $publicKey, 'sha256WithRSAEncryption');

				Log::debug('userVerified', $userVerified);

				if($userVerified)
				{
					Log::debug($activity);

					switch($activity::TYPE)
					{
						case 'Create':
							$activity->store($this->getCollectionName());

							if($activity->object && $activity->object->inReplyTo)
							{
								$activity->store('activity-pub::replies::' . $activity->object->inReplyTo);
							}

							break;

						case 'Follow':
							$activity->store('activity-pub::followers::sean');

							if($actor->endpoints && $actor->endpoints->sharedInbox)
							{
								$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
								$scheme = 'https://';
								$accept = Accept::consume([
									'object'  => $frozenActivity
									, 'actor' => $scheme . $domain . '/ap/actor/sean'
									, 'id'    => $scheme . $domain . '/ephemeral-activity/' . uniqid()
								]);

								$accept->store('activity-pub::outbox::sean');

								Log::debug($accept);

								Log::debug($accept->send(
									parse_url($actor->endpoints->sharedInbox, PHP_URL_HOST)
									, parse_url($actor->endpoints->sharedInbox, PHP_URL_PATH)
								));
							}

							break;

						case 'Accept':
							$activity->store('activity-pub::following::sean');
							$activity->store($this->getCollectionName());
							break;

						case 'Reject':
							$activity->store($this->getCollectionName());
							break;

						case 'Invite':
							$activity->store($this->getCollectionName());
							break;
					}

					return TRUE;
				}

				throw new \SeanMorris\Ids\Http\Http401(
					'User verification failed.'
				);
			}

			return TRUE;
		}

		return parent::index($router);
	}

	protected function createActivity(){}
	protected function followActivity(){}
	protected function acceptActivity(){}
	protected function rejectActivity(){}
	protected function deleteActivity(){}

	public function supportedActivities()
	{
		$activities = Activity::types();

		return json_encode($activities);
	}
}
