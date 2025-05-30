$(document).ready(function () {
    $("#form_Anticipos").on("submit", function (e) {
        e.preventDefault();

        const totalCargo = parseFloat(document.getElementById('total-cargo').value.replace('$', '').trim()) || 0;
        const totalAbono = parseFloat(document.getElementById('total-abono').value.replace('$', '').trim()) || 0;

        if (totalCargo !== totalAbono) {
            Swal.fire({
                icon: 'error',
                title: 'Totales incorrectos',
                text: 'El total de cargos y abonos debe ser igual para guardar la póliza.',
                confirmButtonColor: '#343E53'
            });
            return false;
        }

        var formData = $(this).serialize();
        console.log(formData);

        $.ajax({
            url: '../../modulos/guardado/guardar_anticipo.php',
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
                        title: 'Solicitud de anticipo guardada correctamente',
                        html: `<strong>Número de póliza:</strong> ${json.numero}`,
                        confirmButtonText: 'Aceptar'
                    });

                    // Limpiar el formulario
                    $("#form_Anticipos")[0].reset();
                    $('#beneficiario-select').val(null).trigger('change');
                    $('#aduana-select').val(null).trigger('change');
                    $('select.select2').val(null).trigger('change');
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
