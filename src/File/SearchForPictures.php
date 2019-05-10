<?php

namespace Cercal\IO\MediaOrganizer\File;

final class SearchForPictures implements SearchFilter
{
	private const SEARCH_FOR_ALLOWED_FILE_EXT = [
		'jpg',
		'jpeg',
		'png',
		'gif',
		'bmp',
		'heic',
		'raw',
	];

	public function filter(\SplFileInfo $file): bool
	{
		return in_array(strtolower($file->getExtension()), self::SEARCH_FOR_ALLOWED_FILE_EXT);
	}
}
