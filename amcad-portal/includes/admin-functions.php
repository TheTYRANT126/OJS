<?php
/**
 * Funciones administrativas para el portal AMCAD
 * Sistema de gestión de contenido para Lineamientos, Recursos y Configuración
 */

require_once('ojs-functions.php');

// Ruta de la base de datos
if (!defined('ADMIN_DB_PATH')) {
    define('ADMIN_DB_PATH', __DIR__ . '/../data/admin.db');
}
if (!defined('UPLOADS_DIR')) {
    define('UPLOADS_DIR', __DIR__ . '/../uploads');
}

/**
 * Asegura que una columna exista en la tabla, agreg ndola si hace falta.
 */
function ensureTableColumn(SQLite3 $db, $table, $column, $definition) {
    $result = $db->query('PRAGMA table_info(' . $table . ')');
    $columnExists = false;

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (isset($row['name']) && $row['name'] === $column) {
            $columnExists = true;
            break;
        }
    }

    if (!$columnExists) {
        $db->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
    }
}

/**
 * Inicializar base de datos SQLite
 */
function initAdminDatabase() {
    // Crear directorio data si no existe
    $dataDir = dirname(ADMIN_DB_PATH);
    if (!file_exists($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    // Crear directorio de uploads si no existe
    if (!file_exists(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }

    $db = new SQLite3(ADMIN_DB_PATH);

    // Crear tabla de configuración
    $db->exec('
        CREATE TABLE IF NOT EXISTS config (
            key TEXT PRIMARY KEY,
            value TEXT
        )
    ');

    // Crear tabla de lineamientos
    $db->exec('
        CREATE TABLE IF NOT EXISTS lineamientos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            descripcion TEXT,
            titulo_en TEXT,
            descripcion_en TEXT,
            autor TEXT,
            archivo TEXT NOT NULL,
            archivo_en TEXT,
            fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
            orden INTEGER DEFAULT 0,
            fijado INTEGER DEFAULT 0,
            activo INTEGER DEFAULT 1
        )
    ');
    ensureTableColumn($db, 'lineamientos', 'titulo_en', 'TEXT');
    ensureTableColumn($db, 'lineamientos', 'descripcion_en', 'TEXT');
    ensureTableColumn($db, 'lineamientos', 'archivo_en', 'TEXT');

    // Crear tabla de recursos
    $db->exec('
        CREATE TABLE IF NOT EXISTS recursos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            descripcion TEXT,
            titulo_en TEXT,
            descripcion_en TEXT,
            autor TEXT,
            archivo TEXT NOT NULL,
            archivo_en TEXT,
            fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
            orden INTEGER DEFAULT 0,
            fijado INTEGER DEFAULT 0,
            activo INTEGER DEFAULT 1
        )
    ');
    ensureTableColumn($db, 'recursos', 'titulo_en', 'TEXT');
    ensureTableColumn($db, 'recursos', 'descripcion_en', 'TEXT');
    ensureTableColumn($db, 'recursos', 'archivo_en', 'TEXT');

    // Insertar email por defecto si no existe
    $stmt = $db->prepare('SELECT value FROM config WHERE key = ?');
    $stmt->bindValue(1, 'contact_email');
    $result = $stmt->execute();
    if (!$result->fetchArray()) {
        $stmt = $db->prepare('INSERT INTO config (key, value) VALUES (?, ?)');
        $stmt->bindValue(1, 'contact_email');
        $stmt->bindValue(2, 'spidermavenom1206@hotmail.com');
        $stmt->execute();
    }

    $db->close();
}

/**
 * Obtener conexión a la base de datos
 */
function getAdminDb() {
    if (!file_exists(ADMIN_DB_PATH)) {
        initAdminDatabase();
    }
    return new SQLite3(ADMIN_DB_PATH);
}

/**
 * Verificar si el usuario actual es administrador de OJS
 */
function isOJSAdmin() {
    try {
        $sessionManager = SessionManager::getManager();
        $session = $sessionManager->getUserSession();
        $currentUser = $session ? $session->getUser() : null;

        if (!$currentUser) {
            return false;
        }

        // Verificar si tiene rol de administrador del sitio
        $roleDao = DAORegistry::getDAO('RoleDAO');
        $roles = $roleDao->getByUserId($currentUser->getId());

        // Manejar tanto objetos DAOResultFactory como arrays
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($role->getRoleId() == ROLE_ID_SITE_ADMIN) {
                    return true;
                }
            }
        } else {
            while ($role = $roles->next()) {
                if ($role->getRoleId() == ROLE_ID_SITE_ADMIN) {
                    return true;
                }
            }
        }

        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Obtener configuración por clave
 */
function getConfig($key, $default = '') {
    $db = getAdminDb();
    $stmt = $db->prepare('SELECT value FROM config WHERE key = ?');
    $stmt->bindValue(1, $key);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();

    return $row ? $row['value'] : $default;
}

/**
 * Guardar configuración
 */
function setConfig($key, $value) {
    $db = getAdminDb();
    $stmt = $db->prepare('INSERT OR REPLACE INTO config (key, value) VALUES (?, ?)');
    $stmt->bindValue(1, $key);
    $stmt->bindValue(2, $value);
    $success = $stmt->execute();
    $db->close();

    return $success;
}

/**
 * Subir archivo
 */
function uploadFile($file, $prefix = 'file') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No se recibió archivo'];
    }

    $allowedExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip'];
    $maxSize = 10 * 1024 * 1024; // 10 MB

    $fileInfo = pathinfo($file['name']);
    $ext = strtolower($fileInfo['extension'] ?? '');

    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Archivo demasiado grande (máximo 10 MB)'];
    }

    $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
    $destination = UPLOADS_DIR . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'error' => 'Error al guardar el archivo'];
}

/**
 * Eliminar archivo
 */
function deleteFile($filename) {
    $filepath = UPLOADS_DIR . '/' . basename($filename);
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Obtener lineamientos (con ordenamiento y fijados)
 */
function getLineamientos($activeOnly = true) {
    $db = getAdminDb();

    $query = 'SELECT * FROM lineamientos';
    if ($activeOnly) {
        $query .= ' WHERE activo = 1';
    }
    $query .= ' ORDER BY fijado DESC, orden ASC, fecha_subida DESC';

    $result = $db->query($query);
    $lineamientos = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $lineamientos[] = $row;
    }

    $db->close();
    return $lineamientos;
}

/**
 * Obtener recursos (con ordenamiento y fijados)
 */
function getRecursos($activeOnly = true) {
    $db = getAdminDb();

    $query = 'SELECT * FROM recursos';
    if ($activeOnly) {
        $query .= ' WHERE activo = 1';
    }
    $query .= ' ORDER BY fijado DESC, orden ASC, fecha_subida DESC';

    $result = $db->query($query);
    $recursos = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $recursos[] = $row;
    }

    $db->close();
    return $recursos;
}

/**
 * Obtener lineamiento por ID
 */
function getLineamiento($id) {
    $db = getAdminDb();
    $stmt = $db->prepare('SELECT * FROM lineamientos WHERE id = ?');
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();

    return $row ?: null;
}

/**
 * Obtener recurso por ID
 */
function getRecurso($id) {
    $db = getAdminDb();
    $stmt = $db->prepare('SELECT * FROM recursos WHERE id = ?');
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();

    return $row ?: null;
}

/**
 * Agregar lineamiento
 */
function addLineamiento($titulo, $descripcion, $autor, $archivo, $tituloEn = null, $descripcionEn = null, $archivoEn = null) {
    $db = getAdminDb();

    // Obtener el orden máximo actual
    $result = $db->query('SELECT MAX(orden) as max_orden FROM lineamientos');
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $orden = ($row['max_orden'] ?? 0) + 1;

    $stmt = $db->prepare('
        INSERT INTO lineamientos (titulo, titulo_en, descripcion, descripcion_en, autor, archivo, archivo_en, orden)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->bindValue(1, $titulo);
    $stmt->bindValue(2, $tituloEn);
    $stmt->bindValue(3, $descripcion);
    $stmt->bindValue(4, $descripcionEn);
    $stmt->bindValue(5, $autor);
    $stmt->bindValue(6, $archivo);
    $stmt->bindValue(7, $archivoEn);
    $stmt->bindValue(8, $orden, SQLITE3_INTEGER);

    $success = $stmt->execute();
    $id = $db->lastInsertRowID();
    $db->close();

    return $success ? $id : false;
}

/**
 * Agregar recurso
 */
function addRecurso($titulo, $descripcion, $autor, $archivo, $tituloEn = null, $descripcionEn = null, $archivoEn = null) {
    $db = getAdminDb();

    // Obtener el orden máximo actual
    $result = $db->query('SELECT MAX(orden) as max_orden FROM recursos');
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $orden = ($row['max_orden'] ?? 0) + 1;

    $stmt = $db->prepare('
        INSERT INTO recursos (titulo, titulo_en, descripcion, descripcion_en, autor, archivo, archivo_en, orden)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->bindValue(1, $titulo);
    $stmt->bindValue(2, $tituloEn);
    $stmt->bindValue(3, $descripcion);
    $stmt->bindValue(4, $descripcionEn);
    $stmt->bindValue(5, $autor);
    $stmt->bindValue(6, $archivo);
    $stmt->bindValue(7, $archivoEn);
    $stmt->bindValue(8, $orden, SQLITE3_INTEGER);

    $success = $stmt->execute();
    $id = $db->lastInsertRowID();
    $db->close();

    return $success ? $id : false;
}

/**
 * Actualizar lineamiento
 */
function updateLineamiento($id, $titulo, $descripcion, $autor, $archivo = null, $tituloEn = null, $descripcionEn = null, $archivoEn = null) {
    $db = getAdminDb();

    $fields = [
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'autor' => $autor,
        'titulo_en' => $tituloEn,
        'descripcion_en' => $descripcionEn
    ];

    if ($archivo !== null) {
        $fields['archivo'] = $archivo;
    }

    if ($archivoEn !== null) {
        $fields['archivo_en'] = $archivoEn;
    }

    $setClauses = [];
    foreach ($fields as $column => $value) {
        $setClauses[] = $column . ' = ?';
    }

    $stmt = $db->prepare('UPDATE lineamientos SET ' . implode(', ', $setClauses) . ' WHERE id = ?');

    $index = 1;
    foreach ($fields as $value) {
        $stmt->bindValue($index++, $value);
    }
    $stmt->bindValue($index, $id, SQLITE3_INTEGER);

    $success = $stmt->execute();
    $db->close();

    return $success;
}

/**
 * Actualizar recurso
 */
function updateRecurso($id, $titulo, $descripcion, $autor, $archivo = null, $tituloEn = null, $descripcionEn = null, $archivoEn = null) {
    $db = getAdminDb();

    $fields = [
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'autor' => $autor,
        'titulo_en' => $tituloEn,
        'descripcion_en' => $descripcionEn
    ];

    if ($archivo !== null) {
        $fields['archivo'] = $archivo;
    }

    if ($archivoEn !== null) {
        $fields['archivo_en'] = $archivoEn;
    }

    $setClauses = [];
    foreach ($fields as $column => $value) {
        $setClauses[] = $column . ' = ?';
    }

    $stmt = $db->prepare('UPDATE recursos SET ' . implode(', ', $setClauses) . ' WHERE id = ?');

    $index = 1;
    foreach ($fields as $value) {
        $stmt->bindValue($index++, $value);
    }
    $stmt->bindValue($index, $id, SQLITE3_INTEGER);

    $success = $stmt->execute();
    $db->close();

    return $success;
}

/**
 * Eliminar lineamiento
 */
function deleteLineamiento($id) {
    $lineamiento = getLineamiento($id);
    if (!$lineamiento) return false;

    deleteFile($lineamiento['archivo']);
    if (!empty($lineamiento['archivo_en'])) {
        deleteFile($lineamiento['archivo_en']);
    }

    $db = getAdminDb();
    $stmt = $db->prepare('DELETE FROM lineamientos WHERE id = ?');
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $success = $stmt->execute();
    $db->close();

    return $success;
}

/**
 * Eliminar recurso
 */
function deleteRecurso($id) {
    $recurso = getRecurso($id);
    if (!$recurso) return false;

    deleteFile($recurso['archivo']);
    if (!empty($recurso['archivo_en'])) {
        deleteFile($recurso['archivo_en']);
    }

    $db = getAdminDb();
    $stmt = $db->prepare('DELETE FROM recursos WHERE id = ?');
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $success = $stmt->execute();
    $db->close();

    return $success;
}

/**
 * Actualizar orden de lineamiento
 */
function updateLineamientoOrden($id, $orden) {
    $db = getAdminDb();
    $stmt = $db->prepare('UPDATE lineamientos SET orden = ? WHERE id = ?');
    $stmt->bindValue(1, $orden, SQLITE3_INTEGER);
    $stmt->bindValue(2, $id, SQLITE3_INTEGER);
    $success = $stmt->execute();
    $db->close();

    return $success;
}

/**
 * Actualizar orden de recurso
 */
function updateRecursoOrden($id, $orden) {
    $db = getAdminDb();
    $stmt = $db->prepare('UPDATE recursos SET orden = ? WHERE id = ?');
    $stmt->bindValue(1, $orden, SQLITE3_INTEGER);
    $stmt->bindValue(2, $id, SQLITE3_INTEGER);
    $success = $stmt->execute();
    $db->close();

    return $success;
}

/**
 * Marcar/desmarcar lineamiento como fijado
 */
function toggleLineamientoFijado($id) {
    $db = getAdminDb();
    $stmt = $db->prepare('UPDATE lineamientos SET fijado = 1 - fijado WHERE id = ?');
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $success = $stmt->execute();
    $db->close();

    return $success;
}

/**
 * Marcar/desmarcar recurso como fijado
 */
function toggleRecursoFijado($id) {
    $db = getAdminDb();
    $stmt = $db->prepare('UPDATE recursos SET fijado = 1 - fijado WHERE id = ?');
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $success = $stmt->execute();
    $db->close();

    return $success;
}

/**
 * Obtiene el valor localizado de un campo, usando el sufijo _en para ingls.
 */
function getLocalizedField(array $item, $field, $lang) {
    if ($lang === 'en') {
        $key = $field . '_en';
        if (array_key_exists($key, $item) && $item[$key] !== null && $item[$key] !== '') {
            return $item[$key];
        }
    }

    return $item[$field] ?? '';
}
?>
