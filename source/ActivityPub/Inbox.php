<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class Inbox extends Controller
{
	public function index($router)
	{
		if($router->request()->method() === 'POST')
		{
			if(!$activitySource = file_get_contents('php://input'))
			{
				return FALSE;
			}

			if(!$activity = json_decode($activitySource))
			{
				return FALSE;
			}

			if(!$activity->object || $activity->object->attributedTo)

			$this->getExternalActor($activity->object->attributedTo);

			$sigPairs = explode(',', $router->request()->headers('Signature'));

			$signature = [];

			array_map(
				function($pair) use(&$signature) {parse_str($pair, $sig); $signature += $sig;}
				, $sigPairs
			);

			$signature = array_map(
				function($v) { return trim($v, '"') ;}
				, $signature
			);

			var_dump($signature, $activity->object->attributedTo);

			$hash = 'SHA-256=' . base64_encode(hash('SHA256', $activitySource, TRUE));
		}
	}

	protected function getExternalActor($url)
	{
		$context     = stream_context_create($contextSource = ['http' => ['ignore_errors' => TRUE]]);
		$actorSource = file_get_contents($url, FALSE, $context);
		$headers     = print_r($http_response_header, 1) . PHP_EOL;

		if($actor = json_decode($actorSource))
		{
			return $actor;
		}

		return FALSE;
	}
}
