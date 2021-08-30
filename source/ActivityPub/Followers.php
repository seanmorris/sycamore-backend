<?php
namespace SeanMorris\Sycamore\ActivityPub;

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

		foreach(Follower::load(...$idList) as $object)
		{
			$objects[] = $object->unconsume();
		}

		return $objects;
	}
}
