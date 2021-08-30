<?php
namespace SeanMorris\Sycamore\ActivityPub\Type;

class Follower extends BaseObject
{
	const TYPE = 'Follower';

	public static function consume($values)
	{
		$instance = new static;

		if(is_object($values) || is_array($values))
		{
			$values = (object) $values;

			$instance->actor = $values->actor;
		}
		else if(is_string($values))
		{
			$instance->actor = $values;
		}

		return $instance;
	}

	public function unconsume()
	{
		return (object) $this->actor;
	}
}
