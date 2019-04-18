<?php

namespace Cercal\IO\MediaOrganizer\Command\Style;

use Cercal\IO\MediaOrganizer\Observer\Notification;
use Symfony\Component\Console\Style\SymfonyStyle;

class AppStyle extends SymfonyStyle
{
	public function notification(Notification $notification): void
	{
		$this->writeln(sprintf('<%s>   > %s</>', 'fg=default;bg=black', $this->formatMessage($notification)));
	}

	private function formatMessage(Notification $notification): string
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
