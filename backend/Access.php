<?php
namespace SeanMorris\Sycamore;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class Access extends Controller
{
	public function whoami($router)
	{
		session_start();

		if(!empty($_SESSION['current-user']))
		{
			return json_encode($_SESSION['current-user']);
		}

		return json_encode(NULL);
	}

	public function login($router)
	{
		if(!$redis = Settings::get('redis'))
		{
			return FALSE;
		}

		if($router->request()->method() !== 'POST')
		{
			return FALSE;
		}

		$username = $router->request()->post('username');
		$password = $router->request()->post('password');

		if(!$userSource = $redis->hget('access-list', $username))
		{
			return FALSE;
		}

		if(!$user = json_decode($userSource))
		{
			return FALSE;
		}

		$result = clone $user;

		unset($result->password);

		session_start();

		$_SESSION['current-user'] = $result;

		return json_encode($result);
	}

	public function logout($router)
	{
		session_start();
		session_destroy();
		session_write_close();

		return json_encode(1);
	}

	public function register($router)
	{
		if(!$redis = Settings::get('redis'))
		{
			return FALSE;
		}

		if($router->request()->method() !== 'POST')
		{
			return FALSE;
		}

		$username = $router->request()->post('username');
		$password = $router->request()->post('password');

		if($existing = $redis->hget('access-list', $username))
		{
			return FALSE;
		}

		$user = (object)[
			'username' => $username
			, 'password' => password_hash($password, PASSWORD_DEFAULT)
		];

		$redis->hset('access-list', $username, json_encode($user));

		$result = clone $user;

		unset($result->password);

		session_start();

		$_SESSION['current-user'] = $result;

		return json_encode($result);
	}
}
