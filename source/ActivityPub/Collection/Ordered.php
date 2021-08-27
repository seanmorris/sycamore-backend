<?php
namespace SeanMorris\Sycamore\ActivityPub\Collection;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class Ordered extends Controller
{
	protected $collectionRoot = 'activity-pub::outbox::';
	protected $canonical = '/ap/actor/sean/outbox';
	protected $actorName = 'sean';

	public function index($router)
	{
		$get = $router->request()->get();

		header('Content-Type: application/json');

		$redis = Settings::get('redis');

		$collectionName = $this->getCollectionName();

		$total = $redis->llen($collectionName);

		$page = FALSE;
		$pageLength = 10;

		$objects = [];

		if($get['page'] ?? false)
		{
			$page = (int) $get['page'];

			$list = $redis->lrange(
				$collectionName
				, $pageLength * $page + 0
				, $pageLength * $page + 1
			);

			$first = $pageLength * ($page - 1);
			$last  = -1 + ($pageLength * ($page - 0));

			$activitySources = $redis->lrange($collectionName, $first, $last);

			$activities = array_map('json_decode', $activitySources);

			$getObject = function($a) { return $a->object; };

			$objects = array_map($getObject, $activities);
		}

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';

		return json_encode([
			'@context'     => 'https://www.w3.org/ns/activitystreams'
			, 'id'         => $scheme . $domain . $this->canonical
			, 'type'       => 'OrderedCollection'
			, 'totalItems' => $total
			, 'partOf'     => $scheme . $domain . $this->canonical
			, 'first'      => $scheme . $domain . $this->canonical . '?page=1'
			, 'last'       => $scheme . $domain . $this->canonical . '?page=' . (1 + floor($total / $pageLength))
		] + ($page > 0 ? [
			'prev' => $scheme . $domain . $this->canonical . '?page=' . ($page - 1)
			, 'orderedItems' => $objects
		] : []));
	}

	public function getCollectionName()
	{
		return $this->collectionRoot . $this->actorName;
	}

	public function _dynamic($router)
	{
		$redis = Settings::get('redis');

		$actorName = 'sean';

		if(!$actorSource = $redis->hget('activity-pub::local-actors', $actorName))
		{
			return FALSE;
		}

		if(!$actor = json_decode($actorSource))
		{
			return FALSE;
		}

		if($objectId = $router->path()->getNode())
		{
			$sub = $router->path()->consumeNode();

			$activitySource = $redis->lindex(
				$this->collectionRoot . $actor->preferredUsername
				, -1 + $objectId
			);

			if($sub === 'activity')
			{
				return $activitySource;
			}

			$activity = json_decode($activitySource);

			return json_encode($activity->object);
		}

		return FALSE;
	}
}
