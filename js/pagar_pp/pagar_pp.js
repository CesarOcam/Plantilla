const spinner = document.getElementById('spinnerOverlay');

document.getElementById('formPago').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    // Mostrar spinner
    spinner.classList.add('show');

    fetch('../../modulos/pagar_pp/pagar_cuentas_pp.php', {
        method: 'POST',
        body: formData
    })
    .then(resp => resp.text())
    .then(texto => {
        // Ocultar spinner
        spinner.classList.remove('show');

        console.log('Respuesta cruda del servidor:', texto);

        let respuesta;
        try {
            respuesta = JSON.parse(texto);
        } catch (e) {
            console.error('La respuesta no es un JSON válido.');
            Swal.fire({
                title: 'Error inesperado',
                html: `<pre style="text-align: left;">${texto}</pre>`,
                icon: 'error',
                width: 600
            });
            return;
        }

        console.log('Respuesta del servidor:', respuesta);

        if (respuesta.success) {
            
            if (respuesta.datos) {
                console.log('Datos del pago:', respuesta.datos);
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
        spinner.classList.remove('show');
        console.error('Error en la petición:', error);
        Swal.fire({
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor.',
            icon: 'error'
        });
    });
});