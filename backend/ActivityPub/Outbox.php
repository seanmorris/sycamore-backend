<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;
use \SeanMorris\Sycamore\ActivityPub\Type\Actor;
use \SeanMorris\Sycamore\ActivityPub\Activity\Activity;
use \SeanMorris\Sycamore\ActivityPub\Collection\Ordered;

class Outbox extends Ordered
{
	protected $collectionRoot = 'activity-pub::outbox::';
	protected $canonical = '/ap/actor/sean/outbox';
	protected $actorName = 'sean';

	public function index($router)
	{
		if(!$redis = Settings::get('redis'))
		{
			return FALSE;
		}

		if($router->request()->method() === 'POST')
		{
			session_start();

			$currentUser = FALSE;

			if(empty($_SESSION['current-user']))
			{
				return FALSE;
			}

			$currentUser = $_SESSION['current-user'];

			$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
			$scheme = 'https://';

			$activitySource = $router->request()->fslurp();
			$frozenActivity = json_decode($activitySource);
			$activityType   = Activity::getType($frozenActivity->type);

			if(!$frozenActivity || !$frozenActivity->object)
			{
				return FALSE;
			}

			$frozenActivity->object->attributedTo
				= $frozenActivity->actor
				= $scheme . $domain . '/ap/actor/' . $currentUser->username;

			$activity = $activityType::consume($frozenActivity);

			$activity->store($this->collectionRoot . $currentUser->username);

			$followers = $redis->zrange('activity-pub::followers::' . $currentUser->username, 0, -1);

			foreach($followers as $followerId)
			{
				if(!$follower = Actor::getExternalActor($followerId))
				{
					continue;
				}

				if(!$follower->endpoints || !$follower->endpoints->sharedInbox)
				{
					continue;
				}

				Log::debug($activity->send(
					parse_url($follower->endpoints->sharedInbox, PHP_URL_HOST)
					, parse_url($follower->endpoints->sharedInbox, PHP_URL_PATH)
				));
			}

			Log::debug($activity);
		}

		return parent::index($router);
	}

	public function create($router, $submitPost = true)
	{
		if(!$redis = Settings::get('redis'))
		{
			return FALSE;
		}

		$actorName = 'sean';

		if(!$actorSource = $redis->hget('activity-pub::local-actors', $actorName))
		{
			return FALSE;
		}

		if(!$actor = json_decode($actorSource))
		{
			return FALSE;
		}

		$note = new \SeanMorris\Sycamore\ActivityPub\Type\Note([
			'content' => 'Hello, world!'
			, 'actor' => $actor
		]);

		$activityCreate = new \SeanMorris\Sycamore\ActivityPub\Activity\Create($note);

		$activityCreate->store($this->getCollectionName());

		return json_encode($activityCreate->unconsume());
	}
}
