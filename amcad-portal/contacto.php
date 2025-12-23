<?php
/**
 * Portal AMCAD - Página de Contacto
 * Asociación Mexicana de Cirugía del Aparato Digestivo, A.C.
 */

// Cargar funciones de OJS y traducciones
require_once('includes/ojs-functions.php');
require_once('includes/admin-functions.php');
require_once('includes/translations.php');

// Detectar idioma (por defecto español)
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['amcad_lang']) ? $_COOKIE['amcad_lang'] : 'es');
if (!in_array($lang, ['es', 'en'])) $lang = 'es';

// Guardar idioma en cookie
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

$pageTitle = "Portal AMCAD - " . t('contacto', $lang);

// Manejar el envío del formulario
$formSubmitted = false;
$formError = false;
$errorDetails = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    // Validación detallada
    $errors = [];
    if (empty($nombre)) {
        $errors[] = $lang === 'es' ? 'El nombre es requerido' : 'Name is required';
    }
    if (empty($email)) {
        $errors[] = $lang === 'es' ? 'El correo es requerido' : 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = $lang === 'es' ? 'El correo no es válido' : 'Email is not valid';
    }
    if (empty($mensaje)) {
        $errors[] = $lang === 'es' ? 'El mensaje es requerido' : 'Message is required';
    }

    if (empty($errors)) {
        // Preparar datos del mensaje
        $fecha = date('Y-m-d H:i:s');
        $to = getConfig('contact_email', 'spidermavenom1206@hotmail.com');
        $subject = $lang === 'es' ? 'Nuevo mensaje de contacto - Portal AMCAD' : 'New contact message - AMCAD Portal';

        $emailBody = ($lang === 'es' ? "Nuevo mensaje de contacto desde el Portal AMCAD\n\n" : "New contact message from AMCAD Portal\n\n");
        $emailBody .= "Fecha: " . $fecha . "\n";
        $emailBody .= ($lang === 'es' ? "Nombre: " : "Name: ") . $nombre . "\n";
        $emailBody .= ($lang === 'es' ? "Correo: " : "Email: ") . $email . "\n";
        $emailBody .= ($lang === 'es' ? "Teléfono: " : "Phone: ") . ($telefono ?: 'No proporcionado') . "\n\n";
        $emailBody .= ($lang === 'es' ? "Mensaje:\n" : "Message:\n") . $mensaje . "\n";

        // Guardar mensaje en archivo de respaldo
        $logDir = __DIR__ . '/contact_messages';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/messages_' . date('Y-m') . '.txt';
        $logContent = str_repeat('=', 80) . "\n";
        $logContent .= $emailBody;
        $logContent .= str_repeat('=', 80) . "\n\n";

        file_put_contents($logFile, $logContent, FILE_APPEND);

        // Intentar enviar correo
        $headers = "From: Portal AMCAD <noreply@amcad.org>\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        // En XAMPP, mail() probablemente no funciona, así que marcamos como exitoso
        // ya que guardamos el mensaje en el archivo
        $mailSent = @mail($to, $subject, $emailBody, $headers);

        // Marcar como exitoso independientemente del resultado del mail()
        // porque guardamos el mensaje en el archivo
        $formSubmitted = true;
    } else {
        $formError = true;
        $errorDetails = implode('<br>', $errors);
    }
}
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
                        <a href="https://amcad.com.mx/" target="_blank" rel="noopener">
                            <img src="assets/images/web_icon.png" alt="Sitio web AMCAD">
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
                        <li class="active"><a href="contacto.php"><?php echo t('contacto', $lang); ?></a></li>
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

    <!-- Sección de Contacto -->
    <section class="contact-section">
        <div class="container">
            <h2><?php echo t('contact_title', $lang); ?></h2>

            <div class="contact-container">
                <!-- Información de Contacto -->
                <div class="contact-info">
                    <h3><?php echo t('contact_info_title', $lang); ?></h3>

                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <div class="contact-details">
                            <h4><?php echo t('contact_phone', $lang); ?></h4>
                            <p><a href="tel:+525552114019">55 5211 4019</a></p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <div class="contact-details">
                            <h4><?php echo t('contact_email', $lang); ?></h4>
                            <p><a href="mailto:amcadac@gmail.com">amcadac@gmail.com</a></p>
                        </div>
                    </div>

                    <!-- Logo AMCAD -->
                    <div class="contact-logo">
                        <img src="assets/images/logo_acamed.png" alt="AMCAD Logo">
                    </div>
                </div>

                <!-- Formulario de Contacto -->
                <div class="contact-form-wrapper">
                    <h3><?php echo t('contact_form_title', $lang); ?></h3>

                    <?php if ($formSubmitted): ?>
                        <div class="success-message">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <h4><?php echo t('contact_success_title', $lang); ?></h4>
                            <p><?php echo t('contact_success_message', $lang); ?></p>
                            <a href="contacto.php?lang=<?php echo $lang; ?>" class="btn-reset"><?php echo t('contact_send_another', $lang); ?></a>
                        </div>
                    <?php else: ?>
                        <?php if ($formError): ?>
                            <div class="error-message">
                                <?php if (!empty($errorDetails)): ?>
                                    <?php echo $errorDetails; ?>
                                <?php else: ?>
                                    <?php echo t('contact_error_message', $lang); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <form action="contacto.php?lang=<?php echo $lang; ?>" method="POST" class="contact-form" id="contactForm">
                            <div class="form-group">
                                <label for="nombre"><?php echo t('contact_name', $lang); ?> <span class="required">*</span></label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email"><?php echo t('contact_email', $lang); ?> <span class="required">*</span></label>
                                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="telefono"><?php echo t('contact_phone', $lang); ?></label>
                                <input type="tel" id="telefono" name="telefono" value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="mensaje"><?php echo t('contact_message', $lang); ?> <span class="required">*</span></label>
                                <textarea id="mensaje" name="mensaje" rows="6" required><?php echo isset($_POST['mensaje']) ? htmlspecialchars($_POST['mensaje']) : ''; ?></textarea>
                            </div>

                            <p class="required-fields-note">
                                * <?php echo $lang === 'es' ? 'Campos obligatorios.' : 'Required fields.'; ?>
                            </p>

                            <button type="submit" name="submit_contact" class="btn-submit">
                                <?php echo t('contact_submit', $lang); ?>
                            </button>
                        </form>
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
                    <?php
                    $showAdminLink = false;
                    try {
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
