<?php

namespace Cercal\IO\MediaOrganizer\Observer;

use SplObserver;
use SplSubject;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleNotifier implements SplObserver
{
	private $output;

	public function __construct(OutputInterface $output)
	{
		$this->output = $output;
	}

	public function update(SplSubject $subject)
	{
		$this->output->notification($subject->getNotification());
	}
}
