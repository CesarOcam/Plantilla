$(document).ready(function () {
    // Inicialmente, desactivamos todos los campos del formulario
    $('#form_Navieras input, #form_Navieras select').prop('disabled', true);

    // Activar los campos al hacer clic en "Modificar"
    $('#btn_editar').on('click', function () {
        $('#form_Navieras input, #form_Navieras select').prop('disabled', false);
        $(this).hide(); // Oculta el botón de modificar
        $('#btn_guardar').show(); // Muestra el botón de guardar
    });

    // Envío del formulario por AJAX
    $('#form_Navieras').on('submit', function (e) {
        e.preventDefault(); // Evita recarga
        
        const formData = $(this).serialize();
        console.log("Datos enviados:", formData); 

        $.ajax({
            url: '../../modulos/actualizar/actualizar_navieras.php', 
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.trim() === 'ok') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Actualizada',
                        html: `Naviera actualizada correctamente.`,
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
