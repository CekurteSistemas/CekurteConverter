<?php

namespace Cercal\IO\MediaOrganizer\File;

final class DestinationArtifact
{
	private $path;
	private $file;
	private $extension;

	public function __construct(string $path, string $file, string $extension)
	{
		$this->path = $path;
		$this->file = $file;
		$this->extension = $extension;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getFile(): string
	{
		return $this->file;
	}

	public function getExtension(): string
	{
		return $this->extension;
	}

	public function __toString(): string
	{
		return $this->getPath()
			. DIRECTORY_SEPARATOR
			. $this->getFile()
			. '.'
			. $this->getExtension();
	}
}
