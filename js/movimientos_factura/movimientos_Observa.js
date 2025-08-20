// --- Abrir modal Observaciones con datos de la fila ---
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.obs-edit');
    if (!btn) return;

    const partidaId = btn.getAttribute('data-partida-id');
    const observaciones = btn.getAttribute('data-observaciones') || '';

    document.getElementById('obsPartidaId').value = partidaId;
    document.getElementById('obsTexto').value = observaciones;
});

document.getElementById('btnGuardarObs').addEventListener('click', function (e) {
    e.preventDefault();

    var partidaId = $('#obsPartidaId').val();
    var observaciones = $('#obsTexto').val();

    console.log('Datos a enviar al servidor:', { partidaId, observaciones });

    $.ajax({
        url: '../../modulos/actualizar/actualizar_subcuentaObs.php',
        type: 'POST',
        data: {
            obsPartidaId: partidaId,
            Observaciones: observaciones
        },
        success: function (res) {
            console.log('Respuesta del servidor:', res);
            if (res.status === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                $('#modalObservaciones').modal('hide');

                $('button[data-partida-id="' + partidaId + '"]')
                    .attr('data-observaciones', observaciones)
                    .find('.observaciones-text')
                    .text(observaciones);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message });
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", status, error, xhr.responseText);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor' });
        }
    });
});

