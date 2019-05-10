<?php

namespace Cercal\IO\MediaOrganizer\File;

use Closure;
use DateTimeImmutable;
use Symfony\Component\Process\Process;

final class ExifDataReader
{
	private const METADATA_KEYS = [
		'Date/Time Original',
		'Content Create Date',
		'Creation Date',
		'Create Date',
		'Track Create Date',
		'Media Create Date',
		'DateTimeOriginal',
		'CreateDate',
		'SonyDateTime'
	];

	private $exifTool;
	private $exifData;

	public function __construct(string $exifTool)
	{
		$this->exifTool = $exifTool;
	}

	private function buildCommandLine(string $filename): array
	{
		return [
			$this->exifTool,
			'-json',
			'-a',
			$filename
		];
	}

	private function extractMetadata($type, $buffer): void
	{
		if (Process::OUT === $type) {
			$json = json_decode($buffer, true);

			if (!is_null($json)) {
				foreach (self::METADATA_KEYS as $metadataKeyName) {
					if (in_array($metadataKeyName, array_keys($json[0]))) {
						$creationTime = trim($json[0][$metadataKeyName]);

						if (strpos($creationTime, ' DST') !== false) {
							$creationTime = substr($creationTime, 0, -4);
						}

						$format = strlen($creationTime) === 19 ? 'Y:m:d H:i:s' : 'Y:m:d H:i:sT';

						$this->exifData = new ExifData(DateTimeImmutable::createFromFormat(
							$format,
							$creationTime
						));

						return;
					}
				}
			}
		}
	}

	public function read(\SplFileInfo $file): ExifData
	{
		$commandLine = $this->buildCommandLine($file->getPathname());

		(new Process($commandLine))
			->setWorkingDirectory($file->getPath())
			->mustRun(Closure::fromCallable([$this, 'extractMetadata']))
		;

		if (is_null($this->exifData)) {
			throw new ExifDataNotFoundException($file->getPathname());
		}

		return $this->exifData;
	}
}
