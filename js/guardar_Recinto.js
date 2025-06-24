$(document).ready(function () {
    $("#form_Recintos").on("submit", function (e) {
        e.preventDefault();

        var formData = $(this).serialize();
        console.log(formData);

        $.ajax({
            url: '../../modulos/guardado/guardar_recinto.php',
            type: 'POST',
            data: formData,
            success: function (response) {
                console.log('Respuesta del servidor:', response);

                // Verificamos si la respuesta es el mensaje de éxito
                if (response.trim() === "Recinto guardado correctamente.") {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Guardado',
                        html: `Recinto guardado correctamente.`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });

                    // Limpiar el formulario
                    $("#form_Recintos")[0].reset();
                    $("#form_Recintos select").val(null).trigger('change');
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
