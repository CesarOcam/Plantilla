$(document).ready(function () {
    // Inicializar Select2
    $('#subcuentaInput').select2({
        placeholder: "Seleccione una subcuenta",
        allowClear: false
    });

    actualizarEstadoCheckboxesYBoton();
});

// Enfocar búsqueda al abrir select2
$(document).on('select2:open', () => {
    setTimeout(() => {
        const input = document.querySelector('.select2-container--open .select2-search__field');
        if (input) input.focus();
    }, 100);
});

// Inicializar flatpickr
flatpickr("#fechaDesdeInput", { dateFormat: "Y-m-d" });
flatpickr("#fechaHastaInput", { dateFormat: "Y-m-d" });

// Botón buscar
document.getElementById("btn_buscar").addEventListener("click", function () {
    const fechaDesde = document.getElementById("fechaDesdeInput").value;
    const fechaHasta = document.getElementById("fechaHastaInput").value;
    const subcuenta = document.getElementById("subcuentaInput").value;

    const params = new URLSearchParams({ fecha_desde: fechaDesde, fecha_hasta: fechaHasta, subcuenta });

    const xhr = new XMLHttpRequest();
    xhr.open("GET", "../../modulos/consultas/tabla_facturas_pp.php?" + params.toString(), true);
    xhr.onload = function () {
        if (this.status === 200) {
            document.getElementById("tabla-pp-wrapper").innerHTML = this.responseText;

            // Actualizar estados después de que la tabla cargue
            setTimeout(() => {
                actualizarEstadoCheckboxesYBoton();
            }, 50);
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

    // Cargar tabla inicial
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "../../modulos/consultas/tabla_facturas_cuentas.php", true);
    xhr.onload = function () {
        if (this.status === 200) {
            document.getElementById("tabla-pp-wrapper").innerHTML = this.responseText;
            setTimeout(() => {
                actualizarEstadoCheckboxesYBoton();
                actualizarTotalCargo();
            }, 50);
        }
    };
    xhr.send();
});

// Listener para todos los cambios de checkboxes
document.addEventListener('change', function (e) {
    const checkboxes = document.querySelectorAll('.chk-registro');

    if (e.target && e.target.id === 'select-all') {
        // Marcar/desmarcar todos
        checkboxes.forEach(cb => {
            if (!cb.disabled) cb.checked = e.target.checked;
        });

        // Imprimir el estado de cada checkbox
        checkboxes.forEach(cb => {
            const id = cb.getAttribute('data-id');
            const ultimaSubcuenta = cb.getAttribute('data-ultimasubcuenta');
            console.log('Checkbox id:', id, 'Checked:', cb.checked, 'Última Subcuenta:', ultimaSubcuenta);
        });

        // Actualizar totales y botón
        actualizarTotalCargo();
        actualizarEstadoCheckboxesYBoton();
        return; // Salimos para no ejecutar la parte de checkbox individual
    }

    // Checkbox individual
    if (e.target && e.target.classList.contains('chk-registro')) {
        const id = e.target.getAttribute('data-id');
        const ultimaSubcuenta = e.target.getAttribute('data-ultimasubcuenta');
        console.log('Checkbox id:', id, 'Checked:', e.target.checked, 'Última Subcuenta:', ultimaSubcuenta);
    }

    actualizarEstadoCheckboxesYBoton();
    actualizarTotalCargo();
});



// Función principal para actualizar estados
function actualizarEstadoCheckboxesYBoton() {
    const subcuentaSeleccionada = document.getElementById('subcuentaInput').value !== '';
    const checkboxes = document.querySelectorAll('.chk-registro');
    const btnPagar = document.getElementById('btn_pagar');

    // Habilitar/deshabilitar checkboxes
    checkboxes.forEach(chk => {
        chk.disabled = !subcuentaSeleccionada;
        if (!subcuentaSeleccionada) chk.checked = false;
    });

    // Botón pagar
    const checkboxesSeleccionados = document.querySelectorAll('.chk-registro:checked');
    btnPagar.disabled = !(subcuentaSeleccionada && checkboxesSeleccionados.length > 0);

    // Checkbox "select all"
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        const allChecked = Array.from(checkboxes).every(cb => cb.checked || cb.disabled);
        selectAllCheckbox.checked = allChecked;
    }
}

// Calcular total de cargos
function actualizarTotalCargo() {
    const checkboxesSeleccionados = document.querySelectorAll('.chk-registro:checked');
    const tbody = document.querySelector('table tbody');
    const filaExistente = document.getElementById('filaTotalCargo');

    if (filaExistente) filaExistente.remove();
    if (checkboxesSeleccionados.length === 0) return;

    let total = 0;
    checkboxesSeleccionados.forEach(chk => {
        const cargo = parseFloat(chk.getAttribute('data-cargo'));
        if (!isNaN(cargo)) total += cargo;
    });

    const filaTotal = document.createElement('tr');
    filaTotal.id = 'filaTotalCargo';
    filaTotal.innerHTML = `
        <td colspan="6" class="text-end fw-bold text-end">TOTAL:</td>
        <td class="fw-bold">$ ${total.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td colspan="2"></td>
    `;
    tbody.appendChild(filaTotal);
}

// Botón pagar
document.getElementById("btn_pagar").addEventListener("click", function () {
    const checkboxesSeleccionados = document.querySelectorAll('.chk-registro:checked');

    let total = 0;
    let ids = [];
    let ultimaSubcuenta = '';

    checkboxesSeleccionados.forEach(chk => {
        const id = chk.getAttribute('data-id') || chk.value;
        const cargo = parseFloat(chk.getAttribute('data-cargo'));
        if (!isNaN(cargo)) total += cargo;
        ids.push(id);
        ultimaSubcuenta = chk.getAttribute('data-ultimasubcuenta');
    });

    // Rellenar inputs ocultos
    document.getElementById("inputIds").value = ids.join(',');
    document.getElementById("inputTotal").value = total.toFixed(2);
    document.getElementById("inputUltimasSubcuentas").value = ultimaSubcuenta;

    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalPago'));
    modal.show();
});

// Inicializar flatpickr de fecha con hora
flatpickr("#Fecha", {
    enableTime: true,
    time_24hr: true,
    enableSeconds: true,
    dateFormat: "Y-m-d H:i:s",
    defaultDate: new Date()
});

document.getElementById('Fecha').value = document.getElementById('Fecha')._flatpickr.input.value;

// Inicializar select2 dentro del modal
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById("selectCuentaContable")) {
        $('#selectBeneficiario').select2({
            width: '100%',
            placeholder: "Selecciona un beneficiario",
            allowClear: false,
            dropdownParent: $('#modalPago')
        });
        $('#selectCuentaContable').select2({
            width: '100%',
            placeholder: "Selecciona una cuenta",
            allowClear: false,
            dropdownParent: $('#modalPago')
        });
    }
});
