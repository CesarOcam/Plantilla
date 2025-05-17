<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.css">

    <link rel="stylesheet" href="../../../css/style.css">
</head>

    <?php   
        include($_SERVER['DOCUMENT_ROOT'] . '/portal_web/proyecto_2/php/vistas/navbar.php');
    ?>

    <div class="container-fluid">
        <div class="card mt-3 border shadow rounded-0">
            <div class="card-header">
                <div class="d-flex flex-column mb-3">
                    <div class="row w-100">
                        <div class="col-12 col-sm-10 d-flex align-items-center">
                            <input type="text" class="form-control w-100 rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Filtrar consolidadora por nombre" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                        </div>
                        <div class="col-12 col-sm-2 d-flex align-items-center justify-content-start justify-content-sm-end mt-2 mt-sm-0">
                            <a href="/portal_web/proyecto_2/php/vistas/formularios/form_consolidadoras.php" style="text-decoration: none; color: black;"><h6><i class="fas fa-plus mt-2"></i></h6></a>
                            <span class="mx-2"><h5>|</h5></span>

                            <!-- Interruptor de modo oscuro / claro -->
                            <label class="switch mt-2">
                                <input type="checkbox" id="modeToggle">
                                <span class="slider"></span>
                            </label>

                            <p class="mb-0 ms-2 mt-2">Mostrar inactivos</p>
                        </div>
                    </div>
                </div>

                <?php
                    include('../../modulos/consultas_cat/tabla_consolidadoras.php');
                ?>
            </div>

            <div class="card-body">

            </div>
        </div>
    </div>

<script>
  // Obtener el interruptor
  const modeToggle = document.getElementById('modeToggle');

  // Cambiar el modo cuando se haga clic en el interruptor
  modeToggle.addEventListener('change', function() {
    if (modeToggle.checked) {
      document.body.classList.add('dark-mode');
    } else {
      document.body.classList.remove('dark-mode');
    }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>

</body>
</html>