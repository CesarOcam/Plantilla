$(document).ready(function () {
    $("#form_Clientes").on("submit", function (e) {
        e.preventDefault();

        var formData = $(this).serialize();
        console.log(formData);

        $.ajax({
            url: '../../modulos/guardado/guardar_cliente.php',
            type: 'POST',
            data: formData,
            dataType: 'json', // IMPORTANTE: para que jQuery haga el parse automático
            success: function (data) {
                console.log('Respuesta del servidor:', data);

                if (data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Guardado',
                        html: data.message,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });

                    $("#form_Clientes")[0].reset();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: data.message,
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
