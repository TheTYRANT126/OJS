<?php
/**
 * Búsqueda Personalizada AMCAD
 */

require_once('includes/ojs-functions.php');
require_once('includes/translations.php');

// =============================
// Funciones Auxiliares
// =============================

/**
 * Construir URL de búsqueda con parámetros
 */
function buildSearchUrl($params) {
    return 'busqueda.php?' . http_build_query($params);
}

/**
 * Remover un filtro específico de la URL
 */
function removeFilterUrl($filterName) {
    $params = $_GET;
    unset($params[$filterName]);
    return buildSearchUrl($params);
}

/**
 * Extraer categorías únicas de resultados
 */
function extractCategoriesFromResults($results, $searchType) {
    $categories = [];
    $seen = [];

    foreach ($results as $result) {
        $cats = [];
        if ($searchType === 'revista' && isset($result['globalCategories'])) {
            $cats = $result['globalCategories'];
        } else if ($searchType === 'articulo' && isset($result['journal']['globalCategories'])) {
            $cats = $result['journal']['globalCategories'];
        }

        foreach ($cats as $cat) {
            if (!in_array($cat['id'], $seen)) {
                $categories[] = $cat;
                $seen[] = $cat['id'];
            }
        }
    }

    return $categories;
}

// =============================
// Procesamiento de Parámetros
// =============================

// Detectar idioma
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['amcad_lang']) ? $_COOKIE['amcad_lang'] : 'es');
if (!in_array($lang, ['es', 'en'])) $lang = 'es';

// Determinar si es búsqueda simple o avanzada
$isAdvanced = isset($_GET['advanced']) && $_GET['advanced'] == '1';

// Obtener tipo de búsqueda (revista o artículo)
$searchType = isset($_GET['type']) ? $_GET['type'] : 'revista';
if (!in_array($searchType, ['revista', 'articulo'])) $searchType = 'revista';

// Parámetros de paginación
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Construir filtros según tipo de búsqueda
$filters = [];
$query = '';

if ($isAdvanced) {
    // Búsqueda avanzada
    $filters['author'] = isset($_GET['author']) ? trim($_GET['author']) : null;
    $filters['title'] = isset($_GET['title']) ? trim($_GET['title']) : null;
    $filters['journal'] = isset($_GET['journal']) && $_GET['journal'] !== '' ? (int)$_GET['journal'] : null;
    $filters['category'] = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
    $filters['date_from'] = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? (int)$_GET['date_from'] : null;
    $filters['date_to'] = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? (int)$_GET['date_to'] : null;

    $query = '';
} else {
    // Búsqueda simple
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
}

// Ejecutar búsqueda
$allResults = [];
if ($isAdvanced || $query !== '') {
    $allResults = searchAdvanced($query, $searchType, $filters, $lang);
}

// Paginación
$totalResults = count($allResults);
$totalPages = ceil($totalResults / $perPage);
$results = array_slice($allResults, $offset, $perPage);

// Obtener datos auxiliares para filtros
$allJournals = getAllJournals(null, 0, $lang);
$categories = getAllCategories($lang);

// Configuración del usuario y URL base
$baseUrl = rtrim((string) Config::getVar('general', 'base_url'), '/');
$sessionManager = SessionManager::getManager();
$session = $sessionManager->getUserSession();
$currentUser = $session ? $session->getUser() : null;
$isLoggedIn = Validation::isLoggedIn() && $currentUser;
$userDisplayName = '';
if ($isLoggedIn) {
    $userDisplayName = $currentUser->getUsername();
}

$pathInfoEnabled = Config::getVar('general', 'disable_path_info') ? false : true;
$application = Application::get();
$contextList = $application ? $application->getContextList() : [];
$contextKey = $contextList[0] ?? 'journal';
$siteContext = 'index';

if ($pathInfoEnabled) {
    $userTargetUrl = $baseUrl . '/' . $siteContext . '/admin';
    $profileUrl = $baseUrl . '/' . $siteContext . '/user/profile';
    $logoutUrl = $baseUrl . '/' . $siteContext . '/login/signOut';
} else {
    $userTargetUrl = $baseUrl . '/index.php?' . $contextKey . '=' . $siteContext . '&page=admin';
    $profileUrl = $baseUrl . '/index.php?' . $contextKey . '=' . $siteContext . '&page=user&op=profile';
    $logoutUrl = $baseUrl . '/index.php?' . $contextKey . '=' . $siteContext . '&page=login&op=signOut';
}

$pageTitle = "Búsqueda - Portal AMCAD";
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

    <!-- Banner Superior -->
    <div class="top-banner">
        <div class="container">
            <div class="banner-content">
                <div class="banner-logo-wrapper">
                    <img src="assets/images/logo_acamed.png" alt="AMCAD Logo" class="banner-logo">
                </div>
                <div class="banner-text">
                    <h1>Asociación Mexicana de Cirugía del Aparato Digestivo, A.C.</h1>
                    <p class="slogan">POR LA EXCELENCIA EN LA CIRUGÍA DEL APARATO DIGESTIVO</p>
                </div>
                <div class="banner-right">
                    <div class="social-icons">
                        <a href="https://www.facebook.com/profile.php?id=100075738705913" target="_blank" rel="noopener">
                            <img src="assets/images/facebook_icon.png" alt="Facebook">
                        </a>
                        <a href="https://www.instagram.com/amcad.a.c/" target="_blank" rel="noopener">
                            <img src="assets/images/instagram_icon.png" alt="Instagram">
                        </a>
                        <a href="mailto:amcadac@gmail.com">
                            <img src="assets/images/correo_icon.png" alt="Email">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Sticky -->
    <header class="main-header" id="mainHeader">
        <div class="container">
            <div class="header-content">
                <div class="header-top-row">
                    <a href="index.php" class="header-logo-link">
                        <div class="header-logo">
                            <img src="assets/images/AMCAD_logo.png" alt="AMCAD">
                        </div>
                    </a>
                    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mainNav">
                        <span class="menu-toggle-bar"></span>
                        <span class="menu-toggle-bar"></span>
                        <span class="menu-toggle-bar"></span>
                    </button>
                </div>

                <nav class="main-nav" id="mainNav">
                    <ul>
                        <li><a href="index.php"><?php echo t('inicio', $lang); ?></a></li>
                        <li><a href="lineamientos.php"><?php echo t('lineamientos', $lang); ?></a></li>
                        <li><a href="recursos.php"><?php echo t('recursos', $lang); ?></a></li>
                        <li><a href="contacto.php"><?php echo t('contacto', $lang); ?></a></li>
                        <?php if ($isLoggedIn): ?>
                            <li class="header-user-menu">
                                <button
                                    type="button"
                                    class="user-menu-trigger"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                >
                                    <span
                                        class="user-name-link"
                                        data-dashboard-url="<?php echo htmlspecialchars($userTargetUrl); ?>"
                                    >
                                        <?php echo htmlspecialchars($userDisplayName); ?>
                                    </span>
                                    <span class="user-menu-icon" aria-hidden="true">&#9662;</span>
                                </button>
                                <ul class="user-menu-dropdown">
                                    <li>
                                        <a href="<?php echo htmlspecialchars($profileUrl); ?>">
                                            <?php echo t('login_profile', $lang); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($logoutUrl); ?>">
                                            <?php echo t('login_sign_out', $lang); ?>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li><a href="login.php"><?php echo t('acceso', $lang); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div class="lang-selector">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['lang' => $lang === 'es' ? 'en' : 'es'])); ?>" class="lang-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                        <?php echo strtoupper($lang); ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Sección de Resumen de Búsqueda -->
    <section class="search-summary">
        <div class="container">
            <h1><?php echo t('search_results', $lang); ?></h1>
            <div class="search-meta">
                <span class="search-type-badge">
                    <?php echo t('search_' . $searchType, $lang); ?>
                </span>
                <?php if ($query): ?>
                    <span class="search-query">"<?php echo htmlspecialchars($query); ?>"</span>
                <?php endif; ?>
                <span class="results-count">
                    <?php echo $totalResults; ?> <?php echo t('results_found', $lang); ?>
                </span>
            </div>

            <!-- Filtros activos (pills removibles) -->
            <?php if ($isAdvanced && ($query || $filters['author'] || $filters['title'] || $filters['journal'] || $filters['category'] || $filters['date_from'] || $filters['date_to'])): ?>
                <div class="active-filters">
                    <?php if ($query): ?>
                        <span class="filter-pill">
                            <?php echo t('search_text', $lang); ?> <?php echo htmlspecialchars($query); ?>
                            <a href="<?php echo removeFilterUrl('query'); ?>">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($filters['author']): ?>
                        <span class="filter-pill">
                            <?php echo t('author', $lang); ?> <?php echo htmlspecialchars($filters['author']); ?>
                            <a href="<?php echo removeFilterUrl('author'); ?>">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($filters['title']): ?>
                        <span class="filter-pill">
                            <?php echo t('title', $lang); ?> <?php echo htmlspecialchars($filters['title']); ?>
                            <a href="<?php echo removeFilterUrl('title'); ?>">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($filters['category']): ?>
                        <?php
                        $categoryName = '';
                        foreach ($categories as $cat) {
                            if ($cat['id'] == $filters['category']) {
                                $categoryName = $cat['name'];
                                break;
                            }
                        }
                        ?>
                        <span class="filter-pill">
                            <?php echo t('category', $lang); ?> <?php echo htmlspecialchars($categoryName); ?>
                            <a href="<?php echo removeFilterUrl('category'); ?>">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($filters['date_from'] || $filters['date_to']): ?>
                        <span class="filter-pill">
                            <?php echo t('publication_period', $lang); ?>
                            <?php echo $filters['date_from'] ?? '...'; ?> - <?php echo $filters['date_to'] ?? '...'; ?>
                            <a href="<?php echo removeFilterUrl('date_from'); ?>">×</a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Contenedor Principal de Resultados -->
    <section class="search-results-section">
        <div class="container">
            <div class="search-results-container">

                <!-- Columna de filtros -->
                <div class="filters-column">
                    <!-- Botón de regresar -->
                    <a href="index.php?lang=<?php echo $lang; ?>" class="btn-back">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 13L5 8L10 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php echo $lang === 'es' ? 'Regresar' : 'Back'; ?>
                    </a>

                    <!-- Panel de Filtros Laterales -->
                    <aside class="search-filters">
                        <h3><?php echo t('refine_search', $lang); ?></h3>

                    <form method="GET" id="refineForm">
                        <input type="hidden" name="lang" value="<?php echo $lang; ?>">
                        <input type="hidden" name="advanced" value="1">
                        <input type="hidden" name="type" value="<?php echo $searchType; ?>">

                        <!-- Texto de búsqueda general -->
                        <div class="filter-section">
                            <label><?php echo t('search_text', $lang); ?></label>
                            <input type="text" name="query" value="<?php echo htmlspecialchars($query ?? ''); ?>" placeholder="<?php echo t('search_button', $lang); ?>...">
                        </div>

                        <!-- Periodo de publicación -->
                        <div class="filter-section">
                            <label><?php echo t('publication_period', $lang); ?></label>
                            <div class="date-range">
                                <input type="text" name="date_from" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>" placeholder="<?php echo t('from', $lang); ?> (<?php echo t('year_placeholder', $lang); ?>)" maxlength="4" inputmode="numeric">
                                <input type="text" name="date_to" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>" placeholder="<?php echo t('to', $lang); ?> (<?php echo t('year_placeholder', $lang); ?>)" maxlength="4" inputmode="numeric">
                            </div>
                        </div>

                        <!-- Autor -->
                        <div class="filter-section">
                            <label><?php echo t('author', $lang); ?></label>
                            <input type="text" name="author" value="<?php echo htmlspecialchars($filters['author'] ?? ''); ?>" placeholder="<?php echo t('name_placeholder', $lang); ?>">
                        </div>

                        <!-- Título -->
                        <div class="filter-section">
                            <label><?php echo t('title', $lang); ?></label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($filters['title'] ?? ''); ?>" placeholder="<?php echo t('name_placeholder', $lang); ?>">
                        </div>

                        <!-- En la revista -->
                        <div class="filter-section">
                            <label><?php echo t('in_journal', $lang); ?></label>
                            <select name="journal">
                                <option value=""><?php echo t('select_journal', $lang); ?></option>
                                <?php foreach ($allJournals as $journal): ?>
                                    <option value="<?php echo $journal['id']; ?>" <?php echo (isset($filters['journal']) && $filters['journal'] == $journal['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($journal['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Categoría -->
                        <div class="filter-section">
                            <label><?php echo t('category', $lang); ?></label>
                            <select name="category">
                                <option value=""><?php echo t('select_category', $lang); ?></option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo (isset($filters['category']) && $filters['category'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn-filter">
                            <?php echo t('apply_filters', $lang); ?>
                        </button>
                    </form>
                    </aside>
                </div>

                <!-- Tabla de Resultados -->
                <div class="results-main">
                    <?php if ($totalResults > 0): ?>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th><?php echo t('cover', $lang); ?></th>
                                    <?php if ($searchType === 'revista'): ?>
                                        <th><?php echo t('journal_name', $lang); ?></th>
                                        <th><?php echo t('categories', $lang); ?></th>
                                    <?php else: ?>
                                        <th><?php echo t('article_title', $lang); ?></th>
                                        <th><?php echo t('author', $lang); ?></th>
                                        <th><?php echo t('journal', $lang); ?></th>
                                        <th><?php echo t('categories', $lang); ?></th>
                                    <?php endif; ?>
                                    <th><?php echo t('actions', $lang); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <?php if ($searchType === 'revista'): ?>
                                        <!-- Fila para Revista -->
                                        <tr>
                                            <td class="cover-cell">
                                                <?php if ($result['coverImage']): ?>
                                                    <img src="<?php echo htmlspecialchars($result['coverImage']); ?>"
                                                         alt="<?php echo htmlspecialchars($result['name']); ?>"
                                                         class="table-cover">
                                                <?php else: ?>
                                                    <div class="table-cover-placeholder">
                                                        <?php echo strtoupper(substr($result['name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="title-cell">
                                                <strong><?php echo htmlspecialchars($result['name']); ?></strong>
                                            </td>
                                            <td class="category-cell">
                                                <?php if (!empty($result['globalCategories'])): ?>
                                                    <div class="category-tags">
                                                        <?php foreach ($result['globalCategories'] as $cat): ?>
                                                            <span class="category-tag"><?php echo htmlspecialchars($cat['title']); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="actions-cell">
                                                <button class="btn-table-action btn-primary" onclick="window.location.href='<?php echo rtrim($result['url'], '/'); ?>/about'">
                                                    <?php echo t('view_journal', $lang); ?>
                                                </button>
                                                <button class="btn-table-action btn-secondary" onclick="window.location.href='<?php echo $result['url']; ?>/issue/current'">
                                                    <?php echo t('latest_publication', $lang); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <!-- Fila para Artículo -->
                                        <tr>
                                            <td class="cover-cell">
                                                <?php if ($result['journal']['coverImage']): ?>
                                                    <img src="<?php echo htmlspecialchars($result['journal']['coverImage']); ?>"
                                                         alt="<?php echo htmlspecialchars($result['journal']['name']); ?>"
                                                         class="table-cover">
                                                <?php else: ?>
                                                    <div class="table-cover-placeholder">
                                                        <?php echo strtoupper(substr($result['journal']['name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="title-cell">
                                                <strong><?php echo htmlspecialchars($result['title']); ?></strong>
                                            </td>
                                            <td class="author-cell">
                                                <?php echo htmlspecialchars($result['authors']); ?>
                                            </td>
                                            <td class="journal-cell">
                                                <?php echo htmlspecialchars($result['journal']['name']); ?>
                                            </td>
                                            <td class="category-cell">
                                                <?php if (!empty($result['journal']['globalCategories'])): ?>
                                                    <div class="category-tags">
                                                        <?php foreach ($result['journal']['globalCategories'] as $cat): ?>
                                                            <span class="category-tag"><?php echo htmlspecialchars($cat['title']); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="actions-cell">
                                                <button class="btn-table-action btn-primary" onclick="window.location.href='<?php echo $result['url']; ?>'">
                                                    <?php echo t('view_article', $lang); ?>
                                                </button>
                                                <button class="btn-table-action btn-secondary" onclick="window.location.href='<?php echo rtrim($result['journal']['url'], '/'); ?>/about'">
                                                    <?php echo t('view_journal', $lang); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Paginación -->
                        <?php if ($totalPages > 1): ?>
                            <nav class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo buildSearchUrl(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
                                        « <?php echo t('previous', $lang); ?>
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="<?php echo buildSearchUrl(array_merge($_GET, ['page' => $i])); ?>"
                                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="<?php echo buildSearchUrl(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
                                        <?php echo t('next', $lang); ?> »
                                    </a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Sin resultados -->
                        <div class="no-results">
                            <h2><?php echo t('no_results', $lang); ?></h2>
                            <p><?php echo t('no_results_message', $lang); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <img src="assets/images/ojs_pkp_logo.png" alt="OJS/PKP" class="footer-logo">
                </div>
                <div class="footer-center">
                    <p><?php echo t('copyright', $lang); ?></p>
                    <p><?php echo t('rights_reserved', $lang); ?></p>
                    <p>amcadac@gmail.com</p>
                    <p class="dev-credit is-hidden">Desarrollado por Emmanuel Velásquez Ortiz 😉</p>
                </div>
                <div class="footer-right">
                    <a href="#about"><?php echo t('about_system', $lang); ?></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
