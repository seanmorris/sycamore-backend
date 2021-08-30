<?php
namespace SeanMorris\Sycamore\ActivityPub\Activity;

use \SeanMorris\Ids\Settings;
use \SeanMorris\Sycamore\ActivityPub\Type\BaseObject;

abstract class Activity
{
	const CONTEXT = 'https://www.w3.org/ns/activitystreams';
	const TYPE = 'BaseActivity';

	protected $object;
	protected $actor;
	protected $id;

	public function __construct($object = NULL)
	{
		$this->object = $object;
	}

	abstract public static function consume($values);
	abstract public function store($collectionId);

	public static function load(...$idList)
	{
		$redis = Settings::get('redis');
		$actorName = 'sean';

		foreach($idList as $id)
		{
			$source = $redis->hget('activity-pub::activities::' . $actorName, $id);
			$frozen = json_decode($source, $id);
			$object = static::consume($frozen);

			yield $object;
		}
	}

	public function unconsume()
	{
		$objectData = $this->object;

		if($objectData instanceof BaseObject)
		{
			$objectData = $this->object->unconsume();
		}

		return (object) [
			'@context' => 'https://www.w3.org/ns/activitystreams'
			, 'object' => $objectData
			, 'actor'  => $objectData->attributedTo ?? NULL
			, 'type'   => $this::TYPE
			, 'id'     => $objectData->id ? ($objectData->id . '/activity') : NULL
		];
	}

	public function send($host, $path = 'inbox')
	{
		$now = gmdate('D, d M Y H:i:s T');

		$type = 'activity+json';
		$url  = sprintf('https://%s/%s', $host, $path);

		$this->store('activity-pub::outbox::' . 'sean');

		$document = json_encode($this->unconsume());

		\SeanMorris\Ids\Log::debug($document);

		$hash = 'SHA-256=' . base64_encode(hash('SHA256', $document, TRUE));
		$requestTarget = sprintf('(request-target): post /%s
host: %s
date: %s
digest: %s', $path, $host, $now, $hash);

		if(file_exists($privateKeyFile = 'file://' . IDS_ROOT . '/data/local/ssl/ids_rsa.pem'))
		{
			$privateKey = openssl_pkey_get_private($privateKeyFile);
			$publicKey  = file_get_contents('file://' . IDS_ROOT . '/data/local/ssl/ids_rsa.pub.pem');
		}
		else
		{
			$privateKey = openssl_pkey_get_private(Settings::read('actor', 'private', 'key'));
			$publicKey = Settings::read('actor', 'public', 'key');
		}

		$signature = '';

		openssl_sign($requestTarget, $signature, $privateKey, 'sha256WithRSAEncryption');

		$domain = \SeanMorris\Ids\Settings::read('default', 'domain');
		$scheme = 'https://';

		$signatureHeader = sprintf(
			'keyId="%s",headers="(request-target) host date digest",signature="%s"'
			, $scheme . $domain . '/ap/actor/sean#main-key'
			, base64_encode($signature)
		);

		\SeanMorris\Ids\Log::debug($signatureHeader);

		$context = stream_context_create($contextSource = ['http' => [
			'ignore_errors' => TRUE
			, 'content'     => $document
			, 'method'      => 'POST'
			, 'header' => [
				'Content-Type: application/ld+json'
				, 'Host: '      . $host
				, 'Date: '      . $now
				, 'Digest: '    . $hash
				, 'Signature: ' . $signatureHeader
			]
		]]);

		$body    = json_encode(file_get_contents($url, FALSE, $context));
		$headers = print_r($http_response_header, 1) . PHP_EOL;

		return [$headers, $body];
	}

	public static function types()
	{
		return \SeanMorris\Ids\Linker::classes(static::CLASS);
	}

	public static function getType($type)
	{
		$typesAvailable = \SeanMorris\Ids\Linker::classes(static::CLASS);

		\SeanMorris\Ids\Log::debug('Activity Types:', $typesAvailable);

		$result = [];

		foreach($typesAvailable as $activity)
		{
			$reflect = new \ReflectionClass($activity);

			$result[$reflect->getShortName()] = $activity;
		}

		unset($result['Activity']);

		return $result[$type] ?? NULL;
	}

	public function __get($name)
	{
		return $this->{$name} ?? NULL;
	}
}
