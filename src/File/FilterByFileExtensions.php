<?php

namespace Cercal\IO\MediaOrganizer\File;

class FilterByFileExtensions implements SearchFilter
{
	private $allowedFileExtensions;

	public function __construct(array $allowedFileExtensions)
	{
		$this->allowedFileExtensions = $allowedFileExtensions;
	}

	public function filter(\SplFileInfo $file): bool
	{
		return in_array(strtolower($file->getExtension()), $this->allowedFileExtensions);
	}
}
