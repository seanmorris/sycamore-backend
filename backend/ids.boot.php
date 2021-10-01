<?php
use \SeanMorris\Ids\Settings;

Settings::register('redis', function () {
	if(!$settings = Settings::read('redis'))
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

	if(!$settings = Settings::read('amazonS3'))
	{
		return FALSE;
	}

	static $client;

	if($client)
	{
		return $client;
	}

	$client = \Aws\S3\S3Client::factory([
		'version'     => '2006-03-01',
		'region'      => $settings->region,
		'credentials' => [
			'secret' => $settings->secret,
			'key'    => $settings->id,
		]
	]);

	return $client;
});
