<?php

namespace Cercal\IO\MediaOrganizer\File;

use RuntimeException;
use Throwable;

final class ExifDataNotFoundException extends RuntimeException
{
	public function __construct(string $filename, int $code = 0, Throwable $previous = null)
	{
		$message = sprintf('Exif data not found (filename: "%s")', $filename);

		parent::__construct($message, $code, $previous);
	}
}
