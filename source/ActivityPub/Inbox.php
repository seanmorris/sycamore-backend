<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class Inbox extends Controller
{
	public function index($router)
	{
		header('Content-Type: text/html');

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

			$rawSignature = $router->request()->headers('Signature');
			$hash = $router->request()->headers('Digest');
			$now = $router->request()->headers('Date');

			$sigPairs = explode(',', $rawSignature);

			$signature = [];

			$build = function($pair) use(&$signature) {parse_str($pair, $sig); $signature += $sig;};

			array_map($build, $sigPairs);

			$signature = array_map(
				function($v) { return trim($v, '"') ;}
				, $signature
			);

			$actor = $this->getExternalActor($activity->object->attributedTo);

			if(!$actor || !$actor->publicKey || !$actor->publicKey->publicKeyPem)
			{
				return FALSE;
			}

			$host = '10.0.0.1:2020';

			$requestTarget = sprintf('(request-target): post /inbox
host: %s
date: %s
digest: %s', $host, $now, $hash);

			$publicKey = $actor->publicKey->publicKeyPem;

			$sig = base64_decode(str_replace(' ', '+', $signature['signature']));

			var_dump(openssl_verify($requestTarget, $sig, $publicKey, 'sha256WithRSAEncryption'));

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
