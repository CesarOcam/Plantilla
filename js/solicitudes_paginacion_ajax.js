// archivo: solicitudes_paginacion_ajax.js

document.addEventListener('DOMContentLoaded', function () {
    // Inicializa eventos para los botones "Aceptar"
    function initBotonesAceptar() {
        const botonesAceptar = document.querySelectorAll('.btn-aceptar');

        botonesAceptar.forEach(boton => {
            boton.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');

                // Ejemplo de lógica al hacer clic (ajusta según tu implementación)
                console.log(`Seleccionado: ID=${id}, Nombre=${nombre}`);

                // Puedes setear los valores en inputs ocultos o variables globales
                document.getElementById('input-id-solicitud').value = id;
                document.getElementById('input-nombre-solicitud').value = nombre;

                // Cierra el modal (si usas Bootstrap 5)
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalSolicitudes'));
                modal.hide();
            });
        });
    }

    // Carga contenido de una página específica por AJAX
    function cargarPaginaSolicitudes(pagina) {
        const contenedor = document.getElementById('tabla-aduanas-container');

        fetch(`../../modulos/consultas_traf/tabla_solicitudes.php?pagina=${pagina}`)
            .then(response => response.text())
            .then(html => {
                contenedor.innerHTML = html;

                // Re-inicializa eventos y plugins si es necesario
                initBotonesAceptar();

                // Tooltips Bootstrap
                setTimeout(() => {
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                }, 0);

                // Si usas Select2 dentro del contenido recargado, reactivarlo
                if (window.jQuery && $('.select-subcuenta').length > 0) {
                    $('.select-subcuenta').select2({ width: '100%' });
                }
            })
            .catch(err => {
                console.error("Error cargando solicitudes por AJAX:", err);
            });
    }

    // Delegación de eventos para paginación
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-page')) {
            e.preventDefault();
            const pagina = e.target.getAttribute('data-pagina');
            cargarPaginaSolicitudes(pagina);
        }
    });

    // Inicialización al cargar
    initBotonesAceptar();
});
