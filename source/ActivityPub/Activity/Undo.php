<?php
namespace SeanMorris\Sycamore\ActivityPub\Activity;

class Delete extends Activity
{
	const CONTEXT = 'https://www.w3.org/ns/activitystreams';
	const TYPE = 'Undo';

	protected $object;
	protected $actor;
	protected $id;

	public static function consume($values)
	{}

	public function store($collectionId)
	{}
}
