<?php

namespace Cercal\IO\MediaOrganizer\File;

class SearchForPictures implements SearchFilter
{
	public function filter(\SplFileInfo $file): bool
	{
		$fileExtensions = [
			'jpg',
			'jpeg',
			'png',
			'gif',
			'bmp',
			'heic',
			'raw'
		];

		return (new FilterByFileExtensions($fileExtensions))->filter($file);
	}
}
