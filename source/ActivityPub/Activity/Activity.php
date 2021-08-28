<?php
namespace SeanMorris\Sycamore\ActivityPub\Activity;

use \SeanMorris\Ids\Settings;
use \SeanMorris\Sycamore\ActivityPub\Type\Note;

class Activity
{
	const CONTEXT = 'https://www.w3.org/ns/activitystreams';
	const TYPE = 'BaseActivity';

	protected $object;
	protected $actor;
	protected $id;

	public function __construct($object)
	{
		$this->object = $object;
	}

	public static function consume($values)
	{
		$values = (object) $values;
		$object = NULL;

		if($values->object ?? NULL)
		{
			$object = Note::consume($values->object);
		}

		$instance = new static($object);

		$instance->id     = $values->id    ?? NULL;
		$instance->actor  = $values->actor ?? NULL;
		$instance->object = $object        ?? NULL;

		return $instance;
	}

	public function store($collectionId)
	{
		$this->object->store($collectionId);

		$redis = Settings::get('redis');

		$actorName = 'sean';

		if(!$this->id)
		{
			$this->id = $this->object->id . '/activity';

			$redis->hset(
				'activity-pub::activities::' . $actorName
				, $this->id
				, json_encode($this->unconsume())
			);
		}
	}

	public static function load(...$idList)
	{
		$redis = Settings::get('redis');
		$actorName = 'sean';

		foreach($idList as $id)
		{
			$source = $redis->hget('activity-pub::activities::' . $actorName, $id);
			$frozen = json_decode($source, $id);
			$object = static::consume($frozen);

			yield $object;
		}
	}

	public function unconsume()
	{
		$objectData = $this->object;

		if($objectData instanceof Note)
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

	public static function types()
	{
		return \SeanMorris\Ids\Linker::classes(static::CLASS);
	}

	public static function getType($type)
	{
		$typesAvailable = \SeanMorris\Ids\Linker::classes(static::CLASS);

		\SeanMorris\Ids\Log::debug('Activity Types:', $typesAvailable);

		$result = [];

		foreach($typesAvailable as $activity)
		{
			$reflect = new \ReflectionClass($activity);

			$result[$reflect->getShortName()] = $activity;
		}

		unset($result['Activity']);

		return $result[$type] ?? NULL;
	}

	public function __get($name)
	{
		return $this->{$name};
	}
}
