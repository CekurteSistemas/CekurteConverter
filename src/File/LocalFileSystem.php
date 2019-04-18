<?php

namespace Cercal\IO\MediaOrganizer\File;

use RuntimeException;

class LocalFileSystem
{
	public function cp(string $sourceFile, string $targetFile): void
	{
		if (!copy($sourceFile, $targetFile)) {
			throw new RuntimeException(sprintf(
				'An error occurred when copying "%s" to "%s".',
				$sourceFile,
				$targetFile
			));
		}
	}

	public function mv(string $sourceFile, string $targetFile): void
	{
		if (!rename($sourceFile, $targetFile)) {
			throw new RuntimeException(sprintf(
				'An error occurred when moving "%s" to "%s".',
				$sourceFile,
				$targetFile
			));
		}
	}

	public function rm(string $filename): void
	{
		if (!unlink($filename)) {
			throw new RuntimeException(sprintf(
				'An error occurred when deleting "%s".',
				$filename
			));
		}
	}

	public function exists(string $filename): bool
	{
		return file_exists($filename);
	}

	public function mkdir(string $directory): void
	{
		if (!mkdir($directory, 0777, true)) {
			throw new RuntimeException(sprintf(
				'An error occurred when creating the directory "%s".',
				$directory
			));
		}
	}

	public function hash(string $filename): string
	{
		return md5_file($filename);
	}

	public function compare(string $leftFilename, string $rightFilename): bool
	{
		return $this->hash($leftFilename) == $this->hash($rightFilename);
	}
}
