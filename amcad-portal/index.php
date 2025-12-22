<?php
/**
 * Portal AMCAD - Página Principal
 * Asociación Mexicana de Cirugía del Aparato Digestivo, A.C.
 */

// Cargar funciones de OJS y traducciones
require_once('includes/ojs-functions.php');
require_once('includes/translations.php');

// Detectar idioma (por defecto español)
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['amcad_lang']) ? $_COOKIE['amcad_lang'] : 'es');
if (!in_array($lang, ['es', 'en'])) $lang = 'es';

// Guardar idioma en cookie
setcookie('amcad_lang', $lang, time() + (86400 * 30), '/');

// Obtener datos de OJS
try {
    $totalCounts = getTotalCounts();
    $recentJournals = getRecentJournals(3, $lang);
    $allJournals = getAllJournals(null, 0, $lang);
    $categories = getAllCategories($lang);
} catch (Exception $e) {
    // En caso de error, usar datos vacíos
    $totalCounts = ['journals' => 0, 'articles' => 0];
    $recentJournals = [];
    $allJournals = [];
    $categories = [];
}

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

$pageTitle = "Portal AMCAD - " . t('inicio', $lang);
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
                <!-- Logo AMCAD izquierda -->
                <div class="banner-logo-wrapper">
                    <img src="assets/images/logo_acamed.png" alt="AMCAD Logo" class="banner-logo">
                </div>

                <!-- Texto central -->
                <div class="banner-text">
                    <h1>Asociación Mexicana de Cirugía del Aparato Digestivo, A.C.</h1>
                    <p class="slogan">POR LA EXCELENCIA EN LA CIRUGÍA DEL APARATO DIGESTIVO</p>
                </div>

                <!-- Columna derecha: iconos de redes sociales -->
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
                        <li class="active"><a href="index.php"><?php echo t('inicio', $lang); ?></a></li>
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
                    <a href="?lang=<?php echo $lang === 'es' ? 'en' : 'es'; ?>" class="lang-link">
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

    <!-- Sección de Búsqueda -->
    <section class="search-section">
        <div class="container">
            <!-- Fila superior: Botones y Contador -->
            <div class="search-top-row">
                <div class="search-left">
                    <div class="search-label"><?php echo t('search_by', $lang); ?></div>
                    <div class="search-type-selector">
                        <button class="search-type-btn active" data-type="revista">
                            <?php echo t('search_revista', $lang); ?>
                        </button>
                        <button class="search-type-btn" data-type="articulo">
                            <?php echo t('search_articulo', $lang); ?>
                        </button>
                    </div>
                </div>

                <div class="total-count">
                    <?php echo $totalCounts['journals']; ?> <?php echo t('search_revista', $lang); ?>s,
                    <?php echo $totalCounts['articles']; ?> <?php echo t('search_articulo', $lang); ?>s
                </div>
            </div>

            <!-- Barra de Búsqueda -->
            <form action="busqueda.php" method="GET" class="search-form" id="searchForm">
                <input type="hidden" name="type" id="searchType" value="revista">
                <input type="hidden" name="lang" value="<?php echo $lang; ?>">
                <input
                    type="text"
                    name="q"
                    class="search-input"
                    placeholder=""
                >
                <button type="submit" class="search-btn">
                    <?php echo t('search_button', $lang); ?>
                </button>
            </form>

            <!-- Enlace Búsqueda Avanzada -->
            <div class="advanced-search-toggle">
                <a href="#" id="advancedSearchToggle"><?php echo t('advanced_search', $lang); ?></a>
            </div>

            <!-- Formulario de Búsqueda Avanzada (oculto por defecto) -->
            <div class="advanced-search-form" id="advancedSearchForm" style="display: none;">
                <form action="busqueda.php" method="GET">
                    <input type="hidden" name="advanced" value="1">
                    <input type="hidden" name="lang" value="<?php echo $lang; ?>">

                    <div class="advanced-search-grid">
                        <!-- Periodo de publicación -->
                        <div class="search-field-group">
                            <label><?php echo t('publication_period', $lang); ?></label>
                            <div class="date-range">
                                <div class="date-input">
                                    <label><?php echo t('from', $lang); ?></label>
                                    <input type="text" name="date_from" placeholder="<?php echo t('year_placeholder', $lang); ?>" inputmode="numeric" pattern="\\d{4}" maxlength="4">
                                </div>
                                <div class="date-input">
                                    <label><?php echo t('to', $lang); ?></label>
                                    <input type="text" name="date_to" placeholder="<?php echo t('year_placeholder', $lang); ?>" inputmode="numeric" pattern="\\d{4}" maxlength="4">
                                </div>
                            </div>
                        </div>

                        <!-- Autor -->
                        <div class="search-field">
                            <label><?php echo t('author', $lang); ?></label>
                            <input type="text" name="author" placeholder="<?php echo t('name_placeholder', $lang); ?>">
                        </div>

                        <!-- Título -->
                        <div class="search-field">
                            <label><?php echo t('title', $lang); ?></label>
                            <input type="text" name="title" placeholder="<?php echo t('name_placeholder', $lang); ?>">
                        </div>

                        <!-- En la revista -->
                        <div class="search-field">
                            <label><?php echo t('in_journal', $lang); ?></label>
                            <select name="journal">
                                <option value=""><?php echo t('select_journal', $lang); ?></option>
                                <?php foreach ($allJournals as $journal): ?>
                                    <option value="<?php echo $journal['id']; ?>">
                                        <?php echo htmlspecialchars($journal['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Categoría -->
                        <div class="search-field">
                            <label><?php echo t('category', $lang); ?></label>
                            <select name="category">
                                <option value=""><?php echo t('select_category', $lang); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="advanced-search-submit">
                        <button type="submit" class="search-btn">
                            <?php echo t('search_button', $lang); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Añadidos Recientes -->
    <section class="recent-journals">
        <div class="container">
            <h2><?php echo t('recent_additions', $lang); ?></h2>
            <div class="journals-grid">
                <?php foreach ($recentJournals as $journal): ?>
                    <div class="journal-card">
                        <div class="journal-cover">
                            <?php if ($journal['coverImage']): ?>
                                <img src="<?php echo htmlspecialchars($journal['coverImage']); ?>"
                                     alt="<?php echo htmlspecialchars($journal['name']); ?>">
                            <?php else: ?>
                                <div class="journal-cover-placeholder">
                                    <?php echo strtoupper(substr($journal['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="journal-info">
                            <h3><?php echo htmlspecialchars($journal['name']); ?></h3>
                            <?php if (!empty($journal['globalCategories'])): ?>
                                <?php
                                    $categoryTitles = [];
                                    foreach ($journal['globalCategories'] as $category) {
                                        if (!empty($category['title'])) {
                                            $categoryTitles[] = $category['title'];
                                        }
                                    }
                                    $visibleCategories = array_slice($categoryTitles, 0, 2);
                                    $hasMoreCategories = count($categoryTitles) > 2;
                                ?>
                                <ul class="journal-tags">
                                    <?php foreach ($visibleCategories as $categoryTitle): ?>
                                        <li><?php echo htmlspecialchars($categoryTitle); ?></li>
                                    <?php endforeach; ?>
                                    <?php if ($hasMoreCategories): ?>
                                        <li class="journal-tags-more" data-tooltip="<?php echo htmlspecialchars(implode(', ', $categoryTitles)); ?>">+</li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>
                            <button class="btn-view-journal" onclick="window.location.href='<?php echo rtrim($journal['url'], '/'); ?>/about'">
                                <?php echo t('view_journal', $lang); ?>
                            </button>
                            <button class="btn-latest-pub" onclick="window.location.href='<?php echo $journal['url']; ?>/issue/current'">
                                <?php echo t('latest_publication', $lang); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Todas las Revistas -->
    <section class="all-journals">
        <div class="container">
            <!-- Filtro por Categoría -->
            <div class="category-filter">
                <label><?php echo t('category', $lang); ?></label>
                <select id="categoryFilter">
                    <option value=""><?php echo t('all_categories', $lang); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Grid de todas las revistas -->
            <div class="journals-grid" id="allJournalsGrid">
                <?php foreach ($allJournals as $journal): ?>
                    <?php
                        $categoryIds = [];
                        if (!empty($journal['globalCategories'])) {
                            foreach ($journal['globalCategories'] as $category) {
                                if (isset($category['id'])) {
                                    $categoryIds[] = (int) $category['id'];
                                }
                            }
                        }
                        $categoryData = implode(',', $categoryIds);
                    ?>
                    <div class="journal-card" data-category="<?php echo htmlspecialchars($categoryData); ?>">
                        <div class="journal-cover">
                            <?php if ($journal['coverImage']): ?>
                                <img src="<?php echo htmlspecialchars($journal['coverImage']); ?>"
                                     alt="<?php echo htmlspecialchars($journal['name']); ?>">
                            <?php else: ?>
                                <div class="journal-cover-placeholder">
                                    <?php echo strtoupper(substr($journal['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="journal-info">
                            <h3><?php echo htmlspecialchars($journal['name']); ?></h3>
                            <?php if (!empty($journal['globalCategories'])): ?>
                                <?php
                                    $categoryTitles = [];
                                    foreach ($journal['globalCategories'] as $category) {
                                        if (!empty($category['title'])) {
                                            $categoryTitles[] = $category['title'];
                                        }
                                    }
                                    $visibleCategories = array_slice($categoryTitles, 0, 2);
                                    $hasMoreCategories = count($categoryTitles) > 2;
                                ?>
                                <ul class="journal-tags">
                                    <?php foreach ($visibleCategories as $categoryTitle): ?>
                                        <li><?php echo htmlspecialchars($categoryTitle); ?></li>
                                    <?php endforeach; ?>
                                    <?php if ($hasMoreCategories): ?>
                                        <li class="journal-tags-more" data-tooltip="<?php echo htmlspecialchars(implode(', ', $categoryTitles)); ?>">+</li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>
                            <button class="btn-view-journal" onclick="window.location.href='<?php echo rtrim($journal['url'], '/'); ?>/about'">
                                <?php echo t('view_journal', $lang); ?>
                            </button>
                            <button class="btn-latest-pub" onclick="window.location.href='<?php echo $journal['url']; ?>/issue/current'">
                                <?php echo t('latest_publication', $lang); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
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
                    <?php
                    // Verificar si es admin para mostrar enlace al panel
                    $showAdminLink = false;
                    try {
                        require_once('includes/admin-functions.php');
                        $showAdminLink = isOJSAdmin();
                    } catch (Exception $e) {
                        // Si hay error, mostrar link normal
                    }

                    if ($showAdminLink): ?>
                        <a href="admin/index.php"><?php echo t('about_system', $lang); ?></a>
                    <?php else: ?>
                        <a href="#about"><?php echo t('about_system', $lang); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
