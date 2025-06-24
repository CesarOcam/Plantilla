$(document).ready(function () {
    $("#form_Navieras").on("submit", function (e) {
        e.preventDefault();

        var formData = $(this).serialize();
        console.log(formData);

        $.ajax({
            url: '../../modulos/guardado/guardar_naviera.php',
            type: 'POST',
            data: formData,
            success: function (response) {
                console.log('Respuesta del servidor:', response);

                // Verificamos si la respuesta es el mensaje de éxito
                if (response.trim() === "Naviera guardada correctamente.") {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Guardado',
                        html: `Naviera guardada correctamente.`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });

                    // Limpiar el formulario
                    $("#form_Navieras")[0].reset();
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
