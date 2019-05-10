<?php

namespace Cercal\IO\MediaOrganizer\File;

use DateTimeImmutable;

final class ExifData
{
	private $creationTime;

	public function __construct(DateTimeImmutable $creationTime)
	{
		$this->creationTime = $creationTime;
	}

	public function getCreationTime(): DateTimeImmutable
	{
		return $this->creationTime;
	}
}
