<?php

namespace Cercal\IO\MediaOrganizer\Observer;

use SplObserver;
use SplSubject;

class Publisher implements SplSubject
{
	private $observers;
	private $notification;
	private $report;

	public function __construct()
	{
		$this->observers = [];
		$this->report = [
			Notification::TYPE_ERROR => 0,
			Notification::TYPE_WARNING => 0,
			Notification::TYPE_INFO => 0,
			Notification::TYPE_SUCCESS => 0,
		];
	}

	public function attach(SplObserver $observer): void
	{
		$this->observers[] = $observer;
	}

	public function detach(SplObserver $observer): void
	{
		$key = array_search($observer, $this->observers, true);

		if ($key) {
			unset($this->observers[$key]);
		}
	}

	public function notify(): void
	{
		foreach ($this->observers as $value) {
			$value->update($this);
		}
	}

	public function getNotification(): Notification
	{
		return $this->notification;
	}

	private function updateReport(Notification $notification): void
	{
		$this->report[$notification->getType()]++;
	}

	public function getReport(): array
	{
		return $this->report;
	}

	public function publish(Notification $notification): void
	{
		$this->updateReport($notification);
		$this->notification = $notification;
		$this->notify();
	}
}
