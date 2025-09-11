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
  <!-- jQuery (solo una vez) -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- DataTables core -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <!-- DataTables Buttons -->
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

  <!-- Dependencias para exportar -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

  <link rel="stylesheet" href="../../css/style.css">

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../../js/inicio/charts/chart_1.js"></script>
  <script src="../../js/inicio/charts/chart_2.js"></script>
</head>

<body>

  <?php
  include('navbar.php');
  ?>

  <div class="container-fluid my-4">
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="graficaTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" type="button"
          role="tab" aria-controls="tab1" aria-selected="true">
          Inicio
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab"
          aria-controls="tab2" aria-selected="false">
          Gráficas
        </button>
      </li>
    </ul>

    <!-- Tab content -->
    <div class="tab-content mt-3" id="graficaTabsContent">
      <!-- Tab vacía -->
      <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
        <div class="row my-4">
          <!-- Operaciones por aduana -->
          <div class="col-md-6">
            <div class="card h-100 rounded-0">
              <div class="card-header bg-light text-dark fw-semibold d-flex justify-content-between align-items-center">
                <span>Operaciones en Tráfico</span>
                <span class="ms-auto">
                  <?php if ($_SESSION['usuario_id'] === 'jesus' || $_SESSION['usuario_id'] === 'YAMMONM81' || $_SESSION['usuario_id'] === 'Master'): ?>
                    <select name="ejecutivo" class="form-control mb-3" id="select-ejecutivo">
                      <option value="jesus">Jesús Reyes</option>
                      <option value="SEBROSARA99">Sebastian Rosario</option>
                      <option value="DHALAG23">Dharma Lagunes</option>
                      <option value="AMAAGUGAL43">Amairani Aguilar</option>
                    </select>
                  <?php endif; ?>
                </span>
              </div>
              <div class="card-body">
                <div class="table-responsive" id="tabla-trafico">

                </div>
              </div>
            </div>
          </div>
          <!-- Carga de trabajo por operativo -->
          <div class="col-md-6">
            <div class="card h-100 rounded-0">
              <div class="card-header bg-light text-dark fw-semibold d-flex justify-content-between align-items-center">
                <span>Operaciones en Contabilidad</span>
              </div>
              <div class="card-body">
                <div class="table-responsive" id="tabla-contabilidad">

                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab con gráficas -->
      <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
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
      </div>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
    </script>
  <script src="../../js/inicio/tablas/tabla_trafico.js"></script>
  <script src="../../js/inicio/tablas/tabla_contabilidad.js"></script>
  <script src="../../js/inicio/tablas/actualizar.js"></script>
</body>

</html>