

<table class="table table-sm tabla-partidas-estilo tabla-documentos table-hover align-middle" id="tabla-archivos">
    <thead class="table-light">
        <tr>
            <th>Nombre</th>
            <th class="text-center">Tipo</th>
            <th class="text-center">Tamaño</th>
            <th class="text-center">Acciones</th>
        </tr>
    </thead>
    <tbody id="tabla-documentos-body">
        <?php
        require_once '../../modulos/conexion.php';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $stmt = $con->prepare("
        SELECT Id, Nombre, Ruta
        FROM conta_referencias_archivos 
        WHERE Referencia_id = :id
                                                ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        function obtenerIconoPorExtension($extension)
        {
            $extension = strtolower($extension);
            switch ($extension) {
                case 'pdf':
                    return '<i class="bi bi-file-earmark-pdf text-danger me-1"></i>';
                case 'xml':
                    return '<i class="bi bi-file-earmark-code text-primary me-1"></i>';
                case 'doc':
                case 'docx':
                    return '<i class="bi bi-file-earmark-word text-primary me-1"></i>';
                case 'xls':
                case 'xlsx':
                    return '<i class="bi bi-file-earmark-excel text-success me-1"></i>';
                case 'csv':
                    return '<i class="bi bi-filetype-csv text-success me-1"></i>';
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                    return '<i class="bi bi-file-earmark-image text-info me-1"></i>';
                case 'zip':
                case 'rar':
                    return '<i class="bi bi-file-earmark-zip text-warning me-1"></i>';
                case 'txt':
                    return '<i class="bi bi-file-earmark-text text-muted me-1"></i>';
                default:
                    return '<i class="bi bi-file-earmark text-secondary me-1"></i>';
            }
        }

        // Agrupar archivos por nombre base
        $agrupados = [];
        foreach ($documentos as $doc) {
            $base = pathinfo($doc['Nombre'], PATHINFO_FILENAME);
            $agrupados[$base][] = $doc;
        }

        // Función para imprimir fila
        function imprimirFila($doc)
        {
            $id = $doc['Id'];
            $nombre = htmlspecialchars($doc['Nombre']);
            $ruta = $doc['Ruta'];
            $tamano = file_exists($ruta) ? filesize($ruta) : 0;
            $tamanoLegible = ($tamano >= 1048576)
                ? round($tamano / 1048576, 2) . ' MB'
                : round($tamano / 1024, 2) . ' KB';
            $extension = pathinfo($ruta, PATHINFO_EXTENSION);
            $icono = obtenerIconoPorExtension($extension);
            ?>
            <tr>
                <td><?= $icono . $nombre ?></td>
                <td class="text-center text-uppercase"><?= strtoupper($extension) ?></td>
                <td class="text-center"><?= $tamanoLegible ?></td>
                <td class="text-center">
                    <a href="<?= htmlspecialchars($ruta) ?>" class="btn btn-sm btn-outline-success me-2 rounded-0" download
                        title="Descargar">
                        <i class="bi bi-download"></i> Descargar
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-0" data-eliminar="true"
                        data-id="<?= $id ?>" data-nombre="<?= $nombre ?>" data-ruta="<?= htmlspecialchars($ruta) ?>"
                        title="Eliminar">
                        Eliminar
                    </button>
                </td>
            </tr>
            <?php
        }

        // Primero imprimir archivos que tengan PDF y XML juntos
        $imprimidos = [];
        foreach ($agrupados as $base => $archivos) {
            $pdf = $xml = null;
            foreach ($archivos as $doc) {
                $ext = strtolower(pathinfo($doc['Nombre'], PATHINFO_EXTENSION));
                if ($ext === 'pdf')
                    $pdf = $doc;
                if ($ext === 'xml')
                    $xml = $doc;
            }
            if ($pdf) {
                imprimirFila($pdf);
                $imprimidos[] = $pdf['Id'];
            }
            if ($xml) {
                imprimirFila($xml);
                $imprimidos[] = $xml['Id'];
            }
        }

        // Luego los que quedaron solos (ni PDF ni XML emparejados)
        foreach ($documentos as $doc) {
            if (!in_array($doc['Id'], $imprimidos)) {
                imprimirFila($doc);
            }
        }

        // Si no hay documentos
        if (empty($documentos)) {
            echo '<tr><td colspan="4" class="text-center text-muted">Sin archivos adjuntos</td></tr>';
        }
        ?>
    </tbody>
</table>