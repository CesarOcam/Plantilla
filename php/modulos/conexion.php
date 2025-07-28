<?php
$host = "3.224.12.191";
$usuario = "sql_sapamexport_"; 
$contraseña = "27sNnCnwjid5EN2M"; 
$nombre_base_datos = "sql_sapamexport_"; 

try {
    $con = new PDO("mysql:host=$host;dbname=$nombre_base_datos", $usuario, $contraseña);
    
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

