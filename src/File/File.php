<?php

namespace Cercal\IO\MediaOrganizer\File;

use SplFileInfo;

class File extends SplFileInfo
{
	private $exifData;

	public function __construct(string $filename)
	{
		parent::__construct($filename);
	}
	
    public function getExifData(): ExifData
    {
    	if (!$this->exifData) {
			$this->exifData = new ExifData($this);
		}

		return $this->exifData;
    }
}
