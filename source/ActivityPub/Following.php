<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Sycamore\ActivityPub\Collection\Ordered;

class Following extends Ordered
{
	protected $collectionRoot = 'activity-pub::following::';
	protected $canonical = '/ap/actor/sean/following';
	protected $actorName = 'sean';
}
