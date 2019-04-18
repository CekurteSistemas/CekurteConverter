<?php

namespace Cercal\IO\MediaOrganizer\File;

use InvalidArgumentException;

class SearchContext
{
	private $absolutePath;
	private $recursive;
	private $filters;

	public function __construct(string $absolutePath, bool $recursive, array $filters)
	{
		$this->absolutePath = realpath($absolutePath);

		if ($this->absolutePath === false) {
			throw new InvalidArgumentException(sprintf('The directory "%s" does not exist.', $absolutePath));
		}

		$this->recursive = $recursive;

		foreach ($filters as $filter) {
			if (!$filter instanceof SearchFilter) {
				throw new InvalidArgumentException(sprintf('The filter "%s" does not follow the interface.', SearchFilter::class));
			}
		}
		
		$this->filters = $filters;
	}

	public function getAbsolutePath(): string
	{
		return $this->absolutePath;
	}

	public function isRecursive(): bool
	{
		return $this->recursive;
	}

	public function getFilters(): array
	{
		return $this->filters;
	}
}
