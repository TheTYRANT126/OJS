<?php
/**
 * @file GlobalCategoriesSettingsForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GlobalCategoriesSettingsForm
 * @ingroup plugins_generic_globalCategories
 *
 * @brief Form to manage global categories list.
 */

import('lib.pkp.classes.form.Form');

class GlobalCategoriesSettingsForm extends Form {
	/** @var GlobalCategoriesPlugin */
	var $plugin;

	/**
	 * Constructor.
	 * @param $plugin GlobalCategoriesPlugin
	 */
	function __construct($plugin) {
		$this->plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * @copydoc Form::initData()
	 */
	function initData() {
		$categories = $this->plugin->getSetting(CONTEXT_ID_NONE, 'globalCategories');
		if (!is_array($categories)) $categories = array();
		$currentLocale = AppLocale::getLocale();
		foreach ($categories as &$category) {
			if (isset($category['title']) && !is_array($category['title'])) {
				$category['title'] = array($currentLocale => (string) $category['title']);
			}
		}
		$this->setData('categories', $categories);
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('categoryId', 'categoryTitle'));
	}

	/**
	 * @copydoc Form::validate()
	 */
	function validate(...$functionArgs) {
		$isValid = parent::validate(...$functionArgs);

		$ids = (array) $this->getData('categoryId');
		$titles = (array) $this->getData('categoryTitle');

		$hasTitles = false;
		foreach ($titles as $locale => $localeTitles) {
			foreach ((array) $localeTitles as $title) {
				if (trim((string) $title) !== '') {
					$hasTitles = true;
					break 2;
				}
			}
		}

		if (!$hasTitles) {
			return $isValid;
		}

		$categoryCount = 0;
		foreach ($titles as $locale => $localeTitles) {
			$categoryCount = max($categoryCount, count((array) $localeTitles));
		}

		for ($index = 0; $index < $categoryCount; $index++) {
			$hasAnyTitle = false;
			foreach ($titles as $locale => $localeTitles) {
				$title = trim((string) ($localeTitles[$index] ?? ''));
				if ($title !== '') {
					$hasAnyTitle = true;
					break;
				}
			}
			if (!$hasAnyTitle) {
				continue;
			}
		}

		return $isValid;
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$allLocales = AppLocale::getAllLocales();
		$primaryLocale = AppLocale::getLocale();
		$translationLocale = ($primaryLocale === 'en_US') ? 'es_ES' : 'en_US';
		$templateMgr->assign(array(
			'pluginName' => $this->plugin->getName(),
			'categories' => (array) $this->getData('categories'),
			'primaryLocale' => $primaryLocale,
			'translationLocale' => $translationLocale,
			'primaryLocaleName' => $allLocales[$primaryLocale] ?? $primaryLocale,
			'translationLocaleName' => $allLocales[$translationLocale] ?? $translationLocale,
			'currentLocale' => $primaryLocale,
		));
		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute(...$functionArgs) {
		$ids = (array) $this->getData('categoryId');
		$titles = (array) $this->getData('categoryTitle');

		$categories = array();
		$lastId = (int) $this->plugin->getSetting(CONTEXT_ID_NONE, 'globalCategoriesLastId');
		$primaryLocale = AppLocale::getLocale();
		$translationLocale = ($primaryLocale === 'en_US') ? 'es_ES' : 'en_US';
		$inputLocales = array($primaryLocale, $translationLocale);

		$existingCategories = (array) $this->plugin->getSetting(CONTEXT_ID_NONE, 'globalCategories');
		$existingById = array();
		foreach ($existingCategories as $existing) {
			if (isset($existing['id'])) {
				$existingById[(int) $existing['id']] = $existing;
			}
		}

		$categoryCount = 0;
		foreach ($titles as $locale => $localeTitles) {
			$categoryCount = max($categoryCount, count((array) $localeTitles));
		}

		$usedPaths = array();
		foreach ($existingCategories as $existing) {
			if (!empty($existing['path'])) {
				$usedPaths[] = $existing['path'];
			}
		}

		for ($index = 0; $index < $categoryCount; $index++) {
			$titleData = array();
			$inputRaw = array();
			foreach ($inputLocales as $locale) {
				$raw = trim((string) ($titles[$locale][$index] ?? ''));
				$inputRaw[$locale] = $raw;
				if ($raw === '') continue;
				$titleData[$locale] = $this->_normalizeTitle($raw);
			}

			if (!$titleData) {
				continue;
			}

			$id = (int) ($ids[$index] ?? 0);
			if ($id <= 0) {
				$id = ++$lastId;
			}

			$path = '';
			if (isset($existingById[$id]['path'])) {
				$path = (string) $existingById[$id]['path'];
			}
			if ($path === '') {
				$baseTitle = $titleData[$primaryLocale] ?? reset($titleData);
				$path = $this->_generateUniquePath($baseTitle, $usedPaths);
				$usedPaths[] = $path;
			}

			$existingTitle = array();
			if (isset($existingById[$id]['title']) && is_array($existingById[$id]['title'])) {
				$existingTitle = $existingById[$id]['title'];
			}
			$mergedTitle = $existingTitle;
			foreach ($inputLocales as $locale) {
				if (trim((string) ($inputRaw[$locale] ?? '')) === '') {
					unset($mergedTitle[$locale]);
					continue;
				}
				$mergedTitle[$locale] = $titleData[$locale];
			}

			$categories[] = array(
				'id' => $id,
				'title' => $mergedTitle,
				'path' => $path,
			);
		}

		$this->plugin->updateSetting(CONTEXT_ID_NONE, 'globalCategories', $categories, 'object');
		$this->plugin->updateSetting(CONTEXT_ID_NONE, 'globalCategoriesLastId', $lastId, 'int');

		$this->_cleanupContextAssignments($categories);

		parent::execute(...$functionArgs);
	}

	/**
	 * Remove assignments to deleted categories.
	 * @param $categories array
	 */
	function _cleanupContextAssignments($categories) {
		$validIds = array();
		foreach ($categories as $category) {
			$validIds[] = (int) $category['id'];
		}

		$contextDao = Application::getContextDAO();
		$contexts = $contextDao->getAll(true);
		while ($context = $contexts->next()) {
			$assigned = $this->plugin->getSetting($context->getId(), 'contextCategoryIds');
			if (!is_array($assigned) || !$assigned) continue;
			$filtered = array_values(array_intersect($assigned, $validIds));
			if ($filtered !== $assigned) {
				$this->plugin->updateSetting($context->getId(), 'contextCategoryIds', $filtered, 'object');
			}
		}
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
