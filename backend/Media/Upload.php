<?php
namespace SeanMorris\Sycamore\Media;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SeanMorris\PressKit\Controller;

class Upload extends Controller
{
	public static function upload($router)
	{
		$file = $router->request()->files('media');

		$s3 = Settings::get('amazonS3');

		$bucket  = 'sycamore-media';

		$newName = sprintf(
			'%s.%s.%s'
			, uniqid()
			, microtime(TRUE)
			, $file->extension()
		);

		// var_dump($file->realName(), $file->mime());die;

		$upload = $s3->upload(
			$bucket
			, $newName
			, fopen($file->realName(), 'rb')
			, 'public-read'
			, ['params' => ['ContentType' => $file->mime()]]
		);

		var_dump($upload);die;
	}
}
