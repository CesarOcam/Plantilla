<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['numero'], $_POST['nombre'], $_POST['tipo_saldo'])) {
    // Recoger todos los valores
    $numero = $_POST['numero'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo_saldo'];

    // Función para obtener la fecha y hora actual
    function obtenerFechaHoraActual()
    {
        return date("Y-m-d H:i:s"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
    }

    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = 1;
    $empresa = 2;
    $saldo = 0;

    // Asegurarse de que todos los campos coincidan con los de la base de datos
    $sql = "INSERT INTO cuentas 
    (
        Numero, Nombre, TipoSaldo, EmpresaId, Activo, FechaAlta, UsuarioAlta, Saldo
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";


    // Crear el array de parámetros, sin incluir el valor de 'Activo' ya que ya está seteo como 1
    $params = [
        $numero,
        $nombre,   // nombre va en segundo lugar
        $tipo,     // tipo_saldo en tercero
        $empresa,
        $activo,
        $fecha_alta,
        $usuarioAlta,
        $saldo
    ];


    // Verificar que el número de parámetros coincida con el número de `?` en la consulta
    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params); // Pasamos el array de parámetros

            if ($resultado) {
                echo "Cuenta guardada correctamente.";
            } else {
                echo "Error al guardar: " . implode(", ", $stmt->errorInfo());
            }
        } else {
            echo "Error al preparar la consulta: " . implode(", ", $con->errorInfo());
        }
    }
} else {
    echo "Faltan datos obligatorios.";
}
?>