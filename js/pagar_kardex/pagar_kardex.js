// Crear instancia única del modal fuera de funciones para reusar
const modalPagoElement = document.getElementById('modalPago');
const modalPago = new bootstrap.Modal(modalPagoElement);

// Limpiar backdrop al cerrar modal para evitar overlay pegado
modalPagoElement.addEventListener('hidden.bs.modal', function () {
  const backdrops = document.getElementsByClassName('modal-backdrop');
  while (backdrops.length > 0) {
    backdrops[0].parentNode.removeChild(backdrops[0]);
  }
  document.body.classList.remove('modal-open');
});

function inicializarEventosTabla() {
  const btnPagar = document.getElementById('btn_pagar');
  if (!btnPagar) return;

  btnPagar.disabled = true;

  // Quitar y volver a agregar evento 'change' a todos los checkboxes
  document.querySelectorAll('.kardex-checkbox').forEach(cb => {
    cb.removeEventListener('change', actualizarTotalSaldoSeleccionado);
    cb.addEventListener('change', actualizarTotalSaldoSeleccionado);
  });

  // Quitar y volver a agregar evento click al botón pagar
  btnPagar.removeEventListener('click', mostrarModalPago);
  btnPagar.addEventListener('click', mostrarModalPago);
}

function mostrarModalPago() {
  const checkboxes = document.querySelectorAll('.kardex-checkbox:checked');

  if (checkboxes.length === 0) {
    console.log("No se seleccionó ningún Kardex");
    return;
  }

  // Puedes usar los ids aquí si quieres, por ejemplo para mostrar en modal o enviarlos
  const idsSeleccionados = Array.from(checkboxes).map(cb => cb.value);
  console.log('IDs seleccionados para pagar:', idsSeleccionados.join(','));

  // Mostrar modal (usar instancia creada afuera)
  modalPago.show();
}

function actualizarTotalSaldoSeleccionado() {
  let totalSaldo = 0;
  const checkboxes = document.querySelectorAll('.kardex-checkbox:checked');
  const btnPagar = document.getElementById('btn_pagar');

  btnPagar.disabled = (checkboxes.length === 0);

  checkboxes.forEach(cb => {
    const row = cb.closest('tr');
    if (!row) return;
    const saldoStr = row.querySelector('td:last-child').textContent.replace(/[^0-9.-]+/g, '');
    const saldo = parseFloat(saldoStr);
    if (!isNaN(saldo)) {
      totalSaldo += saldo;
    }
  });

  console.log('Total dinámico de saldos:', totalSaldo.toFixed(2));

  // Eliminar fila total anterior si existe
  const tbody = document.querySelector('table tbody');
  const filaTotalAnterior = document.getElementById('fila-total-saldo');
  if (filaTotalAnterior) filaTotalAnterior.remove();

  if (checkboxes.length > 0) {
    const tr = document.createElement('tr');
    tr.id = 'fila-total-saldo';

    // Celdas vacías para alinear la última columna
    for (let i = 0; i < 9; i++) {
      tr.appendChild(document.createElement('td'));
    }

    const tdTotal = document.createElement('td');
    tdTotal.colSpan = 2;
    tdTotal.style.textAlign = 'right';
    tdTotal.innerHTML = `<strong>Total: $${totalSaldo.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`;
    tr.appendChild(tdTotal);

    tbody.appendChild(tr);
  }
}

// Inicializar todo en DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
  inicializarEventosTabla();

  const form = document.getElementById('formPago');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    const beneficiarioId = document.getElementById('selectBeneficiario').value;
    const cuentaId = document.getElementById('selectCuentaContable').value;

    const checkboxes = document.querySelectorAll('.kardex-checkbox:checked');
    if (checkboxes.length === 0) {
      Swal.fire({
        title: 'Error',
        text: 'Debe seleccionar al menos un kardex para pagar',
        icon: 'warning',
        confirmButtonText: 'Cerrar'
      });
      return;
    }

    const idsSeleccionados = Array.from(checkboxes).map(cb => cb.value);
    const idsParam = idsSeleccionados.join(',');

    let total = 0;
    checkboxes.forEach(cb => {
      const row = cb.closest('tr');
      const saldoStr = row.querySelector('td:last-child').textContent.replace(/[^0-9.-]+/g, '');
      const saldo = parseFloat(saldoStr);
      if (!isNaN(saldo)) total += saldo;
    });

    document.getElementById('inputIds').value = idsParam;
    document.getElementById('inputTotal').value = total.toFixed(2);

    const formData = new FormData(form);

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
            location.reload();
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
