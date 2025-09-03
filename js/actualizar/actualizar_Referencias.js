$("#form_Referencia").on("submit", function (e) {
    e.preventDefault();

    const BTNACTUALIZAR = document.getElementById("btn_guardar");

    // Deshabilitar botón y mostrar estado
    BTNACTUALIZAR.disabled = true;
    BTNACTUALIZAR.innerText = "Guardando...";

    var form = this;
    var formData = new FormData(form);

    var formData2 = new FormData();

    // Copiar todos los campos excepto los archivos
    for (var pair of formData.entries()) {
        if (pair[0] !== 'documentos[]') {
            formData2.append(pair[0], pair[1]);
        }
    }

    // Agregar archivos cargados
    archivosCargados.forEach(file => {
        formData2.append('documentos[]', file);
    });

    // Para debug
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
                recargarTablaArchivos(); // recarga inmediata
                    archivosCargados = []; // <-- Limpiar archivos cargados
                    previewContainer.innerHTML = '';
                    previewContainer.classList.add('d-none');
                    dropZoneDefault.classList.remove('d-none');
                Swal.fire({
                    icon: 'success',
                    title: 'Actualizada',
                    html: `Referencia actualizada correctamente.`,
                    confirmButtonText: 'Aceptar'
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
        },
        complete: function () {
            // Reactivar botón si lo deseas
            BTNACTUALIZAR.disabled = false;
            BTNACTUALIZAR.innerText = "Guardar";
        }
    });
});

function recargarTablaArchivos() {
    const referenciaId = $("#ReferenciaId").val();
    console.log("ReferenciaId:", referenciaId); // <-- verifica que exista y tenga valor
    if (referenciaId) {
        $("#tabla-archivos tbody").load(
            "../../modulos/consultas/tabla_archivos_referencia.php?id=" + referenciaId,
            function (response, status, xhr) {
                if (status === "success") console.log("Tabla recargada correctamente");
                if (status === "error") console.error("Error al recargar tabla:", xhr.statusText);
            }
        );
    }
}


