<?php
namespace SeanMorris\Sycamore\ActivityPub;

class PublicInbox extends Inbox
{
	protected $collectionRoot = 'activity-pub::public-inbox::objects';
	protected $canonical = '/ap/inbox';
	protected $actorName = 'sean';

	public function getCollectionName()
	{
		return $this->collectionRoot;
	}
}
