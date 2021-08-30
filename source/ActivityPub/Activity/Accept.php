<?php
namespace SeanMorris\Sycamore\ActivityPub\Activity;

use \SeanMorris\Ids\Log;

class Accept extends Activity
{
	const CONTEXT = 'https://www.w3.org/ns/activitystreams';
	const TYPE = 'Accept';

	protected $object;
	protected $actor;
	protected $id;

	public static function consume($values)
	{
		Log::debug($values);

		$instance = new static($object);

		$instance->id     = $values->id    ?? NULL;
		$instance->actor  = $values->actor ?? NULL;
		$instance->object = $object        ?? NULL;

		return $instance;
	}

	public function store($collectionId)
	{
	}
}
