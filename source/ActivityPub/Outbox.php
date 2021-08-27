<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class Outbox extends Controller
{
	public function index($router)
	{
		$get = $router->request()->get();

		header('Content-Type: application/json');

		$redis = Settings::get('redis');

		$actorName = 'sean';

		$total = $redis->llen('activity-pub::outbox::' . $actorName);

		$page = FALSE;
		$pageLength = 10;

		if($get['page'] ?? false)
		{
			$page = (int) $get['page'];

			$list = $redis->lrange(
				'activity-pub::outbox::' . $actorName
				, $pageLength * $page + 0
				, $pageLength * $page + 1
			);

			$first = $pageLength * ($page - 1);
			$last  = -1 + ($pageLength * ($page - 0));

			$activitySources = $redis->lrange('activity-pub::outbox::' . $actorName, $first, $last);

			$activities = array_map('json_decode', $activitySources);
		}

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';

		return json_encode([
			'@context'     => 'https://www.w3.org/ns/activitystreams'
			, 'id'         => $scheme . $domain . '/ap/actor/sean/outbox'
			, 'type'       => 'OrderedCollection'
			, 'totalItems' => $total
			, 'partOf'     => $scheme . $domain . '/ap/actor/sean/outbox'
			, 'first'      => $scheme . $domain . '/ap/actor/sean/outbox?page=1'
			, 'last'       => $scheme . $domain . '/ap/actor/sean/outbox?page=' . (1 + floor($total / $pageLength))
		] + ($page > 0 ? [
			'prev' => $scheme . $domain . '/ap/actor/sean/outbox?page=' . ($page - 1)
			, 'orderedItems' => $activities
		] : []));
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


			if($sub === 'activity')
			{
				return $redis->lindex(
					'activity-pub::outbox::' . $actor->preferredUsername
					, -1 + $objectId
				);
			}

			return $redis->lindex(
				'activity-pub::objects::' . $actor->preferredUsername
				, -1 + $objectId
			);
		}

		return FALSE;
	}

	public function create($router, $submitPost = true)
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

		$note = new \SeanMorris\Sycamore\ActivityPub\Type\Note([
			'content' => 'Hello, world!'
			, 'actor' => $actor
		]);

		$activityCreate = new \SeanMorris\Sycamore\ActivityPub\Activity\Create($note);

		$activityCreate->store();

		return json_encode($activityCreate->unconsume());
	}
}
