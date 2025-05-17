<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['beneficiario'])) {
    // Recoger todos los valores
    $empresa = 2;
    $tipo = $_POST['tipo'];
    $beneficiario = trim($_POST['beneficiario']);
    $fecha = $_POST['fecha'];
    $numero = '22222';
    $concepto = $_POST['concepto'];
    $cargos = $_POST['Cargo'] ?? [];
    $abonos = $_POST['Abono'] ?? [];

    $total_cargos = 0.0;
    $total_abonos = 0.0;

    // Sumar cargos
    foreach ($cargos as $c) {
        $total_cargos += is_numeric($c) ? floatval($c) : 0;
    }

    // Sumar abonos
    foreach ($abonos as $a) {
        $total_abonos += is_numeric($a) ? floatval($a) : 0;
    }

    $importe = $total_cargos; // o $total_abonos, o la suma de ambos según tu lógica

    // Función para obtener la fecha y hora actual
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
    }
    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = 1;
    $exportadoCoi = 1;


    $tipo_poliza = $_POST['tipo'] ?? null;

if (!empty($tipo_poliza) && is_numeric($tipo_poliza)) {
    switch ((int)$tipo_poliza) {
        case 1: $prefijo = 'C'; break;
        case 2: $prefijo = 'D'; break;
        case 3: $prefijo = 'I'; break;
        case 4: $prefijo = 'E'; break;
        default: $prefijo = 'X'; break;
    }

    $sql = "
        SELECT Numero
        FROM polizas
        WHERE LEFT(Numero, 1) = ?
        ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC
        LIMIT 1
    ";

    $stmt = $con->prepare($sql);
    $stmt->execute([$prefijo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $ultimo_numero = (int) substr($row['Numero'], 1);
        $nuevo_numero = $ultimo_numero + 1;
    } else {
        $nuevo_numero = 1;
    }

    $numero_poliza = $prefijo . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);

} else {
    die("Tipo de póliza no especificado o inválido.");
}

// Luego usa $numero_poliza en tu insert

    // Asegurarse de que todos los campos coincidan con los de la base de datos
    $sql = "INSERT INTO polizas 
    (
        BeneficiarioId, EmpresaId, Numero, Importe, Concepto, Fecha, ExportadoCoi, Activo, FechaAlta, UsuarioAlta
    )
    VALUES (?, ?, ?, ?, ?, ?, ? , ?, ?, ?)";

    // Crear el array de parámetros, sin incluir el valor de 'Activo' ya que ya está seteo como 1
    $params = [
    $beneficiario,   // FechaAlta
    $empresa,  // UsuarioAlta
    $numero_poliza, 
    $importe,  // Activo
    $concepto,
    $fecha,
    $exportadoCoi,
    $activo,
    $fecha_alta, 
    $usuarioAlta       // Nombre
    ];


    // Verificar que el número de parámetros coincida con el número de `?` en la consulta
    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params); // Pasamos el array de parámetros

            if ($resultado) {
                echo "Poliza guardada correctamente.";
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

