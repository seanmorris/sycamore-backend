<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class ActorList extends Controller
{
	public function index($router)
	{
		$redis = Settings::get('redis');

		if($actorsSource = $redis->hgetall('activity-pub::local-actors'))
		{
			if($actors = array_map('json_decode', $actorsSource))
			{
				return json_encode($actors);
			}
		}

		return '{}';
	}

	public function _dynamic($router)
	{
		if(!$actorName = $router->path()->getNode())
		{
			return FALSE;
		}

		if($router->path()->getNode(1))
		{
			return $router->resumeRouting(new ActorRoute);
		}

		$redis = Settings::get('redis');

		if(preg_match('/\W/', $actorName))
		{
			return FALSE;
		}

		if($actorSource = $redis->hget('activity-pub::local-actors', $actorName))
		{
			if($actor = json_decode($actorSource))
			{
				return $actorSource;
			}
		}

		$actorFile = IDS_ROOT . '/data/global/actors/' . $actorName . '.json';

		if(file_exists($actorFile))
		{
			$actorSource = file_get_contents($actorFile);

			if($actor = json_decode($actorSource))
			{
				$redis->hset('activity-pub::local-actors', $actorName, $actorSource);
				return $actorSource;
			}
		}

		return FALSE;
	}
}