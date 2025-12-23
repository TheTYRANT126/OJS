<?php
/**
 * Funciones para conectar con OJS
 * Aquí centralizas todas las llamadas a OJS
 */

// Definir la ruta base de OJS (subir dos niveles desde includes/)
$ojsBasePath = dirname(dirname(dirname(__FILE__)));

// Definir constantes requeridas por OJS
if (!defined('INDEX_FILE_LOCATION')) {
    define('INDEX_FILE_LOCATION', $ojsBasePath . '/index.php');
}

// Cambiar al directorio de OJS
chdir($ojsBasePath);

// Incluir el bootstrap de OJS (varia segun version)
$bootstrapPath = $ojsBasePath . '/lib/pkp/includes/bootstrap.inc.php';
if (!file_exists($bootstrapPath)) {
    $bootstrapPath = $ojsBasePath . '/lib/pkp/includes/bootstrap.php';
}
$application = require($bootstrapPath);

// Inicializar el request si no existe
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/index/index';
}
if (!isset($_SERVER['PATH_INFO'])) {
    $_SERVER['PATH_INFO'] = '/index/index';
}

/**
 * Obtener lista de locales preferidos sin depender del router de OJS.
 */
function getPreferredLocales($lang = null) {
    $locales = [];
    if ($lang) {
        $lang = strtolower($lang);
        if (strpos($lang, '_') !== false) {
            $locales[] = $lang;
        } elseif ($lang === 'es') {
            $locales[] = 'es_ES';
            $locales[] = 'es_MX';
            $locales[] = 'es';
        } elseif ($lang === 'en') {
            $locales[] = 'en_US';
            $locales[] = 'en_GB';
            $locales[] = 'en';
        } else {
            $locales[] = $lang;
        }
    }

    $defaultLocale = Config::getVar('i18n', 'locale');
    if ($defaultLocale) {
        $locales[] = $defaultLocale;
    }

    return array_values(array_unique(array_filter($locales)));
}

/**
 * Tomar valor localizado sin usar AppLocale (evita dependencia del router).
 */
function pickLocalizedValue($data, $locales) {
    if (!is_array($data)) {
        return $data ?: '';
    }

    foreach ($locales as $locale) {
        if (isset($data[$locale]) && $data[$locale] !== '') {
            return $data[$locale];
        }
    }

    foreach ($data as $value) {
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

/**
 * Construir URL de una revista desde su path y la base_url configurada.
 */
function buildJournalUrl($path) {
    $baseUrl = rtrim((string) Config::getVar('general', 'base_url'), '/');
    if ($path === null || $path === '') {
        return $baseUrl;
    }
    return $baseUrl . '/' . ltrim($path, '/');
}

/**
 * Obtener categorías globales (plugin globalCategories).
 */
function getGlobalCategories() {
    $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
    if (!$pluginSettingsDao) {
        return [];
    }

    $categories = $pluginSettingsDao->getSetting(CONTEXT_ID_NONE, 'globalcategoriesplugin', 'globalCategories');
    return is_array($categories) ? $categories : [];
}

/**
 * Obtener IDs de categorías globales asignadas a un contexto.
 */
function getContextGlobalCategoryIds($contextId) {
    $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
    if (!$pluginSettingsDao) {
        return [];
    }

    $ids = $pluginSettingsDao->getSetting((int) $contextId, 'globalcategoriesplugin', 'contextCategoryIds');
    return is_array($ids) ? $ids : [];
}

/**
 * Obtener todos los contextos (revistas) del sistema
 */
function getAllJournals($limit = null, $offset = 0, $lang = null) {
    $contextDao = Application::getContextDAO();
    $contexts = $contextDao->getAll(true); // true = enabled only

    $journals = [];
    $count = 0;
    $locales = getPreferredLocales($lang);
    $globalCategories = getGlobalCategories();
    $globalCategoriesById = [];
    foreach ($globalCategories as $category) {
        if (isset($category['id'])) {
            $globalCategoriesById[(int) $category['id']] = $category;
        }
    }

    while ($context = $contexts->next()) {
        if ($offset > 0 && $count < $offset) {
            $count++;
            continue;
        }

        $issueDao = DAORegistry::getDAO('IssueDAO');
        $currentIssue = $issueDao->getCurrent($context->getId());

        $name = pickLocalizedValue($context->getData('name'), $locales);
        $description = pickLocalizedValue($context->getData('description'), $locales);
        $coverImage = null;
        if ($currentIssue) {
            $coverImageName = pickLocalizedValue($currentIssue->getData('coverImage'), $locales);
            if ($coverImageName) {
                import('classes.file.PublicFileManager');
                $publicFileManager = new PublicFileManager();
                $coverImage = rtrim((string) Config::getVar('general', 'base_url'), '/') . '/' .
                    $publicFileManager->getContextFilesPath($context->getId()) . '/' . $coverImageName;
            }
        }
        if (!$coverImage) {
            $thumbnailData = pickLocalizedValue($context->getData('journalThumbnail'), $locales);
            if (is_array($thumbnailData) && !empty($thumbnailData['uploadName'])) {
                import('classes.file.PublicFileManager');
                $publicFileManager = new PublicFileManager();
                $coverImage = rtrim((string) Config::getVar('general', 'base_url'), '/') . '/' .
                    $publicFileManager->getContextFilesPath($context->getId()) . '/' . $thumbnailData['uploadName'];
            }
        }

        $contextCategoryIds = getContextGlobalCategoryIds($context->getId());
        $contextGlobalCategories = [];
        foreach ($contextCategoryIds as $categoryId) {
            $categoryId = (int) $categoryId;
            if (isset($globalCategoriesById[$categoryId])) {
                $category = $globalCategoriesById[$categoryId];
                $contextGlobalCategories[] = [
                    'id' => $categoryId,
                    'title' => pickLocalizedValue($category['title'] ?? '', $locales),
                    'path' => $category['path'] ?? '',
                ];
            }
        }

        $journals[] = [
            'id' => $context->getId(),
            'name' => $name,
            'description' => $description,
            'path' => $context->getPath(),
            'url' => buildJournalUrl($context->getPath()),
            'coverImage' => $coverImage,
            'lastModified' => $currentIssue ? $currentIssue->getLastModified() : $context->getData('lastModified'),
            'globalCategories' => $contextGlobalCategories,
        ];

        $count++;
        if ($limit && count($journals) >= $limit) break;
    }

    return $journals;
}

/**
 * Obtener revistas por categoría
 */
function getJournalsByCategory($categoryId = null, $lang = null) {
    if ($categoryId === null) {
        return getAllJournals(null, 0, $lang);
    }

    // Implementar filtrado por categoría según la estructura de OJS
    // Por ahora retornamos todas
    return getAllJournals(null, 0, $lang);
}

/**
 * Contar total de revistas y artículos
 */
function getTotalCounts() {
    // Contar revistas
    $contextDao = Application::getContextDAO();
    $contexts = $contextDao->getAll(true);
    $journalCount = 0;
    $articleCount = 0;

    // Contar artículos publicados en todas las revistas
    $submissionDao = DAORegistry::getDAO('SubmissionDAO');

    while ($context = $contexts->next()) {
        $journalCount++;
        $submissions = $submissionDao->getByContextId($context->getId());
        while ($submission = $submissions->next()) {
            if ($submission->getData('status') == STATUS_PUBLISHED) {
                $articleCount++;
            }
        }
    }

    return [
        'journals' => $journalCount,
        'articles' => $articleCount
    ];
}

/**
 * Obtener todas las categorías de las revistas
 */
function getAllCategories($lang = null) {
    $globalCategories = getGlobalCategories();
    $result = [];
    $locales = getPreferredLocales($lang);

    foreach ($globalCategories as $category) {
        $result[] = [
            'id' => $category['id'] ?? null,
            'name' => pickLocalizedValue($category['title'] ?? '', $locales),
            'path' => $category['path'] ?? '',
        ];
    }

    return $result;
}

/**
 * Obtener revistas recientes (últimas modificadas)
 */
function getRecentJournals($limit = 3, $lang = null) {
    $journals = getAllJournals(null, 0, $lang);

    // Ordenar por fecha de última modificación (fallback al ID si no hay fecha)
    usort($journals, function($a, $b) {
        $aTime = !empty($a['lastModified']) ? strtotime($a['lastModified']) : 0;
        $bTime = !empty($b['lastModified']) ? strtotime($b['lastModified']) : 0;

        if ($aTime && $bTime) {
            return $bTime <=> $aTime;
        }

        if ($aTime || $bTime) {
            return $bTime <=> $aTime;
        }

        $aId = $a['id'] ?? 0;
        $bId = $b['id'] ?? 0;
        return $bId <=> $aId;
    });

    return array_slice($journals, 0, $limit);
}

/**
 * Obtener todos los artículos publicados
 */
function getPublishedArticles($limit = 10, $offset = 0, $lang = null) {
    $submissionDao = DAORegistry::getDAO('SubmissionDAO');
    $contextDao = Application::getContextDAO();
    $contexts = $contextDao->getAll(true);

    $articles = [];
    $locales = getPreferredLocales($lang);
    while ($context = $contexts->next()) {
        $submissions = $submissionDao->getByContextId($context->getId());

        while ($submission = $submissions->next()) {
            if ($submission->getData('status') == STATUS_PUBLISHED) {
                $publication = $submission->getCurrentPublication();
                $title = $publication ? pickLocalizedValue($publication->getData('title'), $locales) : '';
                $abstract = $publication ? pickLocalizedValue($publication->getData('abstract'), $locales) : '';

                $articles[] = [
                    'id' => $submission->getId(),
                    'title' => $title,
                    'abstract' => $abstract,
                    'authors' => $submission->getAuthorString(),
                    'date' => $submission->getDatePublished(),
                ];
            }

            if (count($articles) >= $limit) break 2;
        }
    }

    return $articles;
}

/**
 * Buscar artículos o revistas
 */
function searchContent($query, $type = 'all', $filters = [], $lang = null) {
    $results = [
        'journals' => [],
        'articles' => []
    ];

    $locales = getPreferredLocales($lang);
    if ($type === 'all' || $type === 'revista') {
        $contextDao = Application::getContextDAO();
        $contexts = $contextDao->getAll(true);

        while ($context = $contexts->next()) {
            $name = pickLocalizedValue($context->getData('name'), $locales);
            if (stripos($name, $query) !== false) {
                $results['journals'][] = [
                    'id' => $context->getId(),
                    'name' => $name,
                    'path' => $context->getPath()
                ];
            }
        }
    }

    if ($type === 'all' || $type === 'articulo') {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $contextDao = Application::getContextDAO();
        $contexts = $contextDao->getAll(true);

        while ($context = $contexts->next()) {
            $submissions = $submissionDao->getByContextId($context->getId());

            while ($submission = $submissions->next()) {
                if ($submission->getData('status') == STATUS_PUBLISHED) {
                    $publication = $submission->getCurrentPublication();
                    $title = $publication ? pickLocalizedValue($publication->getData('title'), $locales) : '';
                    $abstract = $publication ? pickLocalizedValue($publication->getData('abstract'), $locales) : '';
                    $authors = $submission->getAuthorString();

                    if (stripos($title, $query) !== false ||
                        stripos($abstract, $query) !== false ||
                        stripos($authors, $query) !== false) {

                        $results['articles'][] = [
                            'id' => $submission->getId(),
                            'title' => $title,
                            'authors' => $authors,
                        ];
                    }
                }
            }
        }
    }

    return $results;
}

/**
 * Obtener número actual
 */
function getCurrentIssue($contextId = null, $lang = null) {
    $issueDao = DAORegistry::getDAO('IssueDAO');
    if ($contextId === null) {
        $contextDao = Application::getContextDAO();
        $contexts = $contextDao->getAll(true);
        $context = $contexts->next();
        if (!$context) {
            return null;
        }
        $contextId = $context->getId();
    }

    $issue = $issueDao->getCurrent($contextId);

    if (!$issue) return null;

    $locales = getPreferredLocales($lang);
    $title = pickLocalizedValue($issue->getData('title'), $locales);
    $description = pickLocalizedValue($issue->getData('description'), $locales);
    $coverImage = null;
    $coverImageName = pickLocalizedValue($issue->getData('coverImage'), $locales);
    if ($coverImageName) {
        import('classes.file.PublicFileManager');
        $publicFileManager = new PublicFileManager();
        $coverImage = rtrim((string) Config::getVar('general', 'base_url'), '/') . '/' .
            $publicFileManager->getContextFilesPath($contextId) . '/' . $coverImageName;
    }

    return [
        'id' => $issue->getId(),
        'title' => $title,
        'description' => $description,
        'coverImage' => $coverImage,
        'published' => $issue->getDatePublished(),
    ];
}

/**
 * Formatear fecha según idioma
 */
function formatDateNumeric($date, $lang = 'es') {
    if (!$date) return '';

    $timestamp = is_numeric($date) ? $date : strtotime($date);

    if ($lang === 'es') {
        return date('d/m/Y', $timestamp);
    } else {
        return date('m/d/Y', $timestamp);
    }
}

/**
 * Obtener datos completos de una revista por ID
 */
function getJournalDataById($contextId, $lang = null) {
    $contextDao = Application::getContextDAO();
    $context = $contextDao->getById($contextId);

    if (!$context) {
        return null;
    }

    $locales = getPreferredLocales($lang);
    $name = pickLocalizedValue($context->getData('name'), $locales);
    $description = pickLocalizedValue($context->getData('description'), $locales);

    // Obtener portada
    $issueDao = DAORegistry::getDAO('IssueDAO');
    $currentIssue = $issueDao->getCurrent($context->getId());

    $coverImage = null;
    if ($currentIssue) {
        $coverImageName = pickLocalizedValue($currentIssue->getData('coverImage'), $locales);
        if ($coverImageName) {
            import('classes.file.PublicFileManager');
            $publicFileManager = new PublicFileManager();
            $coverImage = rtrim((string) Config::getVar('general', 'base_url'), '/') . '/' .
                $publicFileManager->getContextFilesPath($context->getId()) . '/' . $coverImageName;
        }
    }

    if (!$coverImage) {
        $thumbnailData = pickLocalizedValue($context->getData('journalThumbnail'), $locales);
        if (is_array($thumbnailData) && !empty($thumbnailData['uploadName'])) {
            import('classes.file.PublicFileManager');
            $publicFileManager = new PublicFileManager();
            $coverImage = rtrim((string) Config::getVar('general', 'base_url'), '/') . '/' .
                $publicFileManager->getContextFilesPath($context->getId()) . '/' . $thumbnailData['uploadName'];
        }
    }

    // Obtener categorías globales
    $globalCategories = getGlobalCategories();
    $globalCategoriesById = [];
    foreach ($globalCategories as $category) {
        if (isset($category['id'])) {
            $globalCategoriesById[(int) $category['id']] = $category;
        }
    }

    $contextCategoryIds = getContextGlobalCategoryIds($context->getId());
    $contextGlobalCategories = [];
    foreach ($contextCategoryIds as $categoryId) {
        $categoryId = (int) $categoryId;
        if (isset($globalCategoriesById[$categoryId])) {
            $category = $globalCategoriesById[$categoryId];
            $contextGlobalCategories[] = [
                'id' => $categoryId,
                'title' => pickLocalizedValue($category['title'] ?? '', $locales),
                'path' => $category['path'] ?? '',
            ];
        }
    }

    return [
        'id' => $context->getId(),
        'name' => $name,
        'description' => $description,
        'path' => $context->getPath(),
        'url' => buildJournalUrl($context->getPath()),
        'coverImage' => $coverImage,
        'globalCategories' => $contextGlobalCategories,
    ];
}

/**
 * Buscar revistas con filtros
 */
function searchJournals($query, $filters = [], $lang = null) {
    $contextDao = Application::getContextDAO();
    $contexts = $contextDao->getAll(true);

    $journals = [];
    $locales = getPreferredLocales($lang);
    $globalCategories = getGlobalCategories();
    $globalCategoriesById = [];

    foreach ($globalCategories as $category) {
        if (isset($category['id'])) {
            $globalCategoriesById[(int) $category['id']] = $category;
        }
    }

    while ($context = $contexts->next()) {
        $name = pickLocalizedValue($context->getData('name'), $locales);
        $description = pickLocalizedValue($context->getData('description'), $locales);

        // Aplicar filtros
        $matches = true;

        // Filtro por título/nombre
        if (!empty($filters['title']) || !empty($query)) {
            $searchTerm = !empty($filters['title']) ? $filters['title'] : $query;
            if (stripos($name, $searchTerm) === false && stripos($description, $searchTerm) === false) {
                $matches = false;
            }
        }

        // Filtro por categoría
        if ($matches && !empty($filters['category'])) {
            $contextCategoryIds = getContextGlobalCategoryIds($context->getId());
            if (!in_array((int) $filters['category'], $contextCategoryIds)) {
                $matches = false;
            }
        }

        if (!$matches) continue;

        // Obtener datos completos de la revista
        $issueDao = DAORegistry::getDAO('IssueDAO');
        $currentIssue = $issueDao->getCurrent($context->getId());

        $coverImage = null;
        if ($currentIssue) {
            $coverImageName = pickLocalizedValue($currentIssue->getData('coverImage'), $locales);
            if ($coverImageName) {
                import('classes.file.PublicFileManager');
                $publicFileManager = new PublicFileManager();
                $coverImage = rtrim((string) Config::getVar('general', 'base_url'), '/') . '/' .
                    $publicFileManager->getContextFilesPath($context->getId()) . '/' . $coverImageName;
            }
        }

        if (!$coverImage) {
            $thumbnailData = pickLocalizedValue($context->getData('journalThumbnail'), $locales);
            if (is_array($thumbnailData) && !empty($thumbnailData['uploadName'])) {
                import('classes.file.PublicFileManager');
                $publicFileManager = new PublicFileManager();
                $coverImage = rtrim((string) Config::getVar('general', 'base_url'), '/') . '/' .
                    $publicFileManager->getContextFilesPath($context->getId()) . '/' . $thumbnailData['uploadName'];
            }
        }

        // Obtener categorías globales del contexto
        $contextCategoryIds = getContextGlobalCategoryIds($context->getId());
        $contextGlobalCategories = [];
        foreach ($contextCategoryIds as $categoryId) {
            $categoryId = (int) $categoryId;
            if (isset($globalCategoriesById[$categoryId])) {
                $category = $globalCategoriesById[$categoryId];
                $contextGlobalCategories[] = [
                    'id' => $categoryId,
                    'title' => pickLocalizedValue($category['title'] ?? '', $locales),
                    'path' => $category['path'] ?? '',
                ];
            }
        }

        $journals[] = [
            'id' => $context->getId(),
            'name' => $name,
            'description' => $description,
            'path' => $context->getPath(),
            'url' => buildJournalUrl($context->getPath()),
            'coverImage' => $coverImage,
            'globalCategories' => $contextGlobalCategories,
            'lastModified' => $currentIssue ? $currentIssue->getLastModified() : $context->getData('lastModified'),
        ];
    }

    return $journals;
}

/**
 * Buscar artículos con filtros avanzados
 */
function searchArticles($query, $filters = [], $lang = null) {
    $submissionDao = DAORegistry::getDAO('SubmissionDAO');
    $contextDao = Application::getContextDAO();

    $articles = [];
    $locales = getPreferredLocales($lang);

    // Si hay filtro de revista específica, solo buscar en esa revista
    $contextsToSearch = [];
    if (!empty($filters['journal'])) {
        $context = $contextDao->getById((int) $filters['journal']);
        if ($context) {
            $contextsToSearch[] = $context;
        }
    } else if (!empty($filters['category'])) {
        // Si hay filtro de categoría, obtener revistas de esa categoría
        $allContexts = $contextDao->getAll(true);
        while ($context = $allContexts->next()) {
            $contextCategoryIds = getContextGlobalCategoryIds($context->getId());
            if (in_array((int) $filters['category'], $contextCategoryIds)) {
                $contextsToSearch[] = $context;
            }
        }
    } else {
        // Buscar en todas las revistas
        $allContexts = $contextDao->getAll(true);
        while ($context = $allContexts->next()) {
            $contextsToSearch[] = $context;
        }
    }

    foreach ($contextsToSearch as $context) {
        $submissions = $submissionDao->getByContextId($context->getId());

        while ($submission = $submissions->next()) {
            // Solo artículos publicados
            if ($submission->getData('status') != STATUS_PUBLISHED) {
                continue;
            }

            $publication = $submission->getCurrentPublication();
            if (!$publication) continue;

            $title = pickLocalizedValue($publication->getData('title'), $locales);
            $abstract = pickLocalizedValue($publication->getData('abstract'), $locales);
            $authors = $submission->getAuthorString();
            $datePublished = $publication->getData('datePublished');

            // Aplicar filtros
            $matches = true;

            // Filtro por título o query general
            if (!empty($filters['title']) || !empty($query)) {
                $searchTerm = !empty($filters['title']) ? $filters['title'] : $query;
                if (stripos($title, $searchTerm) === false && stripos($abstract, $searchTerm) === false) {
                    $matches = false;
                }
            }

            // Filtro por autor
            if ($matches && !empty($filters['author'])) {
                if (stripos($authors, $filters['author']) === false) {
                    $matches = false;
                }
            }

            // Filtro por rango de fechas
            if ($matches && !empty($datePublished)) {
                $year = (int) date('Y', strtotime($datePublished));

                if (!empty($filters['date_from']) && $year < (int) $filters['date_from']) {
                    $matches = false;
                }

                if (!empty($filters['date_to']) && $year > (int) $filters['date_to']) {
                    $matches = false;
                }
            }

            if (!$matches) continue;

            // Obtener datos de la revista padre
            $journalData = getJournalDataById($context->getId(), $lang);

            // Construir URL del artículo
            $articleUrl = buildJournalUrl($context->getPath()) . '/article/view/' . $submission->getId();

            $articles[] = [
                'id' => $submission->getId(),
                'title' => $title,
                'abstract' => $abstract,
                'authors' => $authors,
                'datePublished' => $datePublished,
                'url' => $articleUrl,
                'journal' => $journalData,
            ];
        }
    }

    return $articles;
}

/**
 * Búsqueda avanzada unificada
 */
function searchAdvanced($query, $type, $filters = [], $lang = null) {
    if ($type === 'revista') {
        return searchJournals($query, $filters, $lang);
    } else {
        return searchArticles($query, $filters, $lang);
    }
}
?>
