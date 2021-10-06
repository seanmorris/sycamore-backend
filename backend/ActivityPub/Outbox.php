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
			if(!$this->currentUser)
			{
				throw new Http404;
			}

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
				= $scheme . $domain . '/ap/actor/' . $this->currentUser->username;

			$activity = $activityType::consume($frozenActivity);

			$activity->store($this->collectionRoot . $this->currentUser->username);

			if($activity->object && $activity->object->inReplyTo)
			{
				$activity->store('activity-pub::replies::' . $activity->object->inReplyTo);
			}

			$followers = $redis->zrange('activity-pub::followers::' . $this->currentUser->username, 0, -1);

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
