<?php

namespace Cercal\IO\MediaOrganizer\File;

final class DestinationArtifact
{
	private $path;
	private $file;
	private $extension;
	private $fileSuffix;

	public function __construct(string $path, string $file, string $extension, string $fileSuffix = '')
	{
		$this->path = $path;
		$this->file = $file;
		$this->extension = $extension;
		$this->fileSuffix = empty($fileSuffix) ? '' : '_' . $fileSuffix;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getFile(): string
	{
		return $this->file;
	}

	public function getFileSuffix(): string
	{
		return $this->fileSuffix;
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
			. $this->getFileSuffix()
			. '.'
			. $this->getExtension();
	}
}
