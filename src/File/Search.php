<?php

namespace Cercal\IO\MediaOrganizer\File;

use Iterator;
use SplFileInfo;

final class Search
{
	private function resolveIterator(SearchContext $context): \Iterator
	{
		$iterator = new \RecursiveDirectoryIterator(
			$context->getAbsolutePath(),
			\FilesystemIterator::CURRENT_AS_FILEINFO
		);

		if ($context->isRecursiveSearchEnabled()) {
			return new \RecursiveIteratorIterator($iterator);
		}

		return $iterator;
	}

	private function applyFilters(SearchContext $context, Iterator $iterator): Iterator
	{
		return new \CallbackFilterIterator($iterator, function (SplFileInfo $file) use ($context) {
			foreach ($context->getFilters() as $filter) {
				if ($filter->filter($file) == false) {
					return false;
				}
			}

			return true;
		});
	}
	
    public function search(SearchContext $context): array
    {
    	$iterator = $this->resolveIterator($context);
    	$filteredIterator = $this->applyFilters($context, $iterator);

        $searchResults = [];

        foreach ($filteredIterator as $file) {
			$searchResults[] = new Picture($file->getPathname());
		}

        return $searchResults;
    }
}
