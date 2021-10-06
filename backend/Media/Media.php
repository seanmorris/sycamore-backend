<?php
namespace SeanMorris\Sycamore\Media;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SeanMorris\Ids\Http\Http404;

use \SeanMorris\PressKit\Controller;

use \SeanMorris\Sycamore\ActivityPub\Type\Actor;

class Media extends Controller
{
	public function __construct()
	{
		session_start();

		$this->currentUser = $_SESSION['current-user'] ?? FALSE;
	}

	public function index($router)
	{

	}

	public function upload($router)
	{
		$redis = Settings::get('redis');

		if(!$this->currentUser)
		{
			throw new Http404;
		}

		$actorName = $this->currentUser->username;

		$currentActor = Actor::getLocalActor($actorName);

		$file = $router->request()->files('media');

		if(!$file)
		{
			return;
		}

		$s3 = Settings::get('amazonS3');

		$bucket  = 'sycamore-media';

		$newName = sprintf(
			'%s.%s.%s'
			, uniqid()
			, microtime(TRUE)
			, $file->extension()
		);

		$upload = $s3->upload(
			$bucket
			, $newName
			, fopen($file->realName(), 'rb')
			, 'public-read'
			, ['params' => ['ContentType' => $file->mime()]]
		);

		$mediaUrl = $upload->get('ObjectURL');

		$file = (object) [
			'actor'  => $currentActor->id
			, 'mime' => $file->mime()
			, 'id'   => $mediaUrl
		];

		$collectionId = 'activity-pub::local-media::' . $actorName . '::';

		$encoded = json_encode($file);

		$redis->hset($collectionId . '::h', $mediaUrl, $encoded);

		$redis->zadd($collectionId . '::z', time(), $file->id);

		return $encoded;
	}
}
