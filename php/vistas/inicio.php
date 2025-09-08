<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
  header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
  exit;
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contabilidad</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
  <link rel="stylesheet" href="../../css/style.css">

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../../js/charts/chart_1.js"></script>
  <script src="../../js/charts/chart_2.js"></script>
</head>

<body>

  <?php
  include('navbar.php');
  ?>

  <div class="container-fluid">
    <div class="row my-4">
      <!-- Operaciones por aduana -->
      <div class="col-md-6">
        <div class="card h-100 rounded-0">
          <div class="card-header bg-light text-dark fw-semibold d-flex justify-content-between align-items-center">
            <span>Número de Operaciones en Tráfico</span>
            <span class="ms-auto">
              Total: <label id="numero"></label> |
              Ref. más antigua: <label id="referencia"></label>
            </span>
          </div>

          <div class="card-body">
            <canvas id="aduanasChart" height="100"></canvas>
          </div>
        </div>
      </div>

      <!-- Carga de trabajo por operativo -->
      <div class="col-md-6">
        <div class="card h-100 rounded-0">
          <div class="card-header bg-light text-dark fw-semibold d-flex justify-content-between align-items-center">
            <span>Número de Operaciones en Contabilidad</span>
            <span class="ms-auto">
              Total: <label id="numero2"></label> |
              Ref. más antigua: <label id="referencia2"></label>
            </span>
          </div>
          <div class="card-body">
            <canvas id="aduanasChart2" height="100"></canvas>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
      </script>

</body>

</html>