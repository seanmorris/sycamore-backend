<?php
namespace SeanMorris\Sycamore\ActivityPub\Type;

use \SeanMorris\Ids\Settings;

class BaseObject
{
	const TYPE = 'Note';

	protected $id, $actor, $content, $published, $attributedTo, $inReplyTo;

	public function __construct($properties = [])
	{
		$properties = (object) $properties;

		$this->actor   = $properties->actor   ?? NULL;
		$this->content = $properties->content ?? '';
	}

	public static function consume($values)
	{
		$values = (object) $values;

		$instance = new static;

		$instance->attributedTo = $values->attributedTo ?? NULL;
		$instance->inReplyTo    = $values->inReplyTo ?? NULL;
		$instance->published    = $values->published ?? NULL;
		$instance->content      = $values->content ?? NULL;
		$instance->actor        = $values->actor ?? NULL;
		$instance->id           = $values->id ?? NULL;

		return $instance;
	}

	public function store($collectionId)
	{
		$redis = Settings::get('redis');

		$actorName = 'sean';

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';

		if(!$this->id)
		{
			$this->id = $scheme . $domain . '/ap/actor/sean/outbox/' . uniqid();
			$this->published = gmdate('D, d M Y H:i:s T');
		}

		$redis->hset(
			'activity-pub::objects::' . $actorName
			, $this->id
			, json_encode($this->unconsume())
		);

		$redis->zadd($collectionId, strtotime($this->published), $this->id);
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
			, 'content'      => $this->content
			, 'to'           => 'https://www.w3.org/ns/activitystreams#Public'
		];
	}

	public function __get($name)
	{
		return $this->{$name};
	}
}
