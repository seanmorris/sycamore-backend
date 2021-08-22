<?php

use \SeanMorris\Ids\Settings;

Settings::register('redis', function () {
	if(!$settings = \SeanMorris\Ids\Settings::read('redis'))
	{
		return FALSE;
	}

	$redis = new \Redis;

	$redis->connect(
		$settings->host
		, $settings->port ?: 6379
	);

	return $redis;
});
