<?php
namespace SeanMorris\Sycamore\ActivityPub\Activity;

class Follow extends Activity
{
	const CONTEXT = 'https://www.w3.org/ns/activitystreams';
	const TYPE = 'Follow';

	protected $object;
	protected $actor;
	protected $id;
}
