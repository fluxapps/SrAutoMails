<?php

namespace srag\Notifications4Plugin\SrAutoMails\Sender;

use ilObjUser;
use srag\DIC\SrAutoMails\DICTrait;
use srag\Notifications4Plugin\SrAutoMails\Utils\Notifications4PluginTrait;

/**
 * Class Factory
 *
 * @package srag\Notifications4Plugin\SrAutoMails\Sender
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
final class Factory {

	use DICTrait;
	use Notifications4PluginTrait;
	/**
	 * @var self
	 */
	protected static $instance = null;


	/**
	 * @return self
	 */
	public static function getInstance(): self {
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Factory constructor
	 */
	private function __construct() {

	}


	/**
	 * @param string       $from
	 * @param string|array $to
	 *
	 * @return ExternalMailSender
	 */
	public function externalMail(string $from = "", $to = ""): ExternalMailSender {
		return new ExternalMailSender($from, $to);
	}


	/**
	 * @param int|string|ilObjUser $user_from
	 * @param int|string|ilObjUser $user_to
	 *
	 * @return InternalMailSender
	 */
	public function internalMail($user_from = 0, $user_to = ""): InternalMailSender {
		return new InternalMailSender($user_from, $user_to);
	}


	/**
	 * @param int|string|ilObjUser $user_from
	 * @param string|array         $to
	 * @param string               $method
	 * @param string               $uid
	 * @param int                  $startTime
	 * @param int                  $endTime
	 * @param int                  $sequence
	 *
	 * @return vcalendarSender
	 */
	public function vcalendar($user_from = 0, $to = "", string $method = vcalendarSender::METHOD_REQUEST, string $uid = "", int $startTime = 0, int $endTime = 0, int $sequence = 0): vcalendarSender {
		return new vcalendarSender($user_from, $to, $method, $uid, $startTime, $endTime, $sequence);
	}
}
