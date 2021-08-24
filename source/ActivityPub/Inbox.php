<?php
namespace SeanMorris\Sycamore\ActivityPub;

use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class Inbox extends Controller
{
	public function index($router)
	{
		if($router->request()->method() === 'POST')
		{
			if(!$entityBody = file_get_contents('php://input'))
			{
				return FALSE;
			}

			if(!$entity = json_decode($entityBody))
			{
				return FALSE;
			}

			var_dump($entity);
		}
	}
}
