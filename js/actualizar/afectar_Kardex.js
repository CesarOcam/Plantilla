// Esto se queda activo siempre, aunque el botón sea reemplazado
$(document).on('click', '#btn_kardex', function () {
    var id = $(this).data('id');
    console.log('[DEBUG] Click en btn_kardex con ID:', id);

    Swal.fire({
        title: 'Procesando...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: '../../modulos/actualizar/afectar_kardex.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',

        success: function (data) {
            // Sumar saldos de la tabla
            Swal.close(); // Oculta el spinner
            
            if (data.success) {
                // Ejemplo: construir un texto con los datos
                let html = '<ul>';
                for (const key in data.data) {
                    if (data.data.hasOwnProperty(key)) {
                        html += `<li><strong>${key}:</strong> ${data.data[key]}</li>`;
                    }
                }
                html += '</ul>';

                // Muestra en SweetAlert
                Swal.fire({
                    title: 'Referencia Facturada',
                    html: 'Kardex afectado correctamente',
                    icon: 'success'
                }).then(() => {
                    location.reload(); // <-- recargar al cerrar el mensaje
                });

                console.log('Referencia:', data.data.referencia);
                console.log('Cuenta1:', data.data.cuenta1);
                console.log('Cuenta2:', data.data.cuenta2);
                console.log();

                const botonAnterior = document.getElementById('btn_actualizar');
                if (botonAnterior) {
                    const nuevoBoton = document.createElement('button');
                    nuevoBoton.type = 'button';
                    nuevoBoton.className = 'btn btn-outline-secondary rounded-0';
                    nuevoBoton.id = 'btn_kardex';
                    nuevoBoton.dataset.id = data.id;
                    nuevoBoton.innerHTML = `<i class="fas fa-paper-plane me-2"></i> Envier CG a Cliente`;

                    // Reemplazar el botón anterior por el nuevo
                    botonAnterior.parentNode.replaceChild(nuevoBoton, botonAnterior);

                    const statusInput = document.getElementById('status');
                    if (statusInput) {
                        statusInput.value = 'FACTURADA';
                    }

                }

            } else {
                Swal.fire('Error', data.message, 'error');
            }
        },
        error: function (xhr, status, error) {
            Swal.close();
            console.error('Error en la petición:', error);
            console.error('Respuesta del servidor:', xhr.responseText);
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    });
});

