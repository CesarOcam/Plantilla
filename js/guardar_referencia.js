$("#form_Referencia").on("submit", function (e) {
    e.preventDefault();

    var form = this;
    var formData = new FormData(form); // Recoge todos los campos del formulario incluyendo archivos del input
    console.log('Archivos en archivosCargados:', archivosCargados);
    // Ahora agregamos manualmente los archivos que tienes en tu array archivosCargados
    console.log('Archivos en archivosCargados:', archivosCargados);

    // Limpiar si acaso ya hay archivos documentos[] en formData para evitar duplicados
    // No hay método para limpiar formData, así que solo creamos uno nuevo
    var formData2 = new FormData();

    // Agregar todos los campos del formulario excepto archivos (los agregamos luego)
    for (var pair of formData.entries()) {
        if (pair[0] !== 'documentos[]') {
            formData2.append(pair[0], pair[1]);
        }
    }

    // Agregar los archivos manualmente
    archivosCargados.forEach(file => {
        formData2.append('documentos[]', file);
    });

    // Ver qué se está enviando
    for (var pair of formData2.entries()) {
        console.log(pair[0] + ': ' + (pair[1].name || pair[1]));
    }

    $.ajax({
        url: '../../modulos/guardado/guardar_referencia.php',
        type: 'POST',
        data: formData2,
        processData: false, // Muy importante para FormData
        contentType: false, // Muy importante para FormData
        dataType: 'json',
        success: function (response) {
            console.log('Respuesta del servidor:', response);

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
                $('#recinto-select').val(null).trigger('change');
                $('#naviera-select').val(null).trigger('change');
                $('#buque-select').val(null).trigger('change');
                $('#consolidadora-select').val(null).trigger('change');
                form.scrollIntoView({ behavior: 'smooth' });
            } else {
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
