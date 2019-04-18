<?php

namespace Cercal\IO\MediaOrganizer\Observer;

use SplObserver;
use SplSubject;

class NotificationObservable implements SplSubject
{
	private $observers;
	private $notification;

	public function __construct()
	{
		$this->observers = [];
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

	public function setNotification(Notification $notification): void
	{
		$this->notification = $notification;
		$this->notify();
	}
}
