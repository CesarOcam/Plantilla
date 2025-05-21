<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php'); // o la página de login
exit;
