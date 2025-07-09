$('#btn_kardex').on('click', function () {
    var id = $(this).data('id');
    console.log(id);

    // Mostrar spinner (SweetAlert2)
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
            Swal.close(); // Oculta el spinner

            if (data.success) {
                console.log('Datos obtenidos:', data.data);

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
                });

                console.log('Referencia:', data.data.referencia);
                console.log('Cuenta1:', data.data.cuenta1);
                console.log('Cuenta2:', data.data.cuenta2);

            } else {
                Swal.fire('Error', data.message, 'error');
            }
        },
        error: function (xhr, status, error) {
            Swal.close();
            console.error('Error en la petición:', error);
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    });
});

