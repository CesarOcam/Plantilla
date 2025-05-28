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
                    icon: 'success',
                    title: 'Referencia actualizada correctamente',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    recargarTablaDocumentos(); // Actualiza la tabla al aceptar el SweetAlert
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

function recargarTablaDocumentos() {
    fetch('../../modulos/consultas/obtener_documentos.php') // Endpoint que devuelva JSON con los documentos
        .then(res => res.json())
        .then(documentos => {
            const tbody = document.getElementById('tabla-documentos-body');
            tbody.innerHTML = ''; // limpiar tabla
            documentos.forEach(doc => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
          <td>${doc.icono} ${doc.nombre}</td>
          <td class="text-center">${doc.extension.toUpperCase()}</td>
          <td class="text-center">${doc.tamano}</td>
          <td class="text-center">
            <a href="${doc.ruta}" class="btn btn-sm btn-outline-success me-2" download title="Descargar">
              <i class="bi bi-download"></i> Descargar
            </a>
            <button type="button" class="btn btn-sm btn-outline-danger" data-eliminar="true" data-id="${doc.id}" data-nombre="${doc.nombre}" data-ruta="${doc.ruta}" title="Eliminar">
              Eliminar
            </button>
          </td>
        `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => console.error('Error al recargar tabla:', err));
}
