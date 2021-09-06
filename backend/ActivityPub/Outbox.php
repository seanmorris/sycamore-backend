<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SeanMorris\Ids\Http\Http404;
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
				throw new Http404;
			}

			$currentUser = $_SESSION['current-user'];

			$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
			$scheme = 'https://';

			$activitySource = $router->request()->fslurp();
			$frozenActivity = json_decode($activitySource);
			$activityType   = Activity::getType($frozenActivity->type);

			if(!$frozenActivity || !$frozenActivity->object)
			{
				throw new Http404;
			}

			$frozenActivity->object->attributedTo
				= $frozenActivity->actor
				= $scheme . $domain . '/ap/actor/' . $currentUser->username;

			$activity = $activityType::consume($frozenActivity);

			Log::debug($activity);

			$activity->store($this->collectionRoot . $currentUser->username);

			if($activity->object && $activity->object->inReplyTo)
			{
				$activity->store('activity-pub::replies::' . $activity->object->inReplyTo);
			}

			$followers = $redis->zrange('activity-pub::followers::' . $currentUser->username, 0, -1);

			foreach($followers as $followerId)
			{
				if(!$follower = Actor::getExternalActor($followerId))
				{
					continue;
				}

				Log::debug($followerId, $follower);

				if(!$follower->endpoints || !$follower->endpoints->sharedInbox)
				{
					continue;
				}

				// Log::debug($activity->send(
				// 	parse_url($follower->inbox, PHP_URL_HOST)
				// 	, parse_url($follower->inbox, PHP_URL_PATH)
				// ));

				Log::debug($activity->send(
					parse_url($follower->endpoints->sharedInbox, PHP_URL_HOST)
					, parse_url($follower->endpoints->sharedInbox, PHP_URL_PATH)
				));
			}

			Log::debug($activity);
		}

		return parent::index($router);
	}
}
