$(document).ready(function () {
    $("#form_Polizas").on("submit", function (e) {
        e.preventDefault();

        const totalCargo = parseFloat(document.getElementById('total-cargo').value) || 0;
        const totalAbono = parseFloat(document.getElementById('total-abono').value) || 0;

        if (totalCargo !== totalAbono) {
            Swal.fire({
                icon: 'error',
                title: 'Totales incorrectos',
                text: 'El total de cargos y abonos debe ser igual para guardar la póliza.',
                confirmButtonColor: '#343E53'
            });
            return false;  // Salir y no hacer nada más
        }

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
