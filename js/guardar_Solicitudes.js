$(document).ready(function () {
    $("#form_Polizas").on("submit", function (e) {
        e.preventDefault();

        var formData = $(this).serialize();
        console.log(formData);

        $.ajax({
            url: '../../modulos/guardado/guardar_solicitud.php',
            type: 'POST',
            data: formData,
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
                        icon: 'success',
                        title: 'Solicitud registrada correctamente',
                        confirmButtonText: 'Aceptar'
                    });

                    // Limpiar el formulario
                    $("#form_Polizas")[0].reset();
                    $('#beneficiario-select').val(null).trigger('change');
                    $('#aduana-select').val(null).trigger('change');
                    $('#form_Polizas').find('select.select2').val(null).trigger('change');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: json.mensaje,
                        confirmButtonText: 'Aceptar'
                    });
                }
            },
            error: function (xhr, status, error) {
                console.log('Error en la solicitud Ajax:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la solicitud.',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });
});
