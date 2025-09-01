$(document).ready(function () {
    // Envío del formulario por AJAX
    $('#form_Cuentas').on('submit', function (e) {
        e.preventDefault(); // Evita recarga
        // Recolectar inputs de subcuentas dinámicas
        const subcuentas = [];
        $('#tablaSubcuentas tbody tr').each(function () {
            const inputs = $(this).find('input');
            if (inputs.length > 0) {
                const numero = $(inputs[0]).val().trim();
                const nombre = $(inputs[1]).val().trim();
                const saldo = $(inputs[2]).val().trim();

                if (numero && nombre && saldo) {
                    subcuentas.push({ numero, nombre, saldo });
                }
            }
        });

        // Agregar los datos de subcuentas al formulario como campo oculto
        // Si ya existe, actualizarlo
        let $subcuentasInput = $('#form_Cuentas input[name="subcuentas_json"]');
        if ($subcuentasInput.length === 0) {
            $subcuentasInput = $('<input>').attr({
                type: 'hidden',
                name: 'subcuentas_json',
                value: JSON.stringify(subcuentas)
            });
            $('#form_Cuentas').append($subcuentasInput);
        } else {
            $subcuentasInput.val(JSON.stringify(subcuentas));
        }

        // Enviar el formulario por AJAX como antes
        $.ajax({
            url: '../../modulos/actualizar/actualizar_cuentas.php', // Ajusta si es necesario
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.trim() === 'ok') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Actualizada',
                        html: `Cuenta actualizada correctamente.`,
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
