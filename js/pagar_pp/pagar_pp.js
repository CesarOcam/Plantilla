document.getElementById("formPago").addEventListener("submit", function (e) {
    e.preventDefault(); // Previene el envío tradicional del formulario

    const form = e.target;
    const formData = new FormData(form);

    fetch('../../modulos/pagar_pp/pagar_cuentas_pp.php', {
        method: 'POST',
        body: formData
    })
        .then(resp => resp.json())
        .then(respuesta => {
            console.log('Respuesta del servidor:', respuesta);



            if (respuesta.success) {
                // Mostrar los datos adicionales en consola
                if (respuesta.datos) {
                    console.log('Datos del pago:');
                    console.log('IDs:', respuesta.datos.ids);
                    console.log('Total:', respuesta.datos.total);
                    console.log('Fecha:', respuesta.datos.fecha);
                    console.log('Beneficiario:', respuesta.datos.beneficiario);
                    console.log('Subcuenta:', respuesta.datos.subcuenta);
                }

                $('#modalPago').modal('hide');
                Swal.fire({
                  icon: 'success',
                    title: 'Pago realizado',
                    html: `<strong>Número de póliza:</strong> ${respuesta.datos.poliza}`,
                    confirmButtonText: 'Aceptar',
                    timer: 5000,
                    timerProgressBar: true
                }).then(() => {
                    location.reload();
                });

            } else {
                Swal.fire({
                    title: 'Error al pagar',
                    text: respuesta.mensaje || 'Hubo un problema al procesar el pago.',
                    icon: 'error'
                });
            }
        })
        .catch(error => {
            console.error('Error en la petición:', error);
            Swal.fire({
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor.',
                icon: 'error'
            });
        });
});
