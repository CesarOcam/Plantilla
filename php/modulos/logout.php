<?php
session_start();
include_once __DIR__ . '/../../config.php';

$_SESSION = [];
session_destroy();

// Redirigir usando base_url
header('Location: ' . $base_url . '/login.php'); // o usa url('/login.php')
exit;
