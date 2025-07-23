
$(document).ready(function () {
    $('#subcuentaInput').select2({
        placeholder: "Seleccione una subcuenta",
        allowClear: false
    });

    actualizarEstadoCheckboxesYBoton();
});

$(document).on('select2:open', () => {
    setTimeout(() => {
        const input = document.querySelector('.select2-container--open .select2-search__field');
        if (input) input.focus();
    }, 100);
});

flatpickr("#fechaDesdeInput", {
    dateFormat: "Y-m-d"
});
flatpickr("#fechaHastaInput", {
    dateFormat: "Y-m-d"
});

document.getElementById("btn_buscar").addEventListener("click", function () {
    const fechaDesde = document.getElementById("fechaDesdeInput").value;
    const fechaHasta = document.getElementById("fechaHastaInput").value;
    const subcuenta = document.getElementById("subcuentaInput").value;

    const params = new URLSearchParams({
        fecha_desde: fechaDesde,
        fecha_hasta: fechaHasta,
        subcuenta
    });

    console.log(Object.fromEntries(params.entries()));

    const xhr = new XMLHttpRequest();
    xhr.open("GET", "../../modulos/consultas/tabla_facturas_pp.php?" + params.toString(), true);
    xhr.onload = function () {
        if (this.status === 200) {
            document.getElementById("tabla-pp-container").innerHTML = this.responseText;

            // Esperamos a que los nuevos checkboxes se hayan agregado al DOM
            setTimeout(() => {
                actualizarEstadoCheckboxesYBoton();
            }, 100);
        }
    };
    xhr.send();
});

// Limpiar filtros
document.getElementById("btn_limpiar").addEventListener("click", function () {
    document.getElementById("fechaDesdeInput").value = "";
    document.getElementById("fechaHastaInput").value = "";

    const subcuentaSelect = document.getElementById("subcuentaInput");
    subcuentaSelect.value = ""; 
    subcuentaSelect.dispatchEvent(new Event('change'));
    document.getElementById("btn_buscar").click();
});

document.addEventListener('change', function (e) {
    if (e.target && e.target.classList.contains('chk-registro')) {
        // Puedes obtenerlo desde data-id o value
        const id = e.target.getAttribute('data-id') || e.target.value;
        console.log('Checkbox id:', id, 'Checked:', e.target.checked);
    }

    actualizarEstadoCheckboxesYBoton();
    actualizarTotalCargo();
});

function actualizarEstadoCheckboxesYBoton() {
    const subcuentaSeleccionada = document.getElementById('subcuentaInput').value !== '';
    const checkboxes = document.querySelectorAll('.chk-registro');
    const btnPagar = document.getElementById('btn_pagar');

    // Habilitar/deshabilitar los checkboxes
    checkboxes.forEach(chk => {
        chk.disabled = !subcuentaSeleccionada;
        if (!subcuentaSeleccionada) {
            chk.checked = false;
        }
    });

    // Habilitar/deshabilitar el botón pagar según checkboxes seleccionados
    const checkboxesSeleccionados = document.querySelectorAll('.chk-registro:checked');
    btnPagar.disabled = !(subcuentaSeleccionada && checkboxesSeleccionados.length > 0);
}

function actualizarTotalCargo() {
    const checkboxesSeleccionados = document.querySelectorAll('.chk-registro:checked');
    const tbody = document.querySelector('table tbody');
    const filaExistente = document.getElementById('filaTotalCargo');

     if (filaExistente) {
        filaExistente.remove();
    }

    if (checkboxesSeleccionados.length === 0) {
        return;
    }

    // Calcular total
    let total = 0;
    checkboxesSeleccionados.forEach(chk => {
        const cargo = parseFloat(chk.getAttribute('data-cargo'));
        if (!isNaN(cargo)) {
            total += cargo;
        }
    });

    // Crear nueva fila
    const filaTotal = document.createElement('tr');
    filaTotal.id = 'filaTotalCargo';
    filaTotal.innerHTML = `
        <td colspan="6" class="text-end fw-bold text-end">TOTAL:</td>
        <td class="fw-bold">$ ${total.toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}</td>
        <td colspan="2"></td>
    `;

    tbody.appendChild(filaTotal);
}

document.getElementById("btn_pagar").addEventListener("click", function () {
    const checkboxesSeleccionados = document.querySelectorAll('.chk-registro:checked');

    let total = 0;
    let ids = [];

    checkboxesSeleccionados.forEach(chk => {
        const id = chk.getAttribute('data-id') || chk.value;
        const cargo = parseFloat(chk.getAttribute('data-cargo'));

        if (!isNaN(cargo)) {
            total += cargo;
        }

        ids.push(id);
    });

    console.log(`Cuentas pagadas: ${ids.join(', ')}`);
    console.log(`Total: $ ${total.toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`);

    // Rellenar inputs ocultos
    document.getElementById("inputIds").value = ids.join(',');
    document.getElementById("inputTotal").value = total.toFixed(2);

    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('modalPago'));
    modal.show();
});

flatpickr("#Fecha", {
    enableTime: true,
    time_24hr: true,
    enableSeconds: true,
    dateFormat: "Y-m-d H:i:s",
    defaultDate: new Date()
});

document.getElementById('Fecha').value = document.getElementById('Fecha')._flatpickr.input.value;

document.addEventListener('DOMContentLoaded', function () {
    const selectCuenta = document.getElementById("selectCuentaContable");
    if (selectCuenta) {
        $('#selectBeneficiario').select2({
            width: '100%',
            placeholder: "Selecciona una beneficiario",
            allowClear: false,
            dropdownParent: $('#modalPago')
        });
    }
    if (selectCuenta) {
        $('#selectCuentaContable').select2({
            width: '100%',
            placeholder: "Selecciona una cuenta",
            allowClear: false,
            dropdownParent: $('#modalPago')
        });
    }

});

