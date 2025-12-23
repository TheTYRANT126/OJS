<?php
/**
 * Portal AMCAD - Página de Login
 * Asociación Mexicana de Cirugía del Aparato Digestivo, A.C.
 *
 * Esta página intercepta las peticiones de login tanto globales (/index/login)
 * como de revistas específicas (/revista/login) y muestra una interfaz personalizada
 * mientras mantiene la funcionalidad completa de OJS
 */

// Cargar funciones de OJS y traducciones
require_once('includes/ojs-functions.php');
require_once('includes/translations.php');

// Detectar idioma (por defecto español)
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['amcad_lang']) ? $_COOKIE['amcad_lang'] : 'es');
if (!in_array($lang, ['es', 'en'])) $lang = 'es';

// Guardar idioma en cookie
setcookie('amcad_lang', $lang, time() + (86400 * 30), '/');

// Determinar el contexto (revista) desde la URL o parámetro GET
$journalPath = null;
$baseUrl = rtrim((string) Config::getVar('general', 'base_url'), '/');
$pathInfoEnabled = Config::getVar('general', 'disable_path_info') ? false : true;
$contextKey = null;
$application = Application::get();
if ($application) {
    $contextList = $application->getContextList();
    $contextKey = $contextList[0] ?? null;
}
if (!$contextKey) {
    $contextKey = 'journal';
}
$basePath = rtrim((string) parse_url($baseUrl, PHP_URL_PATH), '/');
if ($basePath === '') {
    $basePath = '';
}
$baseRoot = $baseUrl;
$baseUrlParts = parse_url($baseUrl);
if (!empty($baseUrlParts['scheme']) && !empty($baseUrlParts['host'])) {
    $baseRoot = $baseUrlParts['scheme'] . '://' . $baseUrlParts['host'];
    if (!empty($baseUrlParts['port'])) {
        $baseRoot .= ':' . $baseUrlParts['port'];
    }
}

// Primero intentar obtener el contexto desde el parámetro GET
if (isset($_GET['context']) && !empty($_GET['context']) && $_GET['context'] !== 'site' && $_GET['context'] !== 'index') {
    $journalPath = $_GET['context'];
} else {
    // Si no hay parámetro, intentar detectar desde la URL
    $requestUri = $_SERVER['REQUEST_URI'];
    $basePath = parse_url($baseUrl, PHP_URL_PATH);
    if ($basePath) {
        $requestUri = str_replace($basePath, '', $requestUri);
    }
    $requestUri = ltrim($requestUri, '/');
    $pathParts = explode('/', $requestUri);

    $reservedSitePaths = ['index', 'site', 'admin', 'login', 'user', 'api', 'stats', 'about', 'search', 'sitemap', 'amcad-portal'];
    // Si el primer segmento no es un path reservado y no está vacío, es una revista
    if (!empty($pathParts[0]) && !in_array($pathParts[0], $reservedSitePaths, true)) {
        $journalPath = $pathParts[0];
    }
}

// Variables para el formulario
$loginMessage = isset($_GET['loginMessage']) ? $_GET['loginMessage'] : '';
$username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : '';
$source = isset($_GET['source']) ? htmlspecialchars($_GET['source']) : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
$reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';

// Si no se detectó contexto, intentar inferirlo desde "source"
if (!$journalPath && $source !== '' && strpos($source, '/') === 0) {
    $sourcePath = $source;
    if ($basePath !== '' && strpos($sourcePath, $basePath . '/') === 0) {
        $sourcePath = substr($sourcePath, strlen($basePath));
    }
    $sourceParts = explode('/', ltrim($sourcePath, '/'));
    if (!empty($sourceParts[0]) && !in_array($sourceParts[0], $reservedSitePaths ?? ['index', 'site', 'admin', 'login', 'user', 'api', 'stats', 'about', 'search', 'sitemap', 'amcad-portal'], true)) {
        $journalPath = $sourceParts[0];
    }
}

// Si no hay "source" y se detectó una revista, enviar al listado de envíos
if ($journalPath && $source === '') {
    if ($pathInfoEnabled) {
        $source = $basePath . '/' . $journalPath . '/submissions';
    } else {
        $source = $basePath . '/index.php?' . $contextKey . '=' . rawurlencode($journalPath) . '&page=submissions';
    }
}
if (!$journalPath && $source === '') {
    if ($pathInfoEnabled) {
        $source = $basePath . '/index/admin';
    } else {
        $source = $basePath . '/index.php?' . $contextKey . '=index&page=admin';
    }
}

$selectedContext = '';
if (isset($_GET['context']) && ($_GET['context'] === 'site' || $_GET['context'] === 'index')) {
    $selectedContext = 'site';
} elseif ($journalPath) {
    $selectedContext = $journalPath;
}

$sessionManager = SessionManager::getManager();
$session = $sessionManager->getUserSession();
$currentUser = $session ? $session->getUser() : null;
$isLoggedIn = Validation::isLoggedIn() && $currentUser;
$userDisplayName = '';
if ($isLoggedIn) {
    $userDisplayName = $currentUser->getUsername();
}
$userTargetUrl = $baseUrl . '/index.php';
if ($journalPath) {
    if ($pathInfoEnabled) {
        $userTargetUrl = $baseUrl . '/' . $journalPath . '/submissions';
    } else {
        $userTargetUrl = $baseUrl . '/index.php?' . $contextKey . '=' . rawurlencode($journalPath) . '&page=submissions';
    }
} else {
    if ($pathInfoEnabled) {
        $userTargetUrl = $baseUrl . '/index/admin';
    } else {
        $userTargetUrl = $baseUrl . '/index.php?' . $contextKey . '=index&page=admin';
    }
}
if ($source) {
    if (preg_match('#^https?://#i', $source)) {
        $userTargetUrl = $source;
    } elseif (strpos($source, '/') === 0) {
        $userTargetUrl = $baseRoot . $source;
    } else {
        $userTargetUrl = $baseUrl . '/' . ltrim($source, '/');
    }
}

if ($isLoggedIn) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Location: ' . $userTargetUrl);
    exit;
}

// Construir la URL de acción del formulario
if ($journalPath) {
    if ($pathInfoEnabled) {
        $loginUrl = $baseUrl . '/' . $journalPath . '/login/signIn';
    } else {
        $loginUrl = $baseUrl . '/index.php?' . $contextKey . '=' . rawurlencode($journalPath) . '&page=login&op=signIn';
    }
} else {
    if ($pathInfoEnabled) {
        $loginUrl = $baseUrl . '/index/login/signIn';
    } else {
        $loginUrl = $baseUrl . '/index.php?' . $contextKey . '=index&page=login&op=signIn';
    }
}

// Construir URLs para enlaces
if ($journalPath) {
    if ($pathInfoEnabled) {
        $forgotPasswordUrl = $baseUrl . '/' . $journalPath . '/login/lostPassword';
        $registerUrl = $baseUrl . '/' . $journalPath . '/user/register';
    } else {
        $forgotPasswordUrl = $baseUrl . '/index.php?' . $contextKey . '=' . rawurlencode($journalPath) . '&page=login&op=lostPassword';
        $registerUrl = $baseUrl . '/index.php?' . $contextKey . '=' . rawurlencode($journalPath) . '&page=user&op=register';
    }
} else {
    if ($pathInfoEnabled) {
        $forgotPasswordUrl = $baseUrl . '/index/login/lostPassword';
        $registerUrl = $baseUrl . '/index/user/register';
    } else {
        $forgotPasswordUrl = $baseUrl . '/index.php?' . $contextKey . '=index&page=login&op=lostPassword';
        $registerUrl = $baseUrl . '/index.php?' . $contextKey . '=index&page=user&op=register';
    }
}

// Agregar source a los enlaces si existe
if ($source) {
    $forgotPasswordUrl .= '?source=' . urlencode($source);
    $registerUrl .= '?source=' . urlencode($source);
}

// Verificar si el registro está deshabilitado
$disableUserReg = Config::getVar('security', 'disable_user_reg') ? true : false;

// Obtener lista de revistas disponibles
try {
    $allJournals = getAllJournals(null, 0, $lang);
} catch (Exception $e) {
    $allJournals = [];
}

$contextLogoUrl = '';
$contextLogoAlt = '';
if ($journalPath) {
    $contextDao = Application::getContextDAO();
    $context = $contextDao->getByPath($journalPath);
    if ($context) {
        $locales = getPreferredLocales($lang);
        $contextLogoAlt = pickLocalizedValue($context->getData('name'), $locales);
        $logoData = pickLocalizedValue($context->getData('pageHeaderLogoImage'), $locales);
        if (is_array($logoData) && !empty($logoData['uploadName'])) {
            import('classes.file.PublicFileManager');
            $publicFileManager = new PublicFileManager();
            $contextLogoUrl = $baseUrl . '/' .
                $publicFileManager->getContextFilesPath($context->getId()) . '/' . $logoData['uploadName'];
        }
    }
} else {
    $contextLogoUrl = $baseUrl . '/amcad-portal/assets/images/logo_acamed.png';
    $contextLogoAlt = 'AMCAD';
}

// Si hay un contexto de revista especificado, redirigir al login de esa revista
// para que OJS genere el CSRF token correctamente
// Eliminamos la redirección obligatoria al login nativo de la revista para centralizar todo en el portal.

// Generar CSRF token para el formulario
$sessionManager = SessionManager::getManager();
$session = $sessionManager->getUserSession();
$csrfToken = $session->getCSRFToken();

$profileUrl = '';
$logoutUrl = '';
if ($pathInfoEnabled) {
    $profileContext = $journalPath ?: 'index';
    $profileUrl = $baseUrl . '/' . $profileContext . '/user/profile';
    $logoutUrl = $baseUrl . '/' . $profileContext . '/login/signOut';
} else {
    $profileContext = $journalPath ?: 'index';
    $profileUrl = $baseUrl . '/index.php?' . $contextKey . '=' . rawurlencode($profileContext) . '&page=user&op=profile';
    $logoutUrl = $baseUrl . '/index.php?' . $contextKey . '=' . rawurlencode($profileContext) . '&page=login&op=signOut';
}

$pageTitle = "Portal AMCAD - " . t('login_title', $lang);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/amcad-portal/css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/amcad-portal/css/login.css">
</head>
<body>

    <!-- Banner Superior -->
    <div class="top-banner">
        <div class="container">
            <div class="banner-content">
                <!-- Logo AMCAD izquierda -->
                <div class="banner-logo-wrapper">
                    <img src="<?php echo $baseUrl; ?>/amcad-portal/assets/images/logo_acamed.png" alt="AMCAD Logo" class="banner-logo">
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
                            <img src="<?php echo $baseUrl; ?>/amcad-portal/assets/images/facebook_icon.png" alt="Facebook">
                        </a>
                        <a href="https://www.instagram.com/amcad.a.c/" target="_blank" rel="noopener">
                            <img src="<?php echo $baseUrl; ?>/amcad-portal/assets/images/instagram_icon.png" alt="Instagram">
                        </a>
                        <a href="mailto:amcadac@gmail.com">
                            <img src="<?php echo $baseUrl; ?>/amcad-portal/assets/images/correo_icon.png" alt="Email">
                        </a>
                        <a href="https://amcad.com.mx/" target="_blank" rel="noopener">
                            <img src="<?php echo $baseUrl; ?>/amcad-portal/assets/images/web_icon.png" alt="Sitio web AMCAD">
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
                    <a href="<?php echo $baseUrl; ?>/amcad-portal/index.php" class="header-logo-link">
                        <div class="header-logo">
                            <img src="<?php echo $baseUrl; ?>/amcad-portal/assets/images/AMCAD_logo.png" alt="AMCAD">
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
                        <li><a href="<?php echo $baseUrl; ?>/amcad-portal/index.php"><?php echo t('inicio', $lang); ?></a></li>
                        <li><a href="<?php echo $baseUrl; ?>/amcad-portal/lineamientos.php"><?php echo t('lineamientos', $lang); ?></a></li>
                        <li><a href="<?php echo $baseUrl; ?>/amcad-portal/recursos.php"><?php echo t('recursos', $lang); ?></a></li>
                        <li><a href="<?php echo $baseUrl; ?>/amcad-portal/contacto.php"><?php echo t('contacto', $lang); ?></a></li>
                        <?php if ($isLoggedIn): ?>
                            <li class="active header-user-menu">
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
                            <li class="active"><a href="#"><?php echo t('acceso', $lang); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div class="lang-selector">
                    <a href="?lang=<?php echo $lang === 'es' ? 'en' : 'es'; ?><?php echo $source ? '&source=' . urlencode($source) : ''; ?>" class="lang-link">
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

    <!-- Contenido Principal: Formulario de Login -->
    <main class="login-page">
        <div class="container">
            <div class="login-container">
                <div class="login-card">
                    <div class="login-header<?php echo $contextLogoUrl ? ' has-logo' : ' no-logo'; ?>">
                        <div class="login-header-content">
                            <?php if ($contextLogoUrl): ?>
                                <div class="login-context-logo">
                                    <img src="<?php echo htmlspecialchars($contextLogoUrl); ?>" alt="<?php echo htmlspecialchars($contextLogoAlt ?: 'Logo'); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="login-header-text">
                                <h2><?php echo t('login_title', $lang); ?></h2>
                                <p class="login-subtitle"><?php echo t('login_welcome', $lang); ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if ($loginMessage): ?>
                        <div class="alert alert-info">
                            <?php echo htmlspecialchars($loginMessage); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <?php
                            if ($error === 'user.login.accountDisabled') {
                                echo t('account_disabled', $lang);
                                if ($reason) {
                                    echo ': ' . $reason;
                                }
                            } else {
                                echo t('login_error', $lang);
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Selector de Contexto -->
                    <div class="context-selector">
                        <label for="loginContext">
                            <?php echo t('login_context', $lang); ?>
                        </label>
                        <select id="loginContext" class="context-select">
                            <option value="" disabled <?php echo $selectedContext === '' ? 'selected' : ''; ?>>
                                <?php echo t('login_context_placeholder', $lang); ?>
                            </option>
                            <option value="site" <?php echo $selectedContext === 'site' ? 'selected' : ''; ?>>
                                <?php echo t('general_access', $lang); ?>
                            </option>
                            <?php foreach ($allJournals as $journal): ?>
                                <option
                                    value="<?php echo htmlspecialchars($journal['path']); ?>"
                                    <?php echo ($selectedContext === $journal['path']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($journal['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="context-error" id="contextError">
                            <?php echo t('login_context_placeholder', $lang); ?>
                        </div>
                    </div>

                    <form class="login-form" method="post" action="<?php echo $loginUrl; ?>" id="loginForm">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrfToken" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="source" value="<?php echo $source; ?>">

                        <div class="form-group">
                            <label for="username">
                                <?php echo t('username', $lang); ?>
                                <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="<?php echo $username; ?>"
                                maxlength="32"
                                required
                                aria-required="true"
                                class="form-input"
                                autocomplete="username"
                            >
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <?php echo t('password', $lang); ?>
                                <span class="required">*</span>
                            </label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                maxlength="32"
                                required
                                aria-required="true"
                                class="form-input"
                                autocomplete="current-password"
                            >
                            <a href="<?php echo $forgotPasswordUrl; ?>" class="forgot-password-link">
                                <?php echo t('forgot_password', $lang); ?>
                            </a>
                        </div>

                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember" id="remember" value="1" checked>
                                <span><?php echo t('remember_me', $lang); ?></span>
                            </label>
                        </div>

                        <button type="submit" class="btn-login">
                            <?php echo t('login_button', $lang); ?>
                        </button>

                        <?php if (!$disableUserReg): ?>
                            <div class="register-link">
                                <?php echo t('no_account', $lang); ?>
                                <a href="<?php echo $registerUrl; ?>">
                                    <?php echo t('register_here', $lang); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <img src="<?php echo $baseUrl; ?>/amcad-portal/assets/images/ojs_pkp_logo.png" alt="OJS/PKP" class="footer-logo">
                </div>
                <div class="footer-center">
                    <p><?php echo t('copyright', $lang); ?></p>
                    <p><?php echo t('rights_reserved', $lang); ?></p>
                    <p>amcadac@gmail.com</p>
                </div>
                <div class="footer-right">
                    <a href="<?php echo $baseUrl; ?>/amcad-portal/index.php#about"><?php echo t('about_system', $lang); ?></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="<?php echo $baseUrl; ?>/amcad-portal/js/main.js"></script>
    <script>
        // Script para cambio dinámico de contexto de login
        (function() {
            const contextSelector = document.getElementById('loginContext');
            const baseUrl = '<?php echo $baseUrl; ?>';
            const currentLang = '<?php echo $lang; ?>';

            const loginForm = document.getElementById('loginForm');
            const contextError = document.getElementById('contextError');

            if (contextSelector) {
                contextSelector.addEventListener('change', function() {
                    const selectedContext = this.value;

                    // Recargar la página con el contexto seleccionado para obtener el CSRF token correcto
                    const params = new URLSearchParams();
                    if (selectedContext !== '') {
                        params.append('context', selectedContext);
                    }
                    params.append('lang', currentLang);

                    // Mantener otros parámetros si existen
                    const currentParams = new URLSearchParams(window.location.search);
                    if (currentParams.has('source')) {
                        params.append('source', currentParams.get('source'));
                    }
                    if (currentParams.has('username')) {
                        params.append('username', currentParams.get('username'));
                    }

                    // Redirigir con los nuevos parámetros
                    window.location.href = baseUrl + '/amcad-portal/login.php?' + params.toString();
                });
            }

            if (loginForm && contextSelector) {
                loginForm.addEventListener('submit', function(e) {
                    if (contextSelector.value === '') {
                        e.preventDefault();
                        if (contextError) {
                            contextError.style.display = 'block';
                        }
                        contextSelector.focus();
                    }
                });

                contextSelector.addEventListener('change', function() {
                    if (contextError) {
                        contextError.style.display = 'none';
                    }
                });
            }
        })();
    </script>
</body>
</html>
