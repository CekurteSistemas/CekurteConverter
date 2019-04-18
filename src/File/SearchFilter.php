<?php

namespace Cercal\IO\MediaOrganizer\File;

interface SearchFilter
{
	public function filter(\SplFileInfo $file): bool;
}
