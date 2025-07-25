$("#form_Referencia").on("submit", function (e) {
    e.preventDefault();

    var form = this;
    var formData = new FormData(form); // Todos los campos, incluidos archivos input

    // Crear un nuevo FormData para asegurarnos de añadir los archivos desde archivosCargados
    var formData2 = new FormData();

    // Copiar todos los campos del formulario excepto los archivos (para evitar duplicados)
    for (var pair of formData.entries()) {
        if (pair[0] !== 'documentos[]') {
            formData2.append(pair[0], pair[1]);
        }
    }

    // Agregar los archivos manualmente desde archivosCargados
    archivosCargados.forEach(file => {
        formData2.append('documentos[]', file);
    });

    // Para debug: mostrar qué se envía
    for (var pair of formData2.entries()) {
        console.log(pair[0] + ': ' + (pair[1].name || pair[1]));
    }

    $.ajax({
        url: '../../modulos/actualizar/actualizar_referencias.php',
        type: 'POST',
        data: formData2,
        processData: false,
        contentType: false,
        success: function (response) {
            console.log('Respuesta del servidor:', response);

            if (response.trim() === "Referencia guardada correctamente.") {
                Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Actualizada',
                        html: `Referencia actualizada correctamente.`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                });
            }
            else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: response,
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



