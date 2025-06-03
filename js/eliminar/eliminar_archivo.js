document.getElementById('tabla-documentos-body').addEventListener('click', (e) => {

    const btn = e.target.closest('button[data-eliminar="true"], button.btn-eliminar');
    if (!btn) return;

    const ruta = btn.getAttribute('data-ruta');
    const nombre = btn.getAttribute('data-nombre');
    const id = btn.getAttribute('data-id');
    console.log('Ruta:', ruta);
    console.log('Nombre:', nombre);
    console.log('ID:', id);

    Swal.fire({
        title: `¿Quieres eliminar el archivo?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../../modulos/eliminar/eliminar_archivo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ruta: ruta })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Archivo eliminado',
                            text: 'El archivo fue eliminado correctamente.',
                            timer: 2000,
                            showConfirmButton: true
                        }).then(() => {
                            // 1. Eliminar la fila de la tabla
                            btn.closest('tr').remove();

                            // 2. Eliminar el archivo de la lista de archivos cargados
                            archivosCargados = archivosCargados.filter(file => file.name !== nombre);
                        });
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Archivo eliminado',
                            text: 'El archivo fue eliminado correctamente'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error en la petición: ' + error.message
                    });
                });
        }
    });
});


