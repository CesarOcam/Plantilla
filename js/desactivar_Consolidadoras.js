document.addEventListener('DOMContentLoaded', function () {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const btnDesactivar = document.getElementById('btnDesactivar');
    const checkboxes = document.querySelectorAll('.chkConsolidadora');

    // Mostrar/ocultar botón según selección
    function actualizarBoton() {
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
        btnDesactivar.style.display = anyChecked ? 'inline-block' : 'none';
    }

    // Escuchar cambios
    checkboxes.forEach(cb => {
        cb.addEventListener('change', actualizarBoton);
    });

    // Botón de desactivar
    btnDesactivar.addEventListener('click', () => {
        const seleccionados = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        if (seleccionados.length === 0) return;

        // Confirmar con SweetAlert
        Swal.fire({
            title: `¿Desactivar ${seleccionados.length} consolidadoras(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Hacer petición al servidor
                fetch('../../../php/modulos/desactivar/desactivar_consolidadoras.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: seleccionados })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Desactivado',
                            text: 'Consolidadoras desactivados correctamente.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'Ocurrió un error al desactivar las consolidadoras.',
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
