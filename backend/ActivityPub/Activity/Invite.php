<?php
namespace SeanMorris\Sycamore\ActivityPub\Activity;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SeanMorris\Sycamore\ActivityPub\Type\Note;
use \SeanMorris\Sycamore\ActivityPub\Type\BaseObject;

class Invite extends Activity
{
	const CONTEXT = 'https://www.w3.org/ns/activitystreams';
	const TYPE = 'Invite';

	protected $object;
	protected $actor;
	protected $id;

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
		}

		$redis->hset(
			'activity-pub::activities::' . $actorName
			, $this->id
			, json_encode($this->unconsume())
		);

		$redis->zadd($collectionId, time(), $this->id);

		$redis = Settings::get('redis');

		$redis->xAdd('notify::sean', '*', [
			'invitation' => json_encode($this->unconsume())
			, 'action' => 'invite'
		]);
	}
}
