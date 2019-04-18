<?php

namespace Cercal\IO\MediaOrganizer\File;

interface Tokenizer
{
	public function tokenize(string $filename): string;
}
