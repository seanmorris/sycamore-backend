<?php
namespace SeanMorris\Sycamore\ActivityPub\Type;

use \SeanMorris\Ids\Settings;

class Note
{
	const TYPE = 'Note';

	protected $id, $actor, $content;

	public function __construct($properties)
	{
		$this->actor   = $properties['actor'];
		$this->content = $properties['content'] ?? '';
	}

	public function store()
	{
		$redis = Settings::get('redis');

		$actorName = 'sean';

		$outboxLen = $redis->rpush(
			'activity-pub::outbox::' . $actorName
			, json_encode($this->unconsume())
		);

		$this->id = 'https://sycamore-backend.herokuapp.com/ap/actor/sean/outbox/' . ( -1 + $outboxLen);
	}

	public function unconsume()
	{
		$now = gmdate('D, d M Y H:i:s T');

		return (object) [
			'id'             => $this->id
			, 'type'         => $this::TYPE
			, 'published'    => $now
			, 'attributedTo' => $this->actor->id
			, 'content'      => $this->content
			, 'to'           => 'https://www.w3.org/ns/activitystreams#Public'
			// , 'inReplyTo'    => 'https://mastodon.social/@seanmorris/106798459503650980'
		];
	}

	public function __get($name)
	{
		return $this->{$name};
	}
}
