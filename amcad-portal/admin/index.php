<?php
/**
 * Panel de Administración AMCAD
 * Solo accesible para administradores de OJS
 */

require_once(__DIR__ . '/../includes/admin-functions.php');
require_once(__DIR__ . '/../includes/translations.php');

// Verificar que sea administrador
if (!isOJSAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Detectar idioma
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['amcad_lang']) ? $_COOKIE['amcad_lang'] : 'es');
if (!in_array($lang, ['es', 'en'])) $lang = 'es';

// Inicializar base de datos
initAdminDatabase();

// Obtener datos
$lineamientos = getLineamientos(false);
$recursos = getRecursos(false);
$contactEmail = getConfig('contact_email', 'spidermavenom1206@hotmail.com');

// Obtener información del usuario
$sessionManager = SessionManager::getManager();
$session = $sessionManager->getUserSession();
$currentUser = $session->getUser();
$userName = $currentUser->getUsername();

$baseUrl = rtrim((string) Config::getVar('general', 'base_url'), '/');
$pathInfoEnabled = Config::getVar('general', 'disable_path_info') ? false : true;
$application = Application::get();
$contextList = $application ? $application->getContextList() : [];
$contextKey = $contextList[0] ?? 'journal';
$siteContext = 'index';

if ($pathInfoEnabled) {
    $profileUrl = $baseUrl . '/' . $siteContext . '/user/profile';
    $logoutUrl = $baseUrl . '/' . $siteContext . '/login/signOut';
} else {
    $profileUrl = $baseUrl . '/index.php?' . $contextKey . '=' . $siteContext . '&page=user&op=profile';
    $logoutUrl = $baseUrl . '/index.php?' . $contextKey . '=' . $siteContext . '&page=login&op=signOut';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Portal AMCAD</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-body">
    <!-- Header Sticky -->
    <header class="main-header admin-header" id="mainHeader">
        <div class="container">
            <div class="header-content">
                <div class="header-top-row">
                    <a href="../index.php" class="header-logo-link">
                        <div class="header-logo">
                            <img src="../assets/images/AMCAD_logo.png" alt="AMCAD">
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
                        <li><a href="../index.php">Inicio</a></li>
                        <li><a href="../lineamientos.php">Lineamientos</a></li>
                        <li><a href="../recursos.php">Recursos</a></li>
                        <li><a href="../contacto.php">Contacto</a></li>
                        <li class="header-user-menu">
                            <button
                                type="button"
                                class="user-menu-trigger"
                                aria-haspopup="true"
                                aria-expanded="false"
                            >
                                <span class="user-name-link">
                                    <?php echo htmlspecialchars($userName); ?>
                                </span>
                                <span class="user-menu-icon" aria-hidden="true">&#9662;</span>
                            </button>
                            <ul class="user-menu-dropdown">
                                <li>
                                    <a href="<?php echo htmlspecialchars($profileUrl); ?>">
                                        Editar perfil
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo htmlspecialchars($logoutUrl); ?>">
                                        Cerrar sesión
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>

                <div class="back-to-portal">
                    <a href="../index.php" class="btn-back-portal">Volver al Portal</a>
                </div>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <!-- Sidebar Panel -->
        <aside class="admin-sidebar">
            <h3>Administración</h3>
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="#lineamientos" data-section="lineamientos">
                            Lineamientos
                        </a>
                    </li>
                    <li>
                        <a href="#recursos" data-section="recursos">
                            Recursos
                        </a>
                    </li>
                    <li>
                        <a href="#contacto" data-section="contacto">
                            Configuración
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Lineamientos Section -->
            <section id="section-lineamientos" class="admin-section active">
                <div class="section-header">
                    <div class="section-title">
                        <h2>Gestión de Lineamientos</h2>
                        <p>Administra los documentos de lineamientos del portal</p>
                    </div>
                    <button class="btn-add" onclick="showAddModal('lineamiento')">+ Agregar Lineamiento</button>
                </div>

                <div class="items-list" id="lineamientos-list">
                    <?php foreach ($lineamientos as $item): ?>
                        <div class="item-card" data-id="<?php echo $item['id']; ?>">
                            <div class="item-header">
                                <div class="item-title">
                                    <h3><?php echo htmlspecialchars($item['titulo']); ?></h3>
                                    <?php if ($item['fijado']): ?>
                                        <span class="badge-pinned">Destacado</span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-actions">
                                    <button class="btn-action" onclick="moveItemUp('lineamiento', <?php echo $item['id']; ?>)" title="Mover arriba">↑</button>
                                    <button class="btn-action" onclick="moveItemDown('lineamiento', <?php echo $item['id']; ?>)" title="Mover abajo">↓</button>
                                    <button class="btn-action" onclick="togglePin('lineamiento', <?php echo $item['id']; ?>)" title="<?php echo $item['fijado'] ? 'Desfijar' : 'Fijar'; ?>">★</button>
                                    <button class="btn-action btn-edit" onclick="editItem('lineamiento', <?php echo $item['id']; ?>)" title="Editar">Editar</button>
                                    <button class="btn-action btn-delete" onclick="deleteItem('lineamiento', <?php echo $item['id']; ?>)" title="Eliminar">Eliminar</button>
                                </div>
                            </div>
                            <div class="item-body">
                                <div class="item-details">
                                    <div class="item-detail">
                                        <span class="detail-label">Descripción:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($item['descripcion'] ?: 'Sin descripción'); ?></span>
                                    </div>
                                    <?php if ($item['autor']): ?>
                                        <div class="item-detail">
                                            <span class="detail-label">Autor:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($item['autor']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['titulo_en'])): ?>
                                        <div class="item-detail">
                                            <span class="detail-label">Título (EN):</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($item['titulo_en']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['descripcion_en'])): ?>
                                        <div class="item-detail">
                                            <span class="detail-label">Descripción (EN):</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($item['descripcion_en']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-detail">
                                        <span class="detail-label">Archivo:</span>
                                        <span class="detail-value">
                                            <a href="../uploads/<?php echo htmlspecialchars($item['archivo']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($item['archivo']); ?>
                                            </a>
                                        </span>
                                    </div>
                                    <?php if (!empty($item['archivo_en'])): ?>
                                        <div class="item-detail">
                                            <span class="detail-label">Archivo (EN):</span>
                                            <span class="detail-value">
                                                <a href="../uploads/<?php echo htmlspecialchars($item['archivo_en']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($item['archivo_en']); ?>
                                                </a>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-meta">
                                        <span>Fecha: <?php echo date('d/m/Y H:i', strtotime($item['fecha_subida'])); ?></span>
                                        <span>Orden: <?php echo $item['orden']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($lineamientos)): ?>
                        <div class="empty-state">
                            <p>No hay lineamientos registrados.</p>
                            <p class="empty-state-hint">Haz clic en "Agregar Lineamiento" para comenzar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Recursos Section -->
            <section id="section-recursos" class="admin-section">
                <div class="section-header">
                    <div class="section-title">
                        <h2>Gestión de Recursos</h2>
                        <p>Administra los archivos de recursos del portal</p>
                    </div>
                    <button class="btn-add" onclick="showAddModal('recurso')">+ Agregar Recurso</button>
                </div>

                <div class="items-list" id="recursos-list">
                    <?php foreach ($recursos as $item): ?>
                        <div class="item-card" data-id="<?php echo $item['id']; ?>">
                            <div class="item-header">
                                <div class="item-title">
                                    <h3><?php echo htmlspecialchars($item['titulo']); ?></h3>
                                    <?php if ($item['fijado']): ?>
                                        <span class="badge-pinned">Destacado</span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-actions">
                                    <button class="btn-action" onclick="moveItemUp('recurso', <?php echo $item['id']; ?>)" title="Mover arriba">↑</button>
                                    <button class="btn-action" onclick="moveItemDown('recurso', <?php echo $item['id']; ?>)" title="Mover abajo">↓</button>
                                    <button class="btn-action" onclick="togglePin('recurso', <?php echo $item['id']; ?>)" title="<?php echo $item['fijado'] ? 'Desfijar' : 'Fijar'; ?>">★</button>
                                    <button class="btn-action btn-edit" onclick="editItem('recurso', <?php echo $item['id']; ?>)" title="Editar">Editar</button>
                                    <button class="btn-action btn-delete" onclick="deleteItem('recurso', <?php echo $item['id']; ?>)" title="Eliminar">Eliminar</button>
                                </div>
                            </div>
                            <div class="item-body">
                                <div class="item-details">
                                    <div class="item-detail">
                                        <span class="detail-label">Descripción:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($item['descripcion'] ?: 'Sin descripción'); ?></span>
                                    </div>
                                    <?php if ($item['autor']): ?>
                                        <div class="item-detail">
                                            <span class="detail-label">Autor:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($item['autor']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['titulo_en'])): ?>
                                        <div class="item-detail">
                                            <span class="detail-label">Título (EN):</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($item['titulo_en']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['descripcion_en'])): ?>
                                        <div class="item-detail">
                                            <span class="detail-label">Descripción (EN):</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($item['descripcion_en']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-detail">
                                        <span class="detail-label">Archivo:</span>
                                        <span class="detail-value">
                                            <a href="../uploads/<?php echo htmlspecialchars($item['archivo']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($item['archivo']); ?>
                                            </a>
                                        </span>
                                    </div>
                                    <?php if (!empty($item['archivo_en'])): ?>
                                        <div class="item-detail">
                                            <span class="detail-label">Archivo (EN):</span>
                                            <span class="detail-value">
                                                <a href="../uploads/<?php echo htmlspecialchars($item['archivo_en']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($item['archivo_en']); ?>
                                                </a>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-meta">
                                        <span>Fecha: <?php echo date('d/m/Y H:i', strtotime($item['fecha_subida'])); ?></span>
                                        <span>Orden: <?php echo $item['orden']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($recursos)): ?>
                        <div class="empty-state">
                            <p>No hay recursos registrados.</p>
                            <p class="empty-state-hint">Haz clic en "Agregar Recurso" para comenzar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Contacto Section -->
            <section id="section-contacto" class="admin-section">
                <div class="section-title">
                    <h2>Configuración de Contacto</h2>
                    <p>Configura el email donde se recibirán los mensajes del formulario</p>
                </div>

                <div class="config-card">
                    <h3>Email de Recepción</h3>
                    <p class="config-description">
                        Los mensajes enviados desde el formulario de contacto del portal serán dirigidos a este correo electrónico.
                    </p>

                    <form id="contact-config-form" class="config-form">
                        <div class="form-group">
                            <label for="contact_email">Correo Electrónico:</label>
                            <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($contactEmail); ?>" required>
                        </div>

                        <button type="submit" class="btn-save">Guardar Configuración</button>
                    </form>

                    <div id="contact-config-message" class="config-message"></div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal para agregar/editar -->
    <div id="item-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Agregar Item</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>

            <form id="item-form" enctype="multipart/form-data">
                <input type="hidden" id="item-type" name="type">
                <input type="hidden" id="item-id" name="id">

                <div class="form-group">
                    <label for="titulo">Título: <span class="required">*</span></label>
                    <input type="text" id="titulo" name="titulo" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="titulo_en">Título en inglés:</label>
                    <input type="text" id="titulo_en" name="titulo_en">
                </div>

                <div class="form-group">
                    <label for="descripcion_en">Descripción en inglés:</label>
                    <textarea id="descripcion_en" name="descripcion_en" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="autor">Autor (opcional):</label>
                    <input type="text" id="autor" name="autor">
                </div>

                <div class="form-group">
                    <label for="archivo">Archivo: <span id="file-required" class="required">*</span></label>
                    <input type="file" id="archivo" name="archivo">
                    <small>Formatos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, ZIP. Máximo 10 MB.</small>
                    <div id="current-file"></div>
                </div>

                <div class="form-group">
                    <label for="archivo_en">Archivo en inglés:</label>
                    <input type="file" id="archivo_en" name="archivo_en">
                    <small>Opcional. Se mostrará cuando el idioma del portal sea inglés.</small>
                    <div id="current-file-en"></div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar</button>
                </div>
            </form>

            <div id="modal-message" class="modal-message"></div>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>
