<?php
/**
 * API para operaciones del panel de administración
 */

require_once('../includes/admin-functions.php');

// Verificar que sea administrador
if (!isOJSAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add_lineamiento':
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $autor = trim($_POST['autor'] ?? '');
            $tituloEn = trim($_POST['titulo_en'] ?? '');
            $descripcionEn = trim($_POST['descripcion_en'] ?? '');

            if (empty($titulo)) {
                throw new Exception('El título es requerido');
            }

            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
                throw new Exception('El archivo es requerido');
            }

            $uploadResult = uploadFile($_FILES['archivo'], 'lineamiento');
            if (!$uploadResult['success']) {
                throw new Exception($uploadResult['error']);
            }

            $archivoEn = null;
            if (isset($_FILES['archivo_en']) && $_FILES['archivo_en']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResultEn = uploadFile($_FILES['archivo_en'], 'lineamiento_en');
                if (!$uploadResultEn['success']) {
                    throw new Exception($uploadResultEn['error']);
                }
                $archivoEn = $uploadResultEn['filename'];
            }

            $id = addLineamiento(
                $titulo,
                $descripcion,
                $autor,
                $uploadResult['filename'],
                $tituloEn === '' ? null : $tituloEn,
                $descripcionEn === '' ? null : $descripcionEn,
                $archivoEn
            );
            if (!$id) {
                throw new Exception('Error al guardar el lineamiento');
            }

            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Lineamiento agregado exitosamente']);
            break;

        case 'add_recurso':
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $autor = trim($_POST['autor'] ?? '');
            $tituloEn = trim($_POST['titulo_en'] ?? '');
            $descripcionEn = trim($_POST['descripcion_en'] ?? '');

            if (empty($titulo)) {
                throw new Exception('El título es requerido');
            }

            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
                throw new Exception('El archivo es requerido');
            }

            $uploadResult = uploadFile($_FILES['archivo'], 'recurso');
            if (!$uploadResult['success']) {
                throw new Exception($uploadResult['error']);
            }

            $archivoEn = null;
            if (isset($_FILES['archivo_en']) && $_FILES['archivo_en']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResultEn = uploadFile($_FILES['archivo_en'], 'recurso_en');
                if (!$uploadResultEn['success']) {
                    throw new Exception($uploadResultEn['error']);
                }
                $archivoEn = $uploadResultEn['filename'];
            }

            $id = addRecurso(
                $titulo,
                $descripcion,
                $autor,
                $uploadResult['filename'],
                $tituloEn === '' ? null : $tituloEn,
                $descripcionEn === '' ? null : $descripcionEn,
                $archivoEn
            );
            if (!$id) {
                throw new Exception('Error al guardar el recurso');
            }

            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Recurso agregado exitosamente']);
            break;

        case 'edit_lineamiento':
            $id = (int) ($_POST['id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $autor = trim($_POST['autor'] ?? '');
            $tituloEn = trim($_POST['titulo_en'] ?? '');
            $descripcionEn = trim($_POST['descripcion_en'] ?? '');

            if (empty($titulo)) {
                throw new Exception('El título es requerido');
            }

            $item = getLineamiento($id);
            if (!$item) {
                throw new Exception('Lineamiento no encontrado');
            }

            $archivo = null;
            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['archivo'], 'lineamiento');
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['error']);
                }
                $archivo = $uploadResult['filename'];
                deleteFile($item['archivo']);
            }

            $archivoEn = null;
            if (isset($_FILES['archivo_en']) && $_FILES['archivo_en']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResultEn = uploadFile($_FILES['archivo_en'], 'lineamiento_en');
                if (!$uploadResultEn['success']) {
                    throw new Exception($uploadResultEn['error']);
                }
                $archivoEn = $uploadResultEn['filename'];
                if (!empty($item['archivo_en'])) {
                    deleteFile($item['archivo_en']);
                }
            }

            $success = updateLineamiento(
                $id,
                $titulo,
                $descripcion,
                $autor,
                $archivo,
                $tituloEn === '' ? null : $tituloEn,
                $descripcionEn === '' ? null : $descripcionEn,
                $archivoEn
            );
            if (!$success) {
                throw new Exception('Error al actualizar el lineamiento');
            }

            echo json_encode(['success' => true, 'message' => 'Lineamiento actualizado exitosamente']);
            break;

        case 'edit_recurso':
            $id = (int) ($_POST['id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $autor = trim($_POST['autor'] ?? '');
            $tituloEn = trim($_POST['titulo_en'] ?? '');
            $descripcionEn = trim($_POST['descripcion_en'] ?? '');

            if (empty($titulo)) {
                throw new Exception('El título es requerido');
            }

            $item = getRecurso($id);
            if (!$item) {
                throw new Exception('Recurso no encontrado');
            }

            $archivo = null;
            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['archivo'], 'recurso');
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['error']);
                }
                $archivo = $uploadResult['filename'];
                deleteFile($item['archivo']);
            }

            $archivoEn = null;
            if (isset($_FILES['archivo_en']) && $_FILES['archivo_en']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResultEn = uploadFile($_FILES['archivo_en'], 'recurso_en');
                if (!$uploadResultEn['success']) {
                    throw new Exception($uploadResultEn['error']);
                }
                $archivoEn = $uploadResultEn['filename'];
                if (!empty($item['archivo_en'])) {
                    deleteFile($item['archivo_en']);
                }
            }

            $success = updateRecurso(
                $id,
                $titulo,
                $descripcion,
                $autor,
                $archivo,
                $tituloEn === '' ? null : $tituloEn,
                $descripcionEn === '' ? null : $descripcionEn,
                $archivoEn
            );
            if (!$success) {
                throw new Exception('Error al actualizar el recurso');
            }

            echo json_encode(['success' => true, 'message' => 'Recurso actualizado exitosamente']);
            break;

        case 'delete_lineamiento':
            $id = (int) ($_POST['id'] ?? 0);
            $success = deleteLineamiento($id);

            if (!$success) {
                throw new Exception('Error al eliminar el lineamiento');
            }

            echo json_encode(['success' => true, 'message' => 'Lineamiento eliminado exitosamente']);
            break;

        case 'delete_recurso':
            $id = (int) ($_POST['id'] ?? 0);
            $success = deleteRecurso($id);

            if (!$success) {
                throw new Exception('Error al eliminar el recurso');
            }

            echo json_encode(['success' => true, 'message' => 'Recurso eliminado exitosamente']);
            break;

        case 'toggle_pin_lineamiento':
            $id = (int) ($_POST['id'] ?? 0);
            $success = toggleLineamientoFijado($id);

            if (!$success) {
                throw new Exception('Error al cambiar estado de fijado');
            }

            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
            break;

        case 'toggle_pin_recurso':
            $id = (int) ($_POST['id'] ?? 0);
            $success = toggleRecursoFijado($id);

            if (!$success) {
                throw new Exception('Error al cambiar estado de fijado');
            }

            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
            break;

        case 'reorder_lineamientos':
            $order = json_decode($_POST['order'] ?? '[]', true);

            foreach ($order as $index => $id) {
                updateLineamientoOrden((int) $id, $index + 1);
            }

            echo json_encode(['success' => true, 'message' => 'Orden actualizado']);
            break;

        case 'reorder_recursos':
            $order = json_decode($_POST['order'] ?? '[]', true);

            foreach ($order as $index => $id) {
                updateRecursoOrden((int) $id, $index + 1);
            }

            echo json_encode(['success' => true, 'message' => 'Orden actualizado']);
            break;

        case 'get_lineamiento':
            $id = (int) ($_GET['id'] ?? 0);
            $item = getLineamiento($id);

            if (!$item) {
                throw new Exception('Lineamiento no encontrado');
            }

            echo json_encode(['success' => true, 'data' => $item]);
            break;

        case 'get_recurso':
            $id = (int) ($_GET['id'] ?? 0);
            $item = getRecurso($id);

            if (!$item) {
                throw new Exception('Recurso no encontrado');
            }

            echo json_encode(['success' => true, 'data' => $item]);
            break;

        case 'update_contact_email':
            $email = trim($_POST['email'] ?? '');

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }

            $success = setConfig('contact_email', $email);

            if (!$success) {
                throw new Exception('Error al guardar la configuración');
            }

            // Enviar correo de confirmación
            $subject = 'Confirmación de correo electrónico - Portal AMCAD';
            $message = "Hola,\n\n";
            $message .= "Este correo es para confirmar que tu dirección de email ha sido configurada correctamente ";
            $message .= "como correo de contacto del Portal AMCAD.\n\n";
            $message .= "A partir de ahora, todos los mensajes del formulario de contacto serán enviados a esta dirección.\n\n";
            $message .= "Si no realizaste este cambio, por favor contacta al administrador del sistema.\n\n";
            $message .= "Saludos,\n";
            $message .= "Portal AMCAD";

            $headers = "From: noreply@amcad.org\r\n";
            $headers .= "Reply-To: noreply@amcad.org\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            // Intentar enviar el correo
            $emailSent = @mail($email, $subject, $message, $headers);

            if ($emailSent) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Email actualizado exitosamente. Te llegará un correo para confirmar.'
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Email actualizado exitosamente. (No se pudo enviar el correo de confirmación)'
                ]);
            }
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
