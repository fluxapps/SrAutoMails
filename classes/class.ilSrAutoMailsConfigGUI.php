<?php

require_once __DIR__ . "/../vendor/autoload.php";

use srag\ActiveRecordConfig\ActiveRecordConfigGUI;
use srag\Plugins\SrAutoMails\Rule\Rule;
use srag\Plugins\SrAutoMails\Rule\RuleFormGUI;
use srag\Plugins\SrAutoMails\Rule\RulesTableGUI;
use srag\Plugins\SrAutoMails\Utils\SrAutoMailsTrait;

/**
 * Class ilSrAutoMailsConfigGUI
 *
 * @author studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class ilSrAutoMailsConfigGUI extends ActiveRecordConfigGUI {

	use SrAutoMailsTrait;
	const PLUGIN_CLASS_NAME = ilSrAutoMailsPlugin::class;
	const TAB_RULES = "rules";
	const CMD_ADD_RULE = "addRule";
	const CMD_CREATE_RULE = "createRule";
	const CMD_EDIT_RULE = "editRule";
	const CMD_UPDATE_RULE = "updateRule";
	const CMD_REMOVE_RULE_CONFIRM = "removeRuleConfirm";
	const CMD_REMOVE_RULE = "removeRule";
	const CMD_ENABLE_RULES = "enableRules";
	const CMD_DISABLE_RULES = "disableRules";
	const CMD_REMOVE_RULES_CONFIRM = "removeRulesConfirm";
	const CMD_REMOVE_RULES = "removeRules";
	/**
	 * @var array
	 */
	protected static $tabs = [ self::TAB_RULES => RulesTableGUI::class ];
	/**
	 * @var array
	 */
	protected static $custom_commands = [
		self::CMD_ADD_RULE,
		self::CMD_CREATE_RULE,
		self::CMD_EDIT_RULE,
		self::CMD_UPDATE_RULE,
		self::CMD_REMOVE_RULE,
		self::CMD_REMOVE_RULE_CONFIRM,
		self::CMD_REMOVE_RULES_CONFIRM,
		self::CMD_ENABLE_RULES,
		self::CMD_DISABLE_RULES,
		self::CMD_REMOVE_RULES
	];


	/**
	 * @param Rule|null $rule
	 *
	 * @return RuleFormGUI
	 */
	protected function getRuleForm(/*?*/
		Rule $rule = NULL): RuleFormGUI {
		$form = new RuleFormGUI($this, self::TAB_RULES, $rule);

		return $form;
	}


	/**
	 *
	 */
	protected function addRule()/*: void*/ {
		self::dic()->tabs()->activateTab(self::TAB_RULES);

		$form = $this->getRuleForm();

		self::plugin()->output($form);
	}


	/**
	 *
	 */
	protected function createRule()/*: void*/ {
		self::dic()->tabs()->activateTab(self::TAB_RULES);

		$form = $this->getRuleForm();

		$form->setValuesByPost();

		if (!$form->checkInput()) {
			self::plugin()->output($form);

			return;
		}

		$form->updateConfig();

		ilUtil::sendSuccess(self::plugin()->translate("added_rule", self::LANG_MODULE_CONFIG, [ $form->getRule()->getTitle() ]), true);

		self::dic()->ctrl()->setParameter($this, "srauma_rule_id", $form->getRule()->getRuleId());

		$this->redirectToTab(self::TAB_RULES);
	}


	/**
	 *
	 */
	protected function editRule()/*: void*/ {
		self::dic()->tabs()->activateTab(self::TAB_RULES);

		$rule_id = filter_input(INPUT_GET, "srauma_rule_id");
		$rule = self::rules()->getRuleById($rule_id);

		$form = $this->getRuleForm($rule);

		self::plugin()->output($form);
	}


	/**
	 *
	 */
	protected function updateRule()/*: void*/ {
		self::dic()->tabs()->activateTab(self::TAB_RULES);

		$rule_id = filter_input(INPUT_GET, "srauma_rule_id");
		$rule = self::rules()->getRuleById($rule_id);

		$form = $this->getRuleForm($rule);

		$form->setValuesByPost();

		if (!$form->checkInput()) {
			self::plugin()->output($form);

			return;
		}

		$form->updateConfig();

		ilUtil::sendSuccess(self::plugin()->translate("saved_rule", self::LANG_MODULE_CONFIG, [ $rule->getTitle() ]), true);

		$this->redirectToTab(self::TAB_RULES);
	}


	/**
	 *
	 */
	protected function removeRuleConfirm()/*: void*/ {
		self::dic()->tabs()->activateTab(self::TAB_RULES);

		$rule_id = filter_input(INPUT_GET, "srauma_rule_id");
		$rule = self::rules()->getRuleById($rule_id);

		$confirmation = new ilConfirmationGUI();

		self::dic()->ctrl()->setParameter($this, "srauma_rule_id", $rule->getRuleId());
		$confirmation->setFormAction(self::dic()->ctrl()->getFormAction($this));
		self::dic()->ctrl()->setParameter($this, "srauma_rule_id", NULL);

		$confirmation->setHeaderText(self::plugin()->translate("remove_rule_confirm", self::LANG_MODULE_CONFIG, [ $rule->getTitle() ]));

		$confirmation->addItem("srauma_rule_id", $rule->getRuleId(), $rule->getTitle());

		$confirmation->setConfirm($this->txt("remove"), self::CMD_REMOVE_RULE);
		$confirmation->setCancel($this->txt("cancel"), $this->getCmdForTab(self::TAB_RULES));

		self::plugin()->output($confirmation);
	}


	/**
	 *
	 */
	protected function removeRule()/*: void*/ {
		$rule_id = filter_input(INPUT_GET, "srauma_rule_id");
		$rule = self::rules()->getRuleById($rule_id);

		$rule->delete();

		ilUtil::sendSuccess(self::plugin()->translate("removed_rule", self::LANG_MODULE_CONFIG, [ $rule->getTitle() ]), true);

		$this->redirectToTab(self::TAB_RULES);
	}


	/**
	 *
	 */
	protected function enableRules()/*: void*/ {
		$rule_ids = filter_input(INPUT_POST, "srauma_rule_id", FILTER_DEFAULT, FILTER_FORCE_ARRAY);

		/**
		 * @var Rule[] $rules
		 */
		$rules = array_map(function (int $rule_id)/*: ?Rule*/ {
			return self::rules()->getRuleById($rule_id);
		}, $rule_ids);

		foreach ($rules as $rule) {
			$rule->setEnabled(true);

			$rule->store();
		}

		ilUtil::sendSuccess($this->txt("enabled_rules"), true);

		$this->redirectToTab(self::TAB_RULES);
	}


	/**
	 *
	 */
	protected function disableRules()/*: void*/ {
		$rule_ids = filter_input(INPUT_POST, "srauma_rule_id", FILTER_DEFAULT, FILTER_FORCE_ARRAY);

		/**
		 * @var Rule[] $rules
		 */
		$rules = array_map(function (int $rule_id)/*: ?Rule*/ {
			return self::rules()->getRuleById($rule_id);
		}, $rule_ids);

		foreach ($rules as $rule) {
			$rule->setEnabled(false);

			$rule->store();
		}

		ilUtil::sendSuccess($this->txt("disabled_rules"), true);

		$this->redirectToTab(self::TAB_RULES);
	}


	/**
	 *
	 */
	protected function removeRulesConfirm()/*: void*/ {
		self::dic()->tabs()->activateTab(self::TAB_RULES);

		$rule_ids = filter_input(INPUT_POST, "srauma_rule_id", FILTER_DEFAULT, FILTER_FORCE_ARRAY);

		/**
		 * @var Rule[] $rules
		 */
		$rules = array_map(function (int $rule_id)/*: ?Rule*/ {
			return self::rules()->getRuleById($rule_id);
		}, $rule_ids);

		$confirmation = new ilConfirmationGUI();

		$confirmation->setFormAction(self::dic()->ctrl()->getFormAction($this));

		$confirmation->setHeaderText($this->txt("remove_rules_confirm"));

		foreach ($rules as $rule) {
			$confirmation->addItem("srauma_rule_id", $rule->getRuleId(), $rule->getTitle());
		}

		$confirmation->setConfirm($this->txt("remove"), self::CMD_REMOVE_RULES);
		$confirmation->setCancel($this->txt("cancel"), $this->getCmdForTab(self::TAB_RULES));

		self::plugin()->output($confirmation);
	}


	/**
	 *
	 */
	protected function removeRules()/*: void*/ {
		$rule_ids = filter_input(INPUT_POST, "srauma_rule_id", FILTER_DEFAULT, FILTER_FORCE_ARRAY);

		/**
		 * @var Rule[] $rules
		 */
		$rules = array_map(function (int $rule_id)/*: ?Rule*/ {
			return self::rules()->getRuleById($rule_id);
		}, $rule_ids);

		foreach ($rules as $rule) {
			$rule->delete();
		}

		ilUtil::sendSuccess($this->txt("removed_rules"), true);

		$this->redirectToTab(self::TAB_RULES);
	}
}
