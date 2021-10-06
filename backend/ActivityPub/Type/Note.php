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
		$instance->mediaType    = $values->mediaType ?? NULL;
		$instance->published    = $values->published ?? NULL;
		$instance->sycamore     = $values->sycamore ?? NULL;
		$instance->content      = $values->content ?? NULL;
		$instance->summary      = $values->summary ?? NULL;
		$instance->actor        = $values->actor ?? NULL;
		$instance->to           = $values->to ?? NULL;
		$instance->id           = $values->id ?? NULL;

		return $instance;
	}
}
