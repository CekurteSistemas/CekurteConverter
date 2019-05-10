<?php

namespace Cercal\IO\MediaOrganizer\File;

use Closure;
use DateTimeImmutable;
use Cekurte\Environment\Environment;
use Symfony\Component\Process\Process;

class ExifData
{
	private $creationTime;

	public function __construct(\SplFileInfo $file)
	{
		$process = new Process($this->buildCommandLine($file));

		$process
			->setWorkingDirectory($file->getPath())
			->mustRun(Closure::fromCallable([$this, 'extractMetadata']))
		;
	}

	private function buildCommandLine(\SplFileInfo $file): array
	{
		return [
			Environment::get('EXIFTOOL_BIN'),
			'-json',
			'-a',
			$file->getpathname()
		];
	}

	private function resolveMetadataKeyNames(): array
	{
		return [
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
	}

	private function extractMetadata($type, $buffer): void
	{
		if (Process::OUT === $type) {
			$json = json_decode($buffer, true);

			if (!is_null($json)) {
				foreach ($this->resolveMetadataKeyNames() as $metadataKeyName) {
					if (in_array($metadataKeyName, array_keys($json[0]))) {
						$creationTime = trim($json[0][$metadataKeyName]);

						if (strpos($creationTime, ' DST') !== false) {
							$creationTime = substr($creationTime, 0, -4);
						}

						$format = strlen($creationTime) === 19 ? 'Y:m:d H:i:s' : 'Y:m:d H:i:sT';

						$this->creationTime = DateTimeImmutable::createFromFormat(
							$format,
							$creationTime
						);

						return;
					}
				}
			}
		}
	}

	public function getCreationTime(): DateTimeImmutable
	{
		return $this->creationTime;
	}
}
