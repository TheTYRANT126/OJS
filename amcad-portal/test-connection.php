<?php
/**
 * Archivo de diagnóstico para probar la conexión a OJS
 */

// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Conexión a OJS</h1>";

echo "<h2>1. Verificando rutas...</h2>";
$ojsBasePath = dirname(dirname(__FILE__));
echo "Ruta base OJS: " . $ojsBasePath . "<br>";
echo "INDEX_FILE_LOCATION: " . $ojsBasePath . '/index.php' . "<br>";
echo "Bootstrap path: " . $ojsBasePath . '/lib/pkp/includes/bootstrap.inc.php' . "<br>";

if (file_exists($ojsBasePath . '/index.php')) {
    echo "✓ index.php existe<br>";
} else {
    echo "✗ index.php NO existe<br>";
}

if (file_exists($ojsBasePath . '/lib/pkp/includes/bootstrap.inc.php')) {
    echo "✓ bootstrap.inc.php existe<br>";
} else {
    echo "✗ bootstrap.inc.php NO existe<br>";
}

echo "<h2>2. Intentando cargar OJS...</h2>";

try {
    // Definir constantes requeridas por OJS
    if (!defined('INDEX_FILE_LOCATION')) {
        define('INDEX_FILE_LOCATION', $ojsBasePath . '/index.php');
    }

    // Cambiar al directorio de OJS
    chdir($ojsBasePath);
    echo "Directorio cambiado a: " . getcwd() . "<br>";

    // Incluir el bootstrap de OJS
    echo "Intentando cargar bootstrap...<br>";
    $application = require($ojsBasePath . '/lib/pkp/includes/bootstrap.inc.php');
    echo "✓ Bootstrap cargado correctamente<br>";

    // Inicializar el request si no existe
    if (!isset($_SERVER['REQUEST_URI'])) {
        $_SERVER['REQUEST_URI'] = '/index/index';
    }
    if (!isset($_SERVER['PATH_INFO'])) {
        $_SERVER['PATH_INFO'] = '/index/index';
    }

    $getPreferredLocales = function() {
        $locales = [];
        $defaultLocale = Config::getVar('i18n', 'locale');
        if ($defaultLocale) {
            $locales[] = $defaultLocale;
        }
        return $locales;
    };

    $pickLocalizedValue = function($data, $locales) {
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
    };

    echo "<h2>3. Probando obtener revistas...</h2>";

    $contextDao = Application::getContextDAO();
    echo "✓ ContextDAO obtenido<br>";

    $contexts = $contextDao->getAll(true);
    echo "✓ Contextos obtenidos<br>";

    $contextList = $contexts->toArray();
    $count = count($contextList);
    echo "Total de revistas: " . $count . "<br><br>";

    if ($count > 0) {
        $locales = $getPreferredLocales();
        echo "<h3>Revistas encontradas:</h3>";
        echo "<ul>";
        foreach ($contextList as $context) {
            $name = $pickLocalizedValue($context->getData('name'), $locales);
            echo "<li>";
            echo "ID: " . $context->getId() . " - ";
            echo "Nombre: " . htmlspecialchars($name) . " - ";
            echo "Path: " . $context->getPath();
            echo "</li>";
        }
        echo "</ul>";
    }

    echo "<h2>✓ Test completado exitosamente</h2>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
