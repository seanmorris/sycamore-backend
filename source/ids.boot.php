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

	// var_dump($settings->dumpStruct());die;

	$redis->connect($settings->host, $settings->port ?: 6379);

	if($settings->pass)
	{
		$this->redis->auth($settings->pass);
	}

	return $redis;
});
