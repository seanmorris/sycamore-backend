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
	}

	public function store($collectionId)
	{
	}
}
