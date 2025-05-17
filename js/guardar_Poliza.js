$(document).ready(function() {
    $("#form_Polizas").on("submit", function(e) {
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
            url: '../../modulos/guardado/guardar_poliza.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Respuesta del servidor:', response);

                // Verificamos si la respuesta es el mensaje de éxito
                if (response.trim() === "Poliza guardada correctamente.") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Poliza guardada correctamente',
                        //text: 'Cliente guardado correctamente.',
                        confirmButtonText: 'Aceptar'
                    });

                    // Limpiar el formulario
                    $("#form_Polizas")[0].reset();
                    $('#beneficiario-select').val(null).trigger('change');
                } else {
                    // Si la respuesta es otro mensaje, mostrarlo (puedes personalizar esto)
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: response,
                        confirmButtonText: 'Aceptar'
                    });
                }
            },
            error: function(xhr, status, error) {
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
