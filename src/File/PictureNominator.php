<?php

namespace Cercal\IO\MediaOrganizer\File;

use Cekurte\Environment\Environment;

final class PictureNominator implements Nominator
{
	private const TOKENIZER_OUTPUT_DIR = 'unknown';

	private $picture;
	private $tokenizer;

	public function __construct(Picture $picture, Tokenizer $tokenizer)
	{
		$this->picture = $picture;
		$this->tokenizer = $tokenizer;
	}

	private function resolveBasePath(): string
	{
		return Environment::get('ORGANIZER_TARGET_ROOT_DIR') . DIRECTORY_SEPARATOR;
	}

	private function resolveRelativePath(): string
	{
		$format = array_filter(explode('/', Environment::get('ORGANIZER_TARGET_DIR_NAME_FORMAT')));

		try {
			$creationTime = $this->picture->getExifData()->getCreationTime();
		} catch (ExifDataNotFoundException $e) {
			return self::TOKENIZER_OUTPUT_DIR;
		}

		$result = '';

		foreach ($format as $current) {
			$result .= $creationTime->format($current) . DIRECTORY_SEPARATOR;
		}

		return substr($result, 0, -1);
	}

	private function resolveFile(): string
	{
		$format = array_filter(explode(':', Environment::get('ORGANIZER_TARGET_FILE_NAME_FORMAT')));
		$separator = Environment::get('ORGANIZER_TARGET_FILE_NAME_SEPARATOR');

		try {
			$creationTime = $this->picture->getExifData()->getCreationTime();
		} catch (ExifDataNotFoundException $e) {
			return $this->tokenizer->tokenize($this->picture);
		}

		$result = '';

		foreach ($format as $current) {
			$result .= $creationTime->format($current) . $separator;
		}

		return substr($result, 0, -1);
	}

	private function resolveExtension(): string
	{
		return strtolower($this->picture->getExtension());
	}

	public function nominate(): DestinationArtifact
	{
		return new DestinationArtifact(
			$this->resolveBasePath() . $this->resolveRelativePath(),
			$this->resolveFile(),
			$this->resolveExtension()
		);
	}
}
