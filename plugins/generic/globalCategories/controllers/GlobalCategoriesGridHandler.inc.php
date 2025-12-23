<?php
/**
 * @file GlobalCategoriesGridHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GlobalCategoriesGridHandler
 * @ingroup plugins_generic_globalCategories
 *
 * @brief Handler to render and save global category assignments for a context.
 */

import('lib.pkp.classes.handler.PKPHandler');

class GlobalCategoriesGridHandler extends PKPHandler {
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
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN),
			array('fetchGrid', 'save')
		);
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display the form for assigning global categories.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage
	 */
	function fetchGrid($args, $request) {
		return $this->_renderForm($request);
	}

	/**
	 * Save category assignments.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage
	 */
	function save($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		$context = $request->getContext();
		$selected = (array) $request->getUserVar('globalCategoryIds');

		$categories = self::$plugin->getSetting(CONTEXT_ID_NONE, 'globalCategories');
		if (!is_array($categories)) $categories = array();
		$validIds = array_map(function($category) {
			return (string) $category['id'];
		}, $categories);

		$newTitles = (array) $request->getUserVar('newCategoryTitle');
		$newCategories = array();

		$seenPaths = array();
		foreach ($categories as $category) {
			if (isset($category['path'])) {
				$seenPaths[] = (string) $category['path'];
			}
		}

		$locale = AppLocale::getLocale();
		foreach ($newTitles as $index => $title) {
			$rawTitle = trim((string) $title);
			if ($rawTitle === '') {
				continue;
			}

			$title = $this->_normalizeTitle($rawTitle);
			if ($title === '') {
				return $this->_renderForm($request, __('plugins.generic.globalCategories.settings.validationError'));
			}

			$newCategories[] = array(
				'title' => array($locale => $title),
				'titleRaw' => $title,
			);
		}

		if ($newCategories) {
			$lastId = (int) self::$plugin->getSetting(CONTEXT_ID_NONE, 'globalCategoriesLastId');
			foreach ($newCategories as $category) {
				$lastId++;
				$category['id'] = $lastId;
				$category['path'] = $this->_generateUniquePath($category['titleRaw'], $seenPaths);
				unset($category['titleRaw']);
				$seenPaths[] = $category['path'];
				$categories[] = $category;
				$validIds[] = (string) $category['id'];
				$selected[] = $category['id'];
			}
			self::$plugin->updateSetting(CONTEXT_ID_NONE, 'globalCategories', $categories, 'object');
			self::$plugin->updateSetting(CONTEXT_ID_NONE, 'globalCategoriesLastId', $lastId, 'int');
		}

		$selected = array_values(array_intersect($selected, $validIds));
		$selected = array_map('intval', $selected);

		self::$plugin->updateSetting($context->getId(), 'contextCategoryIds', $selected, 'object');

		return DAO::getDataChangedEvent();
	}

	/**
	 * Render the context form with optional error.
	 * @param $request PKPRequest
	 * @param $errorMessage string|null
	 * @return JSONMessage
	 */
	function _renderForm($request, $errorMessage = null) {
		$context = $request->getContext();

		$categories = self::$plugin->getSetting(CONTEXT_ID_NONE, 'globalCategories');
		if (!is_array($categories)) $categories = array();
		$locale = AppLocale::getLocale();
		foreach ($categories as &$category) {
			$title = $category['title'] ?? '';
			if (is_array($title)) {
				if (isset($title[$locale]) && $title[$locale] !== '') {
					$category['titleLocalized'] = $title[$locale];
				} else {
					$category['titleLocalized'] = reset($title) ?: '';
				}
			} else {
				$category['titleLocalized'] = (string) $title;
			}
		}
		$selected = self::$plugin->getSetting($context->getId(), 'contextCategoryIds');
		if (!is_array($selected)) $selected = array();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'categories' => $categories,
			'selectedCategoryIds' => $selected,
			'errorMessage' => $errorMessage,
		));

		import('lib.pkp.classes.form.Form');
		$form = new Form(self::$plugin->getTemplateResource('globalCategoriesContextForm.tpl'));
		return new JSONMessage(true, $form->fetch($request));
	}

	/**
	 * Normalize the category title.
	 * @param $title string
	 * @return string
	 */
	function _normalizeTitle($title) {
		$title = trim((string) $title);
		if ($title === '') return '';
		if (function_exists('mb_strtolower')) {
			$title = mb_strtolower($title, 'UTF-8');
			$first = mb_substr($title, 0, 1, 'UTF-8');
			$rest = mb_substr($title, 1, null, 'UTF-8');
			return mb_strtoupper($first, 'UTF-8') . $rest;
		}
		$title = strtolower($title);
		return strtoupper(substr($title, 0, 1)) . substr($title, 1);
	}

	/**
	 * Generate a unique path from a title.
	 * @param $title string
	 * @param $usedPaths array
	 * @return string
	 */
	function _generateUniquePath($title, $usedPaths) {
		$slug = $title;
		if (function_exists('iconv')) {
			$slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
		}
		$slug = strtolower((string) $slug);
		$slug = preg_replace('/[^a-z0-9_-]+/', '-', $slug);
		$slug = trim($slug, '-');
		if ($slug === '') {
			$slug = 'categoria';
		}

		$base = $slug;
		$counter = 2;
		while (in_array($slug, $usedPaths, true)) {
			$slug = $base . '-' . $counter;
			$counter++;
		}
		return $slug;
	}
}
