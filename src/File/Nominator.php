<?php

namespace Cercal\IO\MediaOrganizer\File;

interface Nominator
{
	public function nominate(): DestinationArtifact;
}
