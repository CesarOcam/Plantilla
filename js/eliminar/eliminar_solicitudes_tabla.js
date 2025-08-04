function initBotonesEliminar() {
  const contenedorTabla = document.getElementById('tabla-aduanas-container');
  
  contenedorTabla.addEventListener('click', function (e) {
    const btn = e.target.closest('button.btn-trash');
    if (!btn) return; // No es botón eliminar, ignorar

    e.preventDefault();

    const id = btn.getAttribute('data-id');
    console.log('ID capturado:', id); // Debug

    Swal.fire({
      title: '¿Estás seguro de cancelar la solicitud?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Cancelar solicitud',
      cancelButtonText: 'Salir'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('../../modulos/eliminar/eliminar_solicitud.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        })
          .then(res => res.json())
          .then(data => {
            console.log('Respuesta:', data); // Debug

            if (data.success) {
              Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Solicitud cancelada',
                html: `Se canceló la solicitud.`,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
              });

              // Elimina la fila sin esperar a que el toast desaparezca
              btn.closest('tr').remove();

              // Limpiar el formulario form_Pago
              const formPago = document.getElementById('form_Pago');
              if (formPago) {
                formPago.reset();
              }

              // Limpiar la tabla tabla-partidas (vaciar tbody o colocar mensaje)
              const tablaPago = document.getElementById('tabla-partidas');
              if (tablaPago) {
                const tbody = tablaPago.querySelector('tbody');
                if (tbody) {
                  tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Sin datos disponibles</td></tr>`;
                }
              }
            } else {
              Swal.fire('Error', data.message || 'Error al desactivar.', 'error');
            }
          })
          .catch(err => {
            console.error('Error:', err);
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
          });
      }
    });
  });
}
