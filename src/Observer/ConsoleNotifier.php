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
		$color = $this->resolveColor($subject->getNotification());
		$message = $this->resolveMessage($subject->getNotification());

		$this->output->writeln(sprintf('<fg=%s;bg=black>   ↳ %s</>', $color, $message));
	}

	private function resolveColor(Notification $notification): string
	{
		if ($notification->getType() == Notification::TYPE_ERROR) {
			return 'red';
		}

		if ($notification->getType() == Notification::TYPE_SUCCESS) {
			return 'green';
		}

		if ($notification->getType() == Notification::TYPE_WARNING) {
			return 'yellow';
		}

		return 'default';
	}

	private function resolveMessage(Notification $notification): string
	{
		if ($notification->getType() == Notification::TYPE_INFO) {
			return $notification->getMessage();
		}

		return sprintf(
			'[%s] %s',
			$notification->getType() == Notification::TYPE_SUCCESS ? 'OK' : 'ERROR',
			$notification->getMessage()
		);
	}
}
