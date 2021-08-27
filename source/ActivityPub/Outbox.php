<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;
use SeanMorris\Sycamore\ActivityPub\Collection\Ordered;

class Outbox extends Ordered
{
	public function create($router, $submitPost = true)
	{
		$redis = Settings::get('redis');

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

		$activityCreate->store();

		return json_encode($activityCreate->unconsume());
	}
}
