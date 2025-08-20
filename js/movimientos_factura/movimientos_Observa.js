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

    $.ajax({
        url: '../../modulos/actualizar/actualizar_subcuentaObs.php',
        type: 'POST',
        data: {
            obsPartidaId: partidaId,
            Observaciones: observaciones
        },
        success: function (res) {
            if (res.status === 'ok') {
                $('#modalObservaciones').modal('hide');

                const $btn = $('button.obs-edit[data-partida-id="' + partidaId + '"]');
                $btn.attr('data-observaciones', observaciones);

                const $span = $btn.find('.observaciones-text');

                if (observaciones.trim() === '') {
                    $span.text('');
                    if ($btn.find('i.bi-pencil-square').length === 0) {
                        $btn.append('<i class="bi bi-pencil-square ms-1 text-secondary" style="font-size: 1.2rem;"></i>');
                    }
                } else {
                    $span.text(observaciones);
                    $btn.find('i.bi-pencil-square').remove();
                }

                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
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
