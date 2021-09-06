<?php
namespace SeanMorris\Sycamore\Media;
class Upload
{
	public static function upload($filename, $mime)
	{
		$s3 = \SeanMorris\Ids\Settings::get('amazonS3');

		$bucket  = 'sycamore-media';

		$newName = sprintf(
			'%s.%s.%s'
			, $this->publicId ?? uniqid()
			, microtime(TRUE)
			, $m[1]
		);

		$upload = $s3->upload(
			$bucket
			, $newName
			, fopen($filename, 'rb')
			, 'public-read'
			, ['params' => ['ContentType' => $mime]]
		);
	}
}
