<?php
use \SeanMorris\Ids\Settings;

Settings::register('redis', function () {
	if(!$settings = \SeanMorris\Ids\Settings::read('redis'))
	{
		return FALSE;
	}

	static $redis;

	if($redis)
	{
		return $redis;
	}

	$redis = new \Redis;

	$redis->connect($settings->host, $settings->port ?: 6379);

	if($settings->pass)
	{
		$redis->auth($settings->pass);
	}

	return $redis;
});

Settings::register('amazonS3', function () {

	static $client;

	if($client)
	{
		return $client;
	}

	$client = \Aws\S3\S3Client::factory([
		'credentials' => [
			'key'    => $amazonSettings->id,
			'secret' => $amazonSettings->secret,
		]
	]);

	return $client;
});
