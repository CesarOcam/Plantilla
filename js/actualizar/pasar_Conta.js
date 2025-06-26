$('#btn_actualizar').on('click', function () {
    const id = $(this).data('id');
    console.log('[DEBUG] ID obtenido del botón:', id);

    if (!id) {
        console.error('[ERROR] No se encontró un ID válido.');
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró un ID válido para enviar.'
        });
        return;
    }

    $.ajax({
        url: '../../modulos/actualizar/pasar_conta.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json', // <- Esto asegura que jQuery ya te entrega un objeto JS
        success: function (data) {
            console.log('[DEBUG] Respuesta JSON del servidor:', data);

            if (data.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Status actualizado',
                    html: `La referencia pasó a contabilidad`,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                const botonAnterior = document.getElementById('btn_actualizar');
                if (botonAnterior) {
                    const nuevoBoton = document.createElement('button');
                    nuevoBoton.type = 'button';
                    nuevoBoton.className = 'btn btn-outline-secondary rounded-0';
                    nuevoBoton.id = 'btn_kardex';
                    nuevoBoton.dataset.id = data.id;
                    nuevoBoton.innerHTML = `<i class="fas fa-dolly me-2"></i> Afectar Kardex`;

                    // Reemplazar el botón anterior por el nuevo
                    botonAnterior.parentNode.replaceChild(nuevoBoton, botonAnterior);

                    const statusInput = document.getElementById('status');
                    if (statusInput) {
                        statusInput.value = 'EN CONTABILIDAD';
                    }

                    // Opcional: agregar evento al nuevo botón
                    nuevoBoton.addEventListener('click', function () {
                        // Lógica para afectar el kardex
                        console.log('Afectando Kardex con ID:', this.dataset.id);
                    });
                }


            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Ocurrió un error',
                });
            }
        },
        error: function (xhr, status, error) {
            console.error('[ERROR AJAX] Estado:', status, 'Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de red',
                text: 'No se pudo conectar con el servidor.'
            });
        }
    });
});
