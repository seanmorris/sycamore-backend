<?php
namespace SeanMorris\Sycamore\ActivityPub\Collection;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;
use \SeanMorris\Sycamore\ActivityPub\Type\Note;
use \SeanMorris\Sycamore\ActivityPub\Activity\Create;

class Ordered extends Controller
{
	protected $collectionRoot = '';
	protected $canonical = '';
	protected $actorName = 'sean';

	public function index($router)
	{
		$get = $router->request()->get();

		header('Content-Type: application/json');

		$redis = Settings::get('redis');

		$collectionName = $this->getCollectionName();

		$total = $redis->zcard($collectionName);

		$page = FALSE;
		$pageLength = 10;

		$objects = [];

		if($get['page'] ?? false)
		{
			$page = (int) $get['page'];

			$list = $redis->zrange(
				$collectionName
				, $pageLength * $page + 0
				, $pageLength * $page + 1
			);

			$first = -0 + $pageLength * ($page - 1);
			$last  = $first + $pageLength;

			$cursor = 6;

			$idList = $redis->zRangeByScore(
				$collectionName
				, -INF
				, INF
				, ['LIMIT' => [$first, $last]]
			);

			$objects = $this->listItems($idList);
		}

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = 'https://';
		// $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		// 	? 'https://'
		// 	: 'http://';

		return json_encode([
			'@context'     => 'https://www.w3.org/ns/activitystreams'
			, 'id'         => $scheme . $domain . $this->canonical
			, 'type'       => $page ? 'OrderedCollectionPage' : 'OrderedCollection'
			, 'totalItems' => $total
			, 'partOf'     => $scheme . $domain . $this->canonical
			, 'first'      => $scheme . $domain . $this->canonical . '?page=1'
			, 'last'       => $scheme . $domain . $this->canonical . '?page=' . (ceil($total / $pageLength))
		] + ($page > 0 ? [
			'prev' => $scheme . $domain . $this->canonical . '?page=' . ($page - 1)
			, 'orderedItems' => $objects
		] : []));
	}

	public function listItems($idList)
	{
		$objects = [];

		foreach(Note::load(...$idList) as $object)
		{
			$objects[] = $object->unconsume();
		}

		return $objects;
	}

	public function getCollectionName()
	{
		return $this->collectionRoot . $this->actorName;
	}

	public function _dynamic($router)
	{
		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = 'https://';
		// $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		// 	? 'https://'
		// 	: 'http://';

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

			$id = $scheme . $domain . $this->canonical . '/' . $objectId;

			if($sub === 'activity')
			{
				$loader = Create::load($id . '/activity');
			}
			else
			{
				$loader = Note::load($id);
			}


			foreach($loader as $note)
			{
				return json_encode($note->unconsume());
			}

		}

		return FALSE;
	}
}
