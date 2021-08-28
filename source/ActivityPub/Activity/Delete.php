<?php
namespace SeanMorris\Sycamore\ActivityPub\Activity;

class Delete extends Activity
{
	const CONTEXT = 'https://www.w3.org/ns/activitystreams';
	const TYPE = 'Create';

	protected $object;
	protected $actor;
	protected $id;
}
