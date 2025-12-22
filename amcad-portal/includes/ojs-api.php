<?php
/**
 * Conexión con OJS mediante API REST
 * Esta es una alternativa más simple que no requiere bootstrap
 */

/**
 * Obtener artículos mediante la API REST de OJS
 */
function getArticlesViaAPI($limit = 10) {
    $apiUrl = 'http://localhost/OJS/index/api/v1/submissions';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . '?count=' . $limit);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $data = json_decode($response, true);
        return isset($data['items']) ? $data['items'] : [];
    }

    return [];
}

/**
 * Búsqueda mediante API
 */
function searchViaAPI($query) {
    $apiUrl = 'http://localhost/OJS/index/api/v1/submissions';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . '?searchPhrase=' . urlencode($query));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $data = json_decode($response, true);
        return isset($data['items']) ? $data['items'] : [];
    }

    return [];
}

/**
 * Obtener número actual mediante API
 */
function getCurrentIssueViaAPI() {
    $apiUrl = 'http://localhost/OJS/index/api/v1/issues/current';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        return json_decode($response, true);
    }

    return null;
}
?>
