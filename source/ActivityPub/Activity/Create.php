<?php
namespace SeanMorris\Sycamore\ActivityPub\Activity;

use \SeanMorris\Ids\Settings;

class Create
{
	const CONTEXT = 'https://www.w3.org/ns/activitystreams';
	const TYPE = 'Create';

	protected $object;
	protected $actor;
	protected $id;

	public function __construct($object)
	{
		$this->object = $object;
	}

	public function store()
	{
		$this->object->store();

		$redis = Settings::get('redis');

		$actorName = 'sean';

		$redis = Settings::get('redis');

		$this->id = $this->object->id . '/activity';

		$redis->rpush(
			'activity-pub::outbox::' . $actorName
			, json_encode($this->unconsume())
		);

	}

	public function unconsume()
	{
		$objectData = $this->object;

		if($objectData instanceof \SeanMorris\Sycamore\ActivityPub\Type\Note)
		{
			$objectData = $this->object->unconsume();
		}

		return (object) [
			'@context' => 'https://www.w3.org/ns/activitystreams'
			, 'object' => $objectData
			, 'actor'  => $objectData->attributedTo ?? NULL
			, 'type'   => $this::TYPE
			, 'id'     => $objectData->id ? ($objectData->id . '/activity') : NULL
		];
	}
}
