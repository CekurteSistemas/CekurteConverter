<?php

namespace Cercal\IO\MediaOrganizer\File;

use Cekurte\Environment\Environment;
use SplFileInfo;

final class Picture extends SplFileInfo
{
	private $exifData;

	public function __construct(string $filename)
	{
		parent::__construct($filename);
	}
	
    public function getExifData(): ExifData
    {
    	if (!$this->exifData) {
			$this->exifData = (new ExifDataReader(Environment::get('EXIFTOOL_BIN')))->read($this);
		}

		return $this->exifData;
    }
}
