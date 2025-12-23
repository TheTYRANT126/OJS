{**
 * templates/frontend/pages/userLogin.tpl
 *
 * Redirección al portal AMCAD personalizado para login
 * Mantiene los parámetros necesarios para la autenticación
 *}
<script type="text/javascript">
    // Redirigir al portal AMCAD manteniendo los parámetros
    (function() {
        var baseUrl = '{$baseUrl|escape:"javascript"}';
        var portalUrl = baseUrl.replace(/\/$/, '') + '/amcad-portal/login.php';

        // Mantener los parámetros de query string
        var params = new URLSearchParams(window.location.search);
        params.set('fromOJS', '1');
        if (!params.has('context')) {
            try {
                var basePath = new URL(baseUrl).pathname.replace(/\/$/, '');
                var currentPath = window.location.pathname.replace(basePath, '');
                var pathParts = currentPath.replace(/^\/+/, '').split('/');
                if (pathParts[0] && pathParts[0] !== 'index') {
                    params.set('context', pathParts[0]);
                }
            } catch (e) {
                // Si falla el parseo, seguimos sin contexto
            }
        }
        // Añadir variables del servidor en caso de errores o reintentos
        var serverParams = {
            loginMessage: '{$loginMessage|escape:"javascript"}',
            username: '{$username|escape:"javascript"}',
            error: '{$error|escape:"javascript"}',
            reason: '{$reason|escape:"javascript"}',
            source: '{$source|escape:"javascript"}'
        };
        Object.keys(serverParams).forEach(function(key) {
            if (serverParams[key] && !params.has(key)) {
                params.set(key, serverParams[key]);
            }
        });

        var queryString = params.toString();
        if (queryString) {
            portalUrl += '?' + queryString;
        }

        // Redirigir
        window.location.replace(portalUrl);
    })();
</script>
<noscript>
    <meta http-equiv="refresh" content="0; url={$baseUrl}/amcad-portal/login.php">
    <p>Redirigiendo a la página de inicio de sesión...</p>
</noscript>
