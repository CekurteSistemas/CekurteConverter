<?php

namespace Cercal\IO\MediaOrganizer\File;

final class Md5Tokenizer implements Tokenizer
{
	public function tokenize(string $filename): string
	{
		return md5_file($filename);
	}
}
