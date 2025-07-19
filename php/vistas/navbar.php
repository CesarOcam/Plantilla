<?php
include_once __DIR__ . '/../../config.php'; // Ajusta la ruta si el navbar está en otra carpeta
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<?php include_once __DIR__ . '/../../config.php'; ?>
<nav class="navbar bg-body-secondary">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <a class="navbar-brand m-1 d-flex align-items-center" href="<?php echo $base_url; ?>/php/vistas/inicio.php">
      <img src="<?php echo $base_url; ?>/img/logo2.png" alt="Logo" class="img-fluid me-2" style="max-width: 40px;">
      SISTEMA DE CONTABILIDAD
    </a>

    <!-- Menú desplegable de usuario -->
    <div class="dropdown me-3">
      <button class="btn btn-dark dropdown-toggle d-flex align-items-center rounded-0" type="button"
        id="usuarioDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-person-circle me-2"></i>
        <?php echo $_SESSION['usuario_nombre'] ?? 'Usuario'; ?>
      </button>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="usuarioDropdown">
        <li>
          <a class="dropdown-item" href="<?php echo $base_url; ?>/php/modulos/config_usuario.php">
            <i class="bi bi-gear me-2"></i>Configuraciones
          </a>
        </li>
        <li>
          <hr class="dropdown-divider">
        </li>
        <li>
          <a class="dropdown-item text-danger" href="<?php echo $base_url; ?>/php/modulos/logout.php">
            <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>


<nav class="navbar bg-body-light navbar-expand-lg shadow-bottom">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse small" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item position-relative">
          <a class="nav-link dropdown-toggle" href="trafico" role="button" data-bs-toggle="dropdown"
            aria-expanded="true">Tráfico</a>
          <ul class="dropdown-menu border shadow">
            <li><a class="dropdown-item small"
                href="<?php echo $base_url; ?>/php/vistas/formularios/form_referencias.php">Referencias</a></li>
            <li><a class="dropdown-item small"
                href="<?php echo $base_url; ?>/php/vistas/trafico/solicitud_pago.php">Solicitud de Pago</a></li>
            <li><a class="dropdown-item small"
                href="<?php echo $base_url; ?>/php/vistas/trafico/realizar_pago.php">Realizar Pago</a></li>
            <li><a class="dropdown-item small"
                href="<?php echo $base_url; ?>/php/vistas/trafico/solicitud_anticipo.php">Solicitud de Anticipo</a></li>
            <hr>
            <li><a class="dropdown-item small"
                href="<?php echo $base_url; ?>/php/vistas/trafico/registro_cuota.php">Registro Cuota</a></li>
            <li><a class="dropdown-item small"
                href="<?php echo $base_url; ?>/php/vistas/trafico/registro_factura.php">Registro Facturas</a></li>
            <hr>
            <li><a class="dropdown-item small"
                href="<?php echo $base_url; ?>/php/vistas/formularios/form_polizas.php">Generar Póliza</a></li>
          </ul>
        </li>
        <li class="nav-item position-relative">
          <a class="nav-link dropdown-toggle" href="cat" role="button" data-bs-toggle="dropdown"
            aria-expanded="true">Catálogos</a>
          <ul class="dropdown-menu border shadow">
            <li><a class="dropdown-item small"
                href="<?php echo $base_url; ?>/php/vistas/catalogos/cat_Clientes.php">Clientes</a></li>
            <li><a class="dropdown-item small"
                href="<?php echo url('/php/vistas/catalogos/cat_Buques.php'); ?>">Buques</a></li>
            <li><a class="dropdown-item small"
                href="<?php echo url('/php/vistas/catalogos/cat_Beneficiarios.php'); ?>">Beneficiarios</a></li>
            <li><a class="dropdown-item small"
                href="<?php echo url('/php/vistas/catalogos/cat_Consolidadoras.php'); ?>">Consolidadoras</a></li>
            <li><a class="dropdown-item small"
                href="<?php echo url('/php/vistas/catalogos/cat_Cuentas.php'); ?>">Cuentas Contables</a></li>
            <li><a class="dropdown-item small"
                href="<?php echo url('/php/vistas/catalogos/cat_Navieras.php'); ?>">Navieras</a></li>
            <li><a class="dropdown-item small"
                href="<?php echo url('/php/vistas/catalogos/cat_Recintos.php'); ?>">Recintos</a></li>
            <hr>
            <li><a class="dropdown-item small"
                href="<?php echo url('/php/vistas/catalogos/cat_Aduanas.php'); ?>">Aduanas</a></li>
          </ul>
        </li>
        <li class="nav-item position-relative">
          <a class="nav-link dropdown-toggle" href="cons" role="button" data-bs-toggle="dropdown"
            aria-expanded="false">Consultas</a>
          <ul class="dropdown-menu border shadow">
            <li><a class="dropdown-item small"
                href="<?php echo url('/php/vistas/consultas/consulta_poliza.php'); ?>">Pólizas</a></li>
            <li><a class="dropdown-item small"
                href="<?php echo url('/php/vistas/consultas/consulta_referencia.php'); ?>">Referencias</a></li>
            <li><a class="dropdown-item small" href="<?php echo url('/php/vistas/consultas/consulta_kardex.php'); ?>">Kardex</a></li>
          </ul>
        </li>
        <li class="nav-item position-relative">
          <a class="nav-link dropdown-toggle" href="repo" role="button" data-bs-toggle="dropdown"
            aria-expanded="false">Reportes</a>
          <ul class="dropdown-menu border shadow">
            <li><a class="dropdown-item small" href="<?php echo url('/php/vistas/reportes/reporte_cuentas.php'); ?>">Reportes de Cuentas</a></li>
            <li><a class="dropdown-item small"href="#">Reporte a COI</a></li>
            <li><a class="dropdown-item small" href="<?php echo url('/php/vistas/reportes/facturas_por_pagar.php'); ?>">Reporte a Facturas por Pagar</a></li>
            <li><a class="dropdown-item small" href="#">Reporte de Estado de Resultados</a></li>
            <li><a class="dropdown-item small" href="#">Reporte de Estado de Balance General</a></li>
          </ul>
        </li>
        <li class="nav-item position-relative">
          <a class="nav-link dropdown-toggle" href="seg" role="button" data-bs-toggle="dropdown"
            aria-expanded="false">Seguridad</a>
          <ul class="dropdown-menu border shadow">
            <li><a class="dropdown-item small" href="<?php echo url('/php/vistas/seguridad/usuarios.php'); ?>">Usuarios</a></li>
          </ul>
        </li>
      </ul>
    </div>

  </div>
</nav>