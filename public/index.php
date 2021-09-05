<?php
use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Router;
use \SeanMorris\Ids\Request;
use \SeanMorris\Ids\Settings;

$path = '../docs' . ($_SERVER['REQUEST_URI'] === '/' ? '/index.html' : $_SERVER['REQUEST_URI']);

if(file_exists($path))
{
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
