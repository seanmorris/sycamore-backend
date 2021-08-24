<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class Inbox extends Controller
{
	public function index($router)
	{
		$entityBody = file_get_contents('php://input');
	}
}
