$("#form_Referencia").on("submit", function (e) {
    e.preventDefault();

    const referenciaDirecta = $('#input-referencia').val().trim();
    console.log(referenciaDirecta);
    if (referenciaDirecta !== '') {
        $.ajax({
            url: '../../modulos/consultas/obtener_referencia.php',
            method: 'POST',
            data: { numeroReferencia: referenciaDirecta },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.id) {
                    window.location.href = `../../modulos/consultas/detalle_referencia.php?id=${response.id}`;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Referencia no encontrada',
                        text: 'El número de referencia no existe.',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo verificar la referencia, intenta más tarde.',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
        return;
    }

    var form = this;
    var formData = new FormData(form);
    console.log('Archivos en archivosCargados:', archivosCargados);
    console.log('Archivos en archivosCargados:', archivosCargados);
    var formData2 = new FormData();

    for (var pair of formData.entries()) {
        if (pair[0] !== 'documentos[]') {
            formData2.append(pair[0], pair[1]);
        }
    }

    archivosCargados.forEach(file => {
        formData2.append('documentos[]', file);
    });

    for (var pair of formData2.entries()) {
        console.log(pair[0] + ': ' + (pair[1].name || pair[1]));
    }

    $.ajax({
        url: '../../modulos/guardado/guardar_referencia.php',
        type: 'POST',
        data: formData2,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (response) {
            console.log('Respuesta del servidor:', response);
            console.log(document.getElementById('cierre_doc').value);


            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Referencia guardada correctamente',
                    html: `<strong>Número generado:</strong> ${response.numero}`,
                    confirmButtonText: 'Aceptar'
                });

                form.reset();
                archivosCargados = [];
                previewContainer.innerHTML = '';
                previewContainer.classList.add('d-none');
                dropZoneDefault.classList.remove('d-none');
                // Limpiar los select2
                $('#aduana-select').val(null).trigger('change');
                $('#exportador-select').val(null).trigger('change');
                $('#logistico-select').val(null).trigger('change');
                $('#clave-select').val(null).trigger('change');
                $('#recinto-select').val(null).trigger('change');
                $('#naviera-select').val(null).trigger('change');
                $('#buque-select').val(null).trigger('change');
                $('#consolidadora-select').val(null).trigger('change');
                form.scrollIntoView({ behavior: 'smooth' });
            } else {
                console.error("Error en la solicitud Ajax:", xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: response.mensaje || 'Ocurrió un error al guardar la referencia.',
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
