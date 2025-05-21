document.addEventListener('DOMContentLoaded', function () {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const btnDesactivar = document.getElementById('btnDesactivar');
    const checkboxes = document.querySelectorAll('.chkCliente');

    // Función para actualizar visibilidad del botón
    function actualizarBoton() {
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
        btnDesactivar.style.display = anyChecked ? 'inline-block' : 'none';
    }

    // Agregar listener a cada checkbox
    checkboxes.forEach(cb => {
        cb.addEventListener('change', actualizarBoton);
    });

    // Evento click botón desactivar
    btnDesactivar.addEventListener('click', () => {
        const seleccionados = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        if (seleccionados.length === 0) return;

        // Confirmar con SweetAlert
        Swal.fire({
            title: `¿Desactivar ${seleccionados.length} cliente(s)?`,
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar ids vía AJAX a PHP para desactivar
                fetch('/portal_web/Contabilidad/php/modulos/desactivar/desactivar_clientes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: seleccionados })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Desactivado',
                                text: 'Los clientes fueron desactivados correctamente.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: 'Ocurrió un error al desactivar los clientes.',
                                icon: 'error'
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            title: 'Error de conexión',
                            text: 'No se pudo comunicar con el servidor.',
                            icon: 'error'
                        });
                    });
            }
        });
    });
});
