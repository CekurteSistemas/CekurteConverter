<?php

namespace Cercal\IO\MediaOrganizer\Observer;

class Notification
{
	public const TYPE_INFO = 'info';
	public const TYPE_SUCCESS = 'success';
	public const TYPE_ERROR = 'error';
	public const TYPE_WARNING = 'warning';

	private $type;
	private $message;

	private function __construct(string $message, string $type = self::TYPE_INFO)
	{
		$this->setMessage($message);
		$this->setType($type);
	}

	public static function success(string $message): Notification
	{
		return new Notification($message, self::TYPE_SUCCESS);
	}

	public static function error(string $message): Notification
	{
		return new Notification($message, self::TYPE_ERROR);
	}

	public static function warning(string $message): Notification
	{
		return new Notification($message, self::TYPE_WARNING);
	}

	public static function info(string $message): Notification
	{
		return new Notification($message, self::TYPE_INFO);
	}

	private function setType(string $type): void
	{
		$this->type = $type;
	}

	private function setMessage(string $message): void
	{
		$this->message = $message;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getMessage(): string
	{
		return $this->message;
	}
}
