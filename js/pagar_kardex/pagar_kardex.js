document.addEventListener('DOMContentLoaded', function () {
    const btnPagar = document.getElementById('btn_pagar');

    // Iniciar el botón como deshabilitado
    btnPagar.disabled = true;

    // Escuchar cambios en los checkboxes
    document.querySelectorAll('.kardex-checkbox').forEach(cb => {
        cb.addEventListener('change', actualizarTotalSaldoSeleccionado);
    });

    // Mostrar modal solo si hay seleccionados
    btnPagar.addEventListener('click', function () {
        const checkboxes = document.querySelectorAll('.kardex-checkbox:checked');

        if (checkboxes.length === 0) {
            console.log("No se seleccionó ningún Kardex");
            return;
        }

        const idsSeleccionados = Array.from(checkboxes).map(cb => cb.value);
        const idsParam = idsSeleccionados.join(',');

        const modal = new bootstrap.Modal(document.getElementById('modalPago'));
        modal.show();
    });
});

// Función para actualizar el total y controlar estado del botón
function actualizarTotalSaldoSeleccionado() {
    let totalSaldo = 0;
    const checkboxes = document.querySelectorAll('.kardex-checkbox:checked');
    const btnPagar = document.getElementById('btn_pagar');

    // Controlar estado del botón
    btnPagar.disabled = (checkboxes.length === 0);

    checkboxes.forEach(cb => {
        const row = cb.closest('tr');
        const saldoStr = row.querySelector('td:last-child').textContent.replace(/[^0-9.-]+/g, '');
        const saldo = parseFloat(saldoStr);
        if (!isNaN(saldo)) {
            totalSaldo += saldo;
        }
    });

    console.log('Total dinámico de saldos:', totalSaldo.toFixed(2));

    // Eliminar fila anterior si ya existe
    const tbody = document.querySelector('table tbody');
    const filaTotalAnterior = document.getElementById('fila-total-saldo');
    if (filaTotalAnterior) {
        filaTotalAnterior.remove();
    }

    // Agregar fila de total
    if (checkboxes.length > 0) {
        const tr = document.createElement('tr');
        tr.id = 'fila-total-saldo';

        for (let i = 0; i < 9; i++) {
            tr.appendChild(document.createElement('td'));
        }

        const tdTotal = document.createElement('td');
        tdTotal.colSpan = 2;
        tdTotal.innerHTML = `<strong>Total: $${totalSaldo.toFixed(2)}</strong>`;
        tdTotal.style.textAlign = 'right';
        tr.appendChild(tdTotal);

        tbody.appendChild(tr);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formPago');

    form.addEventListener('submit', function (e) {
        e.preventDefault(); // Previene envío tradicional

        const beneficiarioId = document.getElementById('selectBeneficiario').value;
        const cuentaId = document.getElementById('selectCuentaContable').value;

        // Obtener IDs de los checkboxes seleccionados
        const checkboxes = document.querySelectorAll('.kardex-checkbox:checked');
        const idsSeleccionados = Array.from(checkboxes).map(cb => cb.value);
        const idsParam = idsSeleccionados.join(',');

        // Calcular total
        let total = 0;
        checkboxes.forEach(cb => {
            const row = cb.closest('tr');
            const saldoStr = row.querySelector('td:last-child').textContent.replace(/[^0-9.-]+/g, '');
            const saldo = parseFloat(saldoStr);
            if (!isNaN(saldo)) total += saldo;
        });

        // Asignar valores a inputs ocultos
        document.getElementById('inputIds').value = idsParam;
        document.getElementById('inputTotal').value = total.toFixed(2);

        // Enviar datos por POST a donde tú necesites
        // Ejemplo con AJAX:
        const formData = new FormData(form);
        console.log(formData.get('fecha'));

        fetch('../../modulos/pagar_kardex/pagar_kardex.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(respuesta => {
                console.log('Respuesta del servidor:', respuesta);

                if (respuesta.success) {
                    Swal.fire({
                        title: 'Cuentas pagadas correctamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload(); // recargar después de cerrar
                    });

                } else {
                    Swal.fire({
                        title: 'Error',
                        text: respuesta.mensaje,
                        icon: 'error',
                        confirmButtonText: 'Cerrar'
                    });
                }
            })
            .catch(error => {
                console.error('Error al enviar el formulario:', error);
                Swal.fire({
                    title: 'Error inesperado',
                    text: 'Verifica tu conexión o intenta más tarde.',
                    icon: 'error',
                    confirmButtonText: 'Cerrar'
                });
            });


    });
});
