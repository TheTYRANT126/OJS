<?php
/**
 * Portal AMCAD - Página de Lineamientos
 * Asociación Mexicana de Cirugía del Aparato Digestivo, A.C.
 */

require_once('includes/admin-functions.php');
require_once('includes/translations.php');

// Detectar idioma
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['amcad_lang']) ? $_COOKIE['amcad_lang'] : 'es');
if (!in_array($lang, ['es', 'en'])) $lang = 'es';

setcookie('amcad_lang', $lang, time() + (86400 * 30), '/');

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

// Obtener lineamientos
$lineamientos = getLineamientos(true);

$pageTitle = "Portal AMCAD - " . t('lineamientos', $lang);
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
                        <li class="active"><a href="lineamientos.php"><?php echo t('lineamientos', $lang); ?></a></li>
                        <li><a href="recursos.php"><?php echo t('recursos', $lang); ?></a></li>
                        <li><a href="contacto.php"><?php echo t('contacto', $lang); ?></a></li>
                        <?php if ($isLoggedIn): ?>
                            <li class="header-user-menu">
                                <button type="button" class="user-menu-trigger" aria-haspopup="true" aria-expanded="false">
                                    <span class="user-name-link" data-dashboard-url="<?php echo htmlspecialchars($userTargetUrl); ?>">
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

    <!-- Sección de Lineamientos -->
    <section class="content-section">
        <div class="container">
            <h1 class="page-title"><?php echo t('lineamientos', $lang); ?></h1>

                        <div class="resources-grid">
                <?php foreach ($lineamientos as $item): ?>
                    <?php
                        $itemTitle = getLocalizedField($item, 'titulo', $lang);
                        $itemDescription = getLocalizedField($item, 'descripcion', $lang);
                        $itemFile = getLocalizedField($item, 'archivo', $lang);
                    ?>
                    <article class="resource-card">
                        <?php if ($item['fijado']): ?>
                            <div class="resource-pinned">
                                <span><?php echo $lang === 'es' ? 'Destacado' : 'Featured'; ?></span>
                            </div>
                        <?php endif; ?>

                        <h2 class="resource-title"><?php echo htmlspecialchars($itemTitle); ?></h2>

                        <?php if ($itemDescription): ?>
                            <p class="resource-description"><?php echo nl2br(htmlspecialchars($itemDescription)); ?></p>
                        <?php endif; ?>

                        <div class="resource-footer">
                            <div class="resource-meta">
                                <?php if ($item['autor']): ?>
                                    <p class="resource-author">
                                        <strong><?php echo $lang === 'es' ? 'Autor:' : 'Author:'; ?></strong>
                                        <?php echo htmlspecialchars($item['autor']); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="resource-date">
                                    <strong><?php echo $lang === 'es' ? 'Fecha:' : 'Date:'; ?></strong>
                                    <?php echo date('d/m/Y', strtotime($item['fecha_subida'])); ?>
                                </p>
                            </div>

                            <a href="uploads/<?php echo htmlspecialchars($itemFile); ?>"
                               class="btn-download"
                               target="_blank"
                               download>
                                <?php echo $lang === 'es' ? 'Descargar' : 'Download'; ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (empty($lineamientos)): ?>
                <div class="empty-state">
                    <p><?php echo $lang === 'es' ? 'No hay lineamientos disponibles en este momento.' : 'No guidelines available at this time.'; ?></p>
                </div>
            <?php endif; ?>
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
                    <p class="dev-credit is-hidden">Desarrollado por Emmanuel Velásquez Ortiz</p>
                </div>
                <div class="footer-right">
                    <?php if (isOJSAdmin()): ?>
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
