<?php
use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Router;
use \SeanMorris\Ids\Request;
use \SeanMorris\Ids\Settings;
use \SeanMorris\Ids\Http\Http404;

$composer = require '../vendor/seanmorris/ids/source/init.php';

if(isset($argv))
{
	$args = $argv;
	$script = array_shift($args);
}
else
{
	$request = new Request();
}

if(!$entrypoint = Settings::read('entrypoint'))
{
	print('No entrypoint specified. Please check local settings.');
	Log::error('No entrypoint specified. Please check local settings.');
	die;
}

$routes = new $entrypoint();
$router = new Router($request, $routes);
$router->contextSet('composer', $composer);

ob_start();

$response = $router->route();

$debug = ob_get_contents();

ob_end_clean();

$accString  = explode(',', $request->headers('Accept'));
$acceptList = array_map(function($a){ return explode(';', $a); },  $accString);
$acceptMime = [];

foreach($acceptList as $accept)
{
	$key = array_shift($accept);
	$val = $accept;

	$acceptMime[$key] = $val;
}

if($publicDir = Settings::read('publicDir'))
{
	$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

	if($requestPath === '/' || $response instanceof Http404)
	{
		$path = $publicDir . ($requestPath === '/' ? '/index.html' : $requestPath);

		if(!file_exists($path) || $requestPath === '/')
		{
			header('Content-Type: text/html');
			$path = '../docs/index.html';
		}

		if(substr($path, -3) === 'css')
		{
			header('Content-Type: text/css');
		}

		if(substr($path, -3) === 'svg')
		{
			header('Content-Type: image/svg+xml');
		}

		readfile($path);
		ob_end_flush();
		exit;
	}
}

if($response instanceof \SeanMorris\Ids\Api\Response)
{
	$response->send();
}
else if($response instanceof Traversable || is_array($response))
{
	foreach($response as $chunk)
	{
		if(SeanMorris\Ids\Http\Http::disconnected())
		{
			break;
		}

		echo dechex(strlen($chunk));
		echo "\r\n";
		echo $chunk;
		echo "\r\n";
		ob_get_level() && ob_flush();
		flush();
	}
	echo "0\r\n\r\n";
}
else
{
	print $response;
}

if(Settings::read('devmode') && $debug)
{
	printf('<pre>%s</pre>', $debug);
}

if(Settings::read('devmode') && $debug)
{
	printf('<pre>%s</pre>', $debug);
}
