<?php
/**
 * @file GlobalCategoriesPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GlobalCategoriesPlugin
 * @ingroup plugins_generic_globalCategories
 *
 * @brief Global categories plugin for assigning shared categories to journals.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class GlobalCategoriesPlugin extends GenericPlugin {
	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.globalCategories.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.globalCategories.description');
	}

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		$this->addLocaleData();
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;

		if ($success && $this->getEnabled($mainContextId)) {
			HookRegistry::register('Template::Settings::context', array($this, 'callbackShowContextSettingsTabs'));
			HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
			HookRegistry::register('Templates::Index::journal', array($this, 'callbackJournalCategories'));
			HookRegistry::register('Templates::Admin::Index::SiteManagement', array($this, 'callbackAdminLink'));
			HookRegistry::register('LoadHandler', array($this, 'callbackLoadHandler'));
		}

		return $success;
	}

	/**
	 * Permit requests to the global categories handler.
	 * @param $hookName string
	 * @param $params array
	 * @return boolean
	 */
	function setupGridHandler($hookName, $params) {
		$component =& $params[0];
		if ($component == 'plugins.generic.globalCategories.controllers.GlobalCategoriesGridHandler') {
			import($component);
			GlobalCategoriesGridHandler::setPlugin($this);
			return true;
		}
		return false;
	}

	/**
	 * Extend the context settings tabs to include global categories.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function callbackShowContextSettingsTabs($hookName, $args) {
		$templateMgr = $args[1];
		$output =& $args[2];

		$output .= $templateMgr->fetch($this->getTemplateResource('globalCategoriesTab.tpl'));

		return false;
	}

	/**
	 * Add global categories to the journal homepage.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function callbackJournalCategories($hookName, $args) {
		$templateMgr = $args[1];
		$output =& $args[2];

		$request = Application::get()->getRequest();
		$context = $request->getContext();
		if (!$context) return false;

		$categories = $this->getSetting(CONTEXT_ID_NONE, 'globalCategories');
		if (!is_array($categories)) $categories = array();
		$selected = $this->getSetting($context->getId(), 'contextCategoryIds');
		if (!is_array($selected)) $selected = array();

		$selectedCategories = array();
		foreach ($categories as $category) {
			if (in_array($category['id'], $selected)) {
				$selectedCategories[] = array(
					'id' => $category['id'],
					'title' => $this->_getLocalizedCategoryTitle($category),
					'path' => $category['path'] ?? '',
				);
			}
		}

		if (!$selectedCategories) return false;

		$templateMgr->assign('globalCategories', $selectedCategories);
		$output .= $templateMgr->fetch($this->getTemplateResource('globalCategoriesJournal.tpl'));

		return false;
	}

	/**
	 * Add a shortcut link on the admin dashboard.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function callbackAdminLink($hookName, $args) {
		$output =& $args[2];
		$request = Application::get()->getRequest();
		$dispatcher = $request->getDispatcher();
		$url = $dispatcher->url(
			$request,
			ROUTE_PAGE,
			null,
			'globalCategories'
		);

		$output .= '<li><a href="' . htmlspecialchars($url) . '">' .
			__('plugins.generic.globalCategories.adminLink') . '</a></li>';

		return false;
	}

	/**
	 * Handle direct page requests for global categories.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function callbackLoadHandler($hookName, $args) {
		$page =& $args[0];
		if ($page !== 'globalCategories') {
			return false;
		}

		define('HANDLER_CLASS', 'GlobalCategoriesHandler');
		$this->import('pages.GlobalCategoriesHandler');
		GlobalCategoriesHandler::setPlugin($this);
		return true;
	}

	/**
	 * Get localized title from a category.
	 * @param $category array
	 * @return string
	 */
	function _getLocalizedCategoryTitle($category) {
		$title = $category['title'] ?? '';
		if (is_array($title)) {
			$locale = AppLocale::getLocale();
			if (isset($title[$locale])) return $title[$locale];
			foreach ($title as $value) {
				if ($value !== '') return $value;
			}
			return '';
		}
		return (string) $title;
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $verb) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url(
							$request,
							null,
							null,
							'manage',
							null,
							array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')
						),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			),
			parent::getActions($request, $verb)
		);
	}

	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				if (!Validation::isSiteAdmin()) return new JSONMessage(false);
				AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->registerPlugin('function', 'plugin_url', array($this, 'smartyPluginUrl'));

				$this->import('GlobalCategoriesSettingsForm');
				$form = new GlobalCategoriesSettingsForm($this);

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}
}
