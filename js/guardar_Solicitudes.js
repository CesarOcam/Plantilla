$(document).ready(function () {
    $("#form_solicitud_pago").on("submit", function (e) {
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
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Solicitud registrada',
                        html: `Nueva solicitud generada`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });

                    // Limpiar el formulario
                    $("#form_solicitud_pago")[0].reset();
                    $('#beneficiario-select').val(null).trigger('change');
                    $('#aduana-select').val(null).trigger('change');
                    $('#form_solicitud_pago').find('select.select2').val(null).trigger('change');

                    const tablaPago = document.getElementById('tabla-partidas');
                    const tbody = tablaPago.querySelector('tbody');
                    if (tbody) {
                        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Sin datos disponibles</td></tr>`;
                    }

                    const selectAduana = document.getElementById('aduana-select');
                    if (selectAduana) {
                        selectAduana.disabled = false;
                    }

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
