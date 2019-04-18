<?php

namespace Cercal\IO\MediaOrganizer\File;

class Search
{
	private $context;

	public function __construct(SearchContext $context)
	{
		$this->context = $context;
	}

	private function resolveIterator(): \Iterator
	{
		$iterator = new \RecursiveDirectoryIterator(
			$this->context->getAbsolutePath(),
			\FilesystemIterator::CURRENT_AS_FILEINFO
		);

		if ($this->context->isRecursive()) {
			return new \RecursiveIteratorIterator($iterator);
		}

		return $iterator;
	}

	private function applyFilters(\Iterator $iterator): \Iterator
	{
		return new \CallbackFilterIterator($iterator, function (\SplFileInfo $file) {
			foreach ($this->context->getFilters() as $filter) {
				if ($filter->filter($file) == false) {
					return false;
				}
			}

			return true;
		});
	}
	
    public function search(): array
    {
		$iterator = $this->applyFilters($this->resolveIterator());

        $searchResults = [];

        foreach ($iterator as $file) {
			$searchResults[] = new File($file->getPathname());
		}

        return $searchResults;
    }
}
