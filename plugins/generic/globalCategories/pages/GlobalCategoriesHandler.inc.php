<?php
/**
 * @file GlobalCategoriesHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GlobalCategoriesHandler
 * @ingroup plugins_generic_globalCategories
 *
 * @brief Display global categories settings page.
 */

import('lib.pkp.classes.handler.PKPHandler');

class GlobalCategoriesHandler extends PKPHandler {
	/** @var GlobalCategoriesPlugin */
	static $plugin;

	/**
	 * Set the plugin.
	 * @param $plugin GlobalCategoriesPlugin
	 */
	static function setPlugin($plugin) {
		self::$plugin = $plugin;
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
		$this->addPolicy(new RoleBasedHandlerOperationPolicy($request, array(ROLE_ID_SITE_ADMIN), array('index', 'save')));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display the settings page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$this->_isBackendPage = true;
		$this->setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);
		$templateMgr = TemplateManager::getManager($request);

		self::$plugin->import('GlobalCategoriesSettingsForm');
		$form = new GlobalCategoriesSettingsForm(self::$plugin);
		$form->initData();

		$templateMgr->assign('settingsFormHtml', $form->fetch($request));
		$templateMgr->display(self::$plugin->getTemplateResource('adminSettingsPage.tpl'));
	}

	/**
	 * Save settings.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function save($args, $request) {
		$this->_isBackendPage = true;
		$this->setupTemplate($request);
		self::$plugin->import('GlobalCategoriesSettingsForm');
		$form = new GlobalCategoriesSettingsForm(self::$plugin);
		$form->readInputData();

		if ($form->validate()) {
			$form->execute();
			return new JSONMessage(true);
		}

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('settingsFormHtml', $form->fetch($request));
		return new JSONMessage(true, $templateMgr->fetch(self::$plugin->getTemplateResource('adminSettingsPage.tpl')));
	}
}
