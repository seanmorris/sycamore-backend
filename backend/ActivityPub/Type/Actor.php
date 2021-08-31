<?php
namespace SeanMorris\Sycamore\ActivityPub\Type;
class Actor
{
	const CONTEXT = [
		"https://www.w3.org/ns/activitystreams"
		, "https://w3id.org/security/v1"
	];

	const TYPE = 'Person';

	public function __get($name)
	{
		return $this->{$name};
	}
}
