document.getElementById('form_Pago').addEventListener('submit', function (event) {
    event.preventDefault(); // Evita que se envíe de inmediato

    const formData = new FormData(this);

    console.log('=== DATOS QUE SE ENVIARÁN ===');
    for (const [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }

    // Enviar con AJAX usando jQuery
    $.ajax({
        url: '../../modulos/guardado/guardar_pago.php',  // Cambia esta ruta según corresponda
        type: 'POST',
        data: formData,
        processData: false,  // Muy importante para enviar FormData sin procesar
        contentType: false,  // Muy importante para enviar FormData correctamente
        success: function (response) {
            console.log('Respuesta del servidor:', response);

            let json;
            try {
                json = JSON.parse(response);
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'La respuesta del servidor no es válida.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            if (json.success) {
                Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Pago realizado',
                        html: `<strong>Número de póliza:</strong> ${json.numero}`,
                        showConfirmButton: false,
                        timer: 5000,
                        timerProgressBar: true
                    });

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
                    const tfoot = tablaPago.querySelector('tfoot');
                    if (tfoot) {
                        tfoot.innerHTML = '';  // Vacías el contenido del tfoot
                    }
                }
                //Limpiar el modal
                $('#tabla-aduanas-container').load('../../modulos/consultas_traf/tabla_solicitudes.php');

            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: json.mensaje || 'Error desconocido',
                    confirmButtonText: 'Aceptar'
                });
            }
        },
        error: function (xhr, status, error) {
            console.error('Error en la solicitud Ajax:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error en la solicitud.',
                confirmButtonText: 'Aceptar'
            });
        }
    });
});
