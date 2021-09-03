<?php

use \SeanMorris\Ids\Settings;
use \SeanMorris\Sycamore\SessionHandler;

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

$sessionHandler = new SessionHandler;

session_set_save_handler($sessionHandler, TRUE);

session_set_cookie_params(['path' => '/']);
