<?php
namespace SeanMorris\Sycamore\ActivityPub\Type;

class Note extends BaseObject
{
	const TYPE = 'Note';

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
}
