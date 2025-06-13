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
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const renderChart = (canvasId, chartType, labels, dataValues, backgroundColor, borderColor, label) => {
        const canvas = document.getElementById(canvasId);
        if (canvas) {
          const ctx = canvas.getContext('2d');
          new Chart(ctx, {
            type: chartType,
            data: {
              labels: labels,
              datasets: [{
                label: label,
                data: dataValues,
                backgroundColor: backgroundColor,
                borderColor: borderColor,
                borderWidth: 1
              }]
            },
            options: {
              responsive: true,
              animation: {
                duration: 0,
                animations: {
                  numbers: {
                    type: 'number',
                    duration: 0
                  },
                  colors: {
                    type: 'color',
                    duration: 0
                  }
                }
              },
              scales: {
                y: { beginAtZero: true }
              }
            }
          });
        }
      };

      renderChart('aduanasChart', 'bar',
        ['Veracruz', 'Altamira', 'AIFA', 'Ciudad de México', 'Manzanillo', 'Lázaro Cardenas'],
        [34, 28, 15, 22, 48, 36],
        'rgba(98, 192, 75, 0.6)',
        'rgb(85, 192, 75)',
        'Operaciones'
      );

      renderChart('operativosChart', 'bar',
        ['Operativo 1', 'Operativo 2', 'Operativo 3', 'Operativo 4', 'Operativo 5'],
        [12, 19, 8, 14, 10],
        'rgba(255, 159, 64, 0.6)',
        'rgba(255, 159, 64, 1)',
        'Tareas Asignadas'
      );
    });
  </script>
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
          <div class="card-header">
            Número de Operaciones por Aduana
          </div>
          <div class="card-body">
            <canvas id="aduanasChart" height="100"></canvas>
          </div>
        </div>
      </div>

      <!-- Carga de trabajo por operativo -->
      <div class="col-md-6">
        <div class="card h-100 rounded-0"> <!-- <-- envolvemos todo en una card -->
          <div class="card-header rounded-0">
            Carga de Trabajo por Operativo
          </div>
          <div class="card-body">
            <div class="row row-cols-2 g-2 mb-3">
              <!-- Operativo 1 -->
              <div class="col">
                <div class="card text-center shadow-sm rounded-0">
                  <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-center mb-1">
                      <i class="bi bi-person-circle me-2 fs-4 text-primary"></i>
                      <span class="fw-semibold">Operativo 1</span>
                    </div>
                    <h4 class="mb-1 text-primary">6</h4>
                    <div class="progress" style="height: 6px;">
                      <div class="progress-bar bg-primary" style="width: 70%;"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Operativo 2 -->
              <div class="col">
                <div class="card text-center shadow-sm rounded-0">
                  <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-center mb-1">
                      <i class="bi bi-person-circle me-2 fs-4 text-success"></i>
                      <span class="fw-semibold">Operativo 2</span>
                    </div>
                    <h4 class="mb-1 text-success">4</h4>
                    <div class="progress" style="height: 6px;">
                      <div class="progress-bar bg-success" style="width: 50%;"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Operativo 3 -->
              <div class="col">
                <div class="card text-center shadow-sm rounded-0">
                  <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-center mb-1">
                      <i class="bi bi-person-circle me-2 fs-4 text-warning"></i>
                      <span class="fw-semibold">Operativo 3</span>
                    </div>
                    <h4 class="mb-1 text-warning">17</h4>
                    <div class="progress" style="height: 6px;">
                      <div class="progress-bar bg-warning" style="width: 40%;"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Operativo 5 -->
              <div class="col">
                <div class="card text-center shadow-sm rounded-0">
                  <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-center mb-1">
                      <i class="bi bi-person-circle me-2 fs-4 text-info"></i>
                      <span class="fw-semibold">Operativo 5</span>
                    </div>
                    <h4 class="mb-1 text-info">7</h4>
                    <div class="progress" style="height: 6px;">
                      <div class="progress-bar bg-info" style="width: 65%;"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Operativo 6 -->
              <div class="col">
                <div class="card text-center shadow-sm rounded-0">
                  <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-center mb-1">
                      <i class="bi bi-person-circle me-2 fs-4 text-secondary"></i>
                      <span class="fw-semibold">Operativo 6</span>
                    </div>
                    <h4 class="mb-1 text-secondary">5</h4>
                    <div class="progress" style="height: 6px;">
                      <div class="progress-bar bg-secondary" style="width: 30%;"></div>
                    </div>
                  </div>
                </div>
              </div>


              <!-- Operativo 4 -->
              <div class="col">
                <div class="card text-center shadow-sm rounded-0">
                  <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-center mb-1">
                      <i class="bi bi-person-circle me-2 fs-4 text-danger"></i>
                      <span class="fw-semibold">Operativo 4</span>
                    </div>
                    <h4 class="mb-1 text-danger">12</h4>
                    <div class="progress" style="height: 6px;">
                      <div class="progress-bar bg-danger" style="width: 85%;"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Límite de crédito por cliente -->
      <div class="col-12 pt-3">
        <div class="card rounded-0">
          <div class="card-header">
            Límite de Crédito por Cliente
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped align-middle">
                <thead>
                  <tr>
                    <th style="width: 25%;">Cliente</th>
                    <th style="width: 25%;">Crédito Límite</th>
                    <th style="width: 25%;">Monto Utilizado</th>
                    <th style="width: 25%;">Disponible</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Cliente C - 60% usado - sin alerta -->
                  <tr>
                    <td>Cliente A</td>
                    <td>$100,000</td>
                    <td>$40,000</td>
                    <td>$60,000</td>
                  </tr>

                  <!-- Cliente B - 90,000 de 150,000 = 60% - sin alerta -->
                  <tr class="table-warning">
                    <td>
                      <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                      Cliente B
                    </td>
                    <td>$150,000</td>
                    <td>$120,000</td>
                    <td>$30,000</td>
                  </tr>

                  <!-- Cliente C - 120,000 de 200,000 = 60% - sin alerta -->
                  <tr class="table-warning">
                    <td>
                      <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                      Cliente C
                    </td>
                    <td>$200,000</td>
                    <td>$180,000</td>
                    <td>$20,000</td>
                  </tr>

                  <!-- Cliente D - 100% gastado -->
                  <tr class="table-danger">
                    <td>
                      <i class="bi bi-x-circle-fill text-danger me-1"></i>
                      Cliente D
                    </td>
                    <td>$120,000</td>
                    <td>$120,000</td>
                    <td>$0</td>
                  </tr>

                  <!-- Cliente E - Excede límite -->
                  <tr class="table-danger">
                    <td>
                      <i class="bi bi-x-circle-fill text-danger me-1"></i>
                      Cliente E
                    </td>
                    <td>$100,000</td>
                    <td>$130,000</td>
                    <td class="text-danger">-$30,000</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
      </script>

</body>

</html>