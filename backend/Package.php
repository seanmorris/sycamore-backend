<?php
namespace SeanMorris\Sycamore;
class Package extends \SeanMorris\Ids\Package
{
	public function sourceDir()
	{
		$key = $this->packageName . '-source';

		if(isset(static::$directories[$key]))
		{
			return static::$directories[$key];
		}

		return static::$directories[$key] = new \SeanMorris\Ids\Disk\Directory(
			$this->packageDir() . 'backend/'
		);
	}

	public function publicDir()
	{
		$key = $this->packageName . '-public';

		if(isset(static::$directories[$key]))
		{
			return static::$directories[$key];
		}

		return static::$directories[$key] = new \SeanMorris\Ids\Disk\Directory(
			$this->packageDir() . 'docs/'
		);
	}
}
