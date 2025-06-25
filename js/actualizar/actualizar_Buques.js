$(document).ready(function () {
    // Inicialmente, desactivamos todos los campos del formulario
    $('#form_Buques input, #form_Buques select').prop('disabled', true);

    // Activar los campos al hacer clic en "Modificar"
    $('#btn_editar').on('click', function () {
        $('#form_Buques input, #form_Buques select').prop('disabled', false);
        $(this).hide(); // Oculta el botón de modificar
        $('#btn_guardar').show(); // Muestra el botón de guardar
    });

    // Envío del formulario por AJAX
    $('#form_Buques').on('submit', function (e) {
        e.preventDefault(); // Evita recarga

        $.ajax({
            url: '../../modulos/actualizar/actualizar_buques.php', // Ajusta esta ruta
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.trim() === 'ok') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Actualizado',
                        html: `Buque actualizado correctamente.`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al actualizar',
                        text: response,
                        confirmButtonText: 'Aceptar'
                    });
                }
            },
            error: function (xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ocurrió un error',
                    text: error,
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });
});
