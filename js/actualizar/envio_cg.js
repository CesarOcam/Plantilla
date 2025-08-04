$(document).ready(function () {
    $('#btn_correo').on('click', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Enviando correo...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '../../modulos/actualizar/enviar_cg.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function (response) {
                Swal.close();
                if (response.success) {
                    Swal.fire('Éxito', 'Correo enviado correctamente.', 'success');
                } else {
                    Swal.fire('Error', response.message || 'Error al enviar el correo.', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error("Error en la petición:", error);
                console.log("Respuesta del servidor:", xhr.responseText);
                alert("Error al enviar el correo.");
            }
        });
    });
});

