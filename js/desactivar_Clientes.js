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

        // Confirmar acción
        if (!confirm(`¿Deseas desactivar ${seleccionados.length} cliente(s)?`)) return;

        // Enviar ids vía AJAX a PHP para desactivar
        fetch('/portal_web/proyecto_2/php/modulos/desactivar/desactivar_clientes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: seleccionados })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Clientes desactivados correctamente');
                    // Recargar la página o actualizar la tabla
                    location.reload();
                } else {
                    alert('Error al desactivar clientes');
                }
            })
            .catch(() => alert('Error en la comunicación con el servidor'));
    });
});

