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

		$instance = new static($values->object ?? NULL);

		$instance->object = $values->object ?? NULL;
		$instance->actor  = $values->actor  ?? NULL;
		$instance->id     = $values->id     ?? NULL;

		return $instance;
	}

	public function store($collectionId)
	{
	}
}
