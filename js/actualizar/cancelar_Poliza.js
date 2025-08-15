$('#btn_cancelar_poliza').on('click', function () {
    var id = $(this).data('id');

    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción cancelará la póliza y no podrás revertirlo",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, mantener',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {

            Swal.fire({
                title: 'Cancelando póliza...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: '../../modulos/actualizar/cancelar_poliza.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function (response) {
                    Swal.close();

                    if (response.success) {
                        Swal.fire('Éxito', 'Póliza cancelada correctamente', 'success');
                        // Aquí podrías refrescar la página o actualizar la UI
                    } else {
                        Swal.fire('Error', response.message || 'Error al cancelar póliza', 'error');
                    }
                },
                error: function () {
                    Swal.close();
                    Swal.fire('Error', 'Error en la comunicación con el servidor', 'error');
                }
            });

        } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire('Cancelado', 'La póliza no se ha cancelado', 'info');
        }
    });
});
