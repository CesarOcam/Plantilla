<?php
// config.php

if (strpos($_SERVER['DOCUMENT_ROOT'], 'xampp') !== false) {
    $base_url = '/portal_web/Contabilidad';
} else {
    $base_url = ''; // porque root ya es /var/www/html/portal_web/Contabilidad
}
// Función para generar URLs absolutas con base_url
function url($path = '') {
    global $base_url;
    return $base_url . $path;
}
