<?php

namespace Cercal\IO\MediaOrganizer\File;

use DateTimeImmutable;
use Cekurte\Environment\Environment;
use Symfony\Component\Process\Process;

class ExifData
{
	private $creationTime;

	private function buildCommandLine(\SplFileInfo $file): string
	{
		return sprintf(
			'%s -json -a "%s"',
			Environment::get('EXIFTOOL_BIN'),
			$file->getpathname()
		);
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

	public function __construct(\SplFileInfo $file)
	{
		$process = new Process($this->buildCommandLine($file));

		$process
			->setWorkingDirectory($file->getPath())
			->run(function ($type, $buffer) {
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
							}
						}
					}
				}
			})
		;
	}

	public function getCreationTime(): ?DateTimeImmutable
	{
		return $this->creationTime;
	}
}
