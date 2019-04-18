<?php

namespace Cercal\IO\MediaOrganizer\Command\Question;

use Cekurte\Environment\Environment;
use InvalidArgumentException;
use Symfony\Component\Console\Question\Question;

class SourceDirectoryQuestion extends Question
{
	private const QUESTION_TITLE = 'Set the directory (absolute path)';
	private const QUESTION_ERROR = 'You must type an valid directory.';

	public function __construct()
	{
		parent::__construct(self::QUESTION_TITLE, Environment::get('ORGANIZER_SOURCE_ROOT_DIR'));
		$this->setValidator([$this, 'validator']);
	}

	public function validator(string $input): string
	{
		if (!is_dir($input = str_replace('\\ ', ' ', $input))) {
			throw new InvalidArgumentException(self::QUESTION_ERROR);
		}

		return $input;
	}
}
