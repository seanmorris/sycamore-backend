<?php
namespace SeanMorris\Sycamore\ActivityPub\Activity;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;

class Accept extends Activity
{
	const CONTEXT = 'https://www.w3.org/ns/activitystreams';
	const TYPE = 'Accept';

	protected $object;
	protected $actor;
	protected $id;

	public static function consume($values)
	{
		$values = (object) $values;

		Log::debug($values);

		$instance = new static($values->object ?? NULL);

		$instance->object = $values->object ?? NULL;
		$instance->actor  = $values->actor  ?? NULL;
		$instance->id     = $values->id     ?? NULL;

		return $instance;
	}

	public function store($collectionId)
	{
		// $this->object->store($collectionId);

		$redis = Settings::get('redis');

		$actorName = 'sean';

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = 'https://';

		if(!$this->id)
		{
			$this->id = $scheme . $domain . '/ephemeral/' . uniqid() . '/activity';
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
			, 'action' => 'accept'
		]);
	}
}
