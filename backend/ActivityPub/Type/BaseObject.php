<?php
namespace SeanMorris\Sycamore\ActivityPub\Type;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;

class BaseObject
{
	const TYPE = 'Baseobject';

	protected $id, $actor, $content, $published, $attributedTo, $inReplyTo;

	public function __construct($properties = [])
	{
		$properties = (object) $properties;

		$this->actor   = $properties->actor   ?? NULL;
		$this->content = $properties->content ?? '';
	}

	public static function consume($values)
	{
		$instance = new static;

		return $instance;
	}

	public function store($collectionId)
	{
		$redis = Settings::get('redis');

		$actorName = 'sean';

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		// $scheme = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
		$scheme = 'https://';

		if(!$this->id)
		{
			$this->id = $scheme . $domain . '/ap/actor/sean/outbox/' . uniqid();
			$this->published = date(DATE_ATOM);
		}

		$redis->hset(
			'activity-pub::objects::' . $actorName
			, $this->id
			, json_encode($this->unconsume())
		);
	}

	public static function load(...$idList)
	{
		$redis = Settings::get('redis');
		$actorName = 'sean';

		foreach($idList as $id)
		{
			$source = $redis->hget('activity-pub::objects::' . $actorName, $id);
			$frozen = json_decode($source, $id);
			$object = static::consume($frozen);

			Log::debug($source, $frozen, $object);

			yield $object;
		}
	}

	public function unconsume()
	{
		return (object) [
			'id'             => $this->id
			, 'type'         => $this::TYPE
			, 'published'    => $this->published
			, 'inReplyTo'    => $this->inReplyTo
			, 'attributedTo' => $this->attributedTo
			, 'replies'      => $this->id . '/replies'
			, 'content'      => $this->content
			, 'to'           => 'https://www.w3.org/ns/activitystreams#Public'
		];
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

		unset($result['BaseObject']);

		return $result[$type] ?? NULL;
	}

	public function __get($name)
	{
		return $this->{$name};
	}
}
