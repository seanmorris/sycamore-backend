<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Log;
use \SeanMorris\Sycamore\ActivityPub\Type\Follower;
use \SeanMorris\Sycamore\ActivityPub\Collection\Ordered;

class Followers extends Ordered
{
	protected $collectionRoot = 'activity-pub::followers::';
	protected $canonical = '/ap/actor/sean/followers';
	protected $actorName = 'sean';

	public function listItems($idList)
	{
		$objects = [];

		Log::debug($idList);

		foreach(Follower::load(...$idList) as $object)
		{
			Log::debug($object);

			if(!$object) continue;

			$objects[] = $object->unconsume();
		}

		Log::debug($objects);

		return $objects;
	}
}
