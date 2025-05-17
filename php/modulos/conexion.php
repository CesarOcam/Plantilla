<?php
// Definir las variables de conexión a la base de datos
$host = "35.208.179.185"; // Cambia esto si tu base de datos está en otro servidor
$usuario = "root"; // Tu usuario de MySQL
$contraseña = "Am3xP0rt_2025"; // Tu contraseña de MySQL (deja vacío si no tienes contraseña configurada)
$nombre_base_datos = "amexportdb"; // Nombre de tu base de datos

try {
    // Crear la conexión usando PDO
    $con = new PDO("mysql:host=$host;dbname=$nombre_base_datos", $usuario, $contraseña);
    
    // Configurar el modo de error de PDO a excepción
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $con->exec("SET NAMES 'utf8mb4'");

    //echo "Conexión exitosa";
    
    // Si la conexión es exitosa, puedes imprimir este mensaje
    //echo "Conexión exitosa a la base de datos!";
} catch (PDOException $e) {
    // En caso de error, mostrar el mensaje de error
    echo "Error de conexión: " . $e->getMessage();
}
?>

