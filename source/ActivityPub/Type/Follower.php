<?php
namespace SeanMorris\Sycamore\ActivityPub\Type;

use \SeanMorris\Ids\Log;

class Follower extends BaseObject
{
	const TYPE = 'Follower';

	public static function consume($values)
	{
		$instance = new static;

		if(is_object($values) || is_array($values))
		{
			$values = (object) $values;

			if(isset($values->scalar))
			{
				$values = $values->scalar;
			}
		}

		if(is_object($values) || is_array($values))
		{
			$values = (object) $values;

			if(empty($values->actor))
			{
				return FALSE;
			}

			if(is_object($values->actor) || is_array($values->actor))
			{
				$values->actor = (object) $values->actor;

				$instance->content = $values->actor->id;
			}
			else if(is_string($values->actor))
			{
				$instance->content = $values->actor;
			}
		}
		else if(is_string($values))
		{
			$instance->content = $values;
		}

		return $instance;
	}

	public function unconsume()
	{
		return $this->content;
	}
}
