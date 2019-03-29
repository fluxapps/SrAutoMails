<?php

namespace srag\Plugins\SrAutoMails\Job;

use ilCronJob;
use ilCronJobResult;
use ilDateTime;
use ilLogLevel;
use ilSrAutoMailsPlugin;
use srag\DIC\SrAutoMails\DICTrait;
use srag\Notifications4Plugin\Notifications4Plugins\Exception\Notifications4PluginException;
use srag\Notifications4Plugin\Notifications4Plugins\Utils\Notifications4PluginTrait;
use srag\Plugins\Notifications4Plugins\Notification\Language\NotificationLanguage;
use srag\Plugins\Notifications4Plugins\Notification\Notification;
use srag\Plugins\SrAutoMails\ObjectType\ObjectType;
use srag\Plugins\SrAutoMails\Rule\Rule;
use srag\Plugins\SrAutoMails\Utils\SrAutoMailsTrait;
use Throwable;

/**
 * Class Job
 *
 * @package rag\Plugins\SrAutoMails\Job
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class Job extends ilCronJob {

	use DICTrait;
	use SrAutoMailsTrait;
	use Notifications4PluginTrait;
	const CRON_JOB_ID = ilSrAutoMailsPlugin::PLUGIN_ID;
	const PLUGIN_CLASS_NAME = ilSrAutoMailsPlugin::class;
	const LANG_MODULE_CRON = "cron";


	/**
	 * Job constructor
	 */
	public function __construct() {

	}


	/**
	 * Get id
	 *
	 * @return string
	 */
	public function getId(): string {
		return self::CRON_JOB_ID;
	}


	/**
	 * @return string
	 */
	public function getTitle(): string {
		return ilSrAutoMailsPlugin::PLUGIN_NAME;
	}


	/**
	 * @return string
	 */
	public function getDescription(): string {
		return "";
	}


	/**
	 * Is to be activated on "installation"
	 *
	 * @return boolean
	 */
	public function hasAutoActivation(): bool {
		return true;
	}


	/**
	 * Can the schedule be configured?
	 *
	 * @return boolean
	 */
	public function hasFlexibleSchedule(): bool {
		return true;
	}


	/**
	 * Get schedule type
	 *
	 * @return int
	 */
	public function getDefaultScheduleType(): int {
		return self::SCHEDULE_TYPE_IN_HOURS;
	}


	/**
	 * Get schedule value
	 *
	 * @return int|array
	 */
	public function getDefaultScheduleValue(): int {
		return 1;
	}


	/**
	 * Run job
	 *
	 * @return ilCronJobResult
	 */
	public function run(): ilCronJobResult {
		$time = time();
		$sent_mails_count = 0;

		$result = new ilCronJobResult();

		$object_types = self::objectTypes()->getObjectTypes();

		/**
		 * @var Rule[] $checked_rules
		 */
		$checked_rules = [];

		foreach ($object_types as $object_type) {
			$objects = $object_type->getObjects();

			$rules = self::rules()->getRulesForObjectType($object_type->getObjectType());

			foreach ($objects as $object) {

				foreach ($rules as $rule) {
					if ($object_type->checkRuleForObject($rule, $object)) {

						$receivers = $object_type->getReceivers($rule, $object);

						foreach ($receivers as $user_id) {
							if ($rule->getIntervalType() === Rule::INTERVAL_TYPE_NUMBER
								|| !self::sents()->hasSent($rule->getRuleId(), $object_type->getObjectId($object), $user_id)) {

								try {
									$this->sendNotification($rule, $object_type, $object, $user_id);

									self::sents()->sent($rule->getRuleId(), $object_type->getObjectId($object), $user_id);

									$sent_mails_count ++;
								} catch (Throwable $ex) {
									self::dic()->logger()->root()->log($ex->__toString(), ilLogLevel::ERROR);
								}
							}
						}
					}

					if (!isset($checked_rules[$rule->getRuleId()])) {
						$checked_rules[$rule->getRuleId()] = $rule;
					}
				}
			}
		}

		foreach ($checked_rules as $rule) {
			$rule->setLastCheck(new ilDateTime($time, IL_CAL_UNIX));

			$rule->store();
		}

		$result->setStatus(ilCronJobResult::STATUS_OK);

		$result->setMessage(nl2br(str_replace("\\n", "\n", self::plugin()->translate("status", self::LANG_MODULE_CRON, [
			count($rules),
			$sent_mails_count
		])), false));

		return $result;
	}


	/**
	 * @param Rule       $rule
	 * @param ObjectType $object_type
	 * @param object     $object
	 * @param int        $user_id
	 *
	 * @throws Notifications4PluginException
	 */
	protected function sendNotification(Rule $rule, ObjectType $object_type, $object, int $user_id)/*: void*/ {
		$notification = self::notification(Notification::class, NotificationLanguage::class)->getNotificationByName($rule->getMailTemplateName());

		$sender = self::sender()->factory()->internalMail(ANONYMOUS_USER_ID, $user_id);

		$placeholders = $object_type->getPlaceholdersForMail($object, $user_id, $rule);

		self::sender()->send($sender, $notification, $placeholders, $placeholders["receiver"]->getLanguage());
	}
}
