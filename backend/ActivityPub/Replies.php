<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Sycamore\ActivityPub\Type\Reply;
use \SeanMorris\Sycamore\ActivityPub\Activity\Create;
use \SeanMorris\Sycamore\ActivityPub\Collection\Ordered;

class Replies extends Ordered
{
	protected $collectionRoot = 'activity-pub::replies::%s';
	protected $canonical = '';
	protected $actorName = 'sean';

	public function __construct($rootObject)
	{
		$this->collectionRoot = sprintf($this->collectionRoot, $rootObject->id);
		$this->canonical = parse_url($rootObject->id, PHP_URL_PATH) . '/replies';
	}

	public function listItems($idList)
	{
		$objects = [];


		foreach(Create::load(...$idList) as $activity)
		{
			if(!$activity || !$activity->object) continue;

			$objects[] = $activity->object->id;
		}

		return array_unique($objects);
	}

	public function getCollectionName()
	{
		return $this->collectionRoot;
	}
}
