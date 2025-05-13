<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['nombre'], $_POST['rfc'], $_POST['tipo'])) {
    // Recoger todos los valores
    $nombre = trim($_POST['nombre']);
    $rfc = trim($_POST['rfc']);
    $tipo = trim($_POST['tipo']);
    $calle = $_POST['calle'] ?? '';
    $num_exterior = $_POST['num_exterior'] ?? '';
    $num_interior = $_POST['num_interior'] ?? '';
    $cp = $_POST['cp'] ?? '';
    $colonia = $_POST['colonia'] ?? '';
    $localidad = $_POST['localidad'] ?? '';
    $referencia = $_POST['referencia'] ?? '';
    $municipio = $_POST['municipio'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $pais = $_POST['pais'] ?? '';
    $telefono = $_POST['tel'] ?? '';
    $email_trafico = $_POST['email_trafico'] ?? '';
    $email_conta = $_POST['email_conta'] ?? '';

    // Función para obtener la fecha y hora actual
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
    }

    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = 1;

    // Asegurarse de que todos los campos coincidan con los de la base de datos
    $sql = "INSERT INTO clientes 
        (Nombre, Rfc, Tipo, Calle, NumeroExterior, NumeroInterior, CodigoPostal,
         Colonia, Localidad, ReferenciaDomicilio, Municipio, Estado, Pais, Telefono, Emailtrafico, EmailContabilidad, Activo, FechaAlta, UsuarioAlta)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Crear el array de parámetros, sin incluir el valor de 'Activo' ya que ya está seteo como 1
    $params = [
        $nombre, $rfc, $tipo, $calle, $num_exterior, $num_interior, $cp,
        $colonia, $localidad, $referencia, $municipio, $estado, $pais,
        $telefono, $email_trafico, $email_conta, $activo, $fecha_alta, $usuarioAlta
    ];

    // Verificar que el número de parámetros coincida con el número de `?` en la consulta
    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params); // Pasamos el array de parámetros

            if ($resultado) {
                echo "Cliente guardado correctamente.";
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

