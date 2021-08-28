<?php
namespace SeanMorris\Sycamore\ActivityPub\Activity;

class Reject extends Activity
{
	const CONTEXT = 'https://www.w3.org/ns/activitystreams';
	const TYPE = 'Reject';

	protected $object;
	protected $actor;
	protected $id;
}
