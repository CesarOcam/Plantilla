document.getElementById('form_Cuota').addEventListener('submit', function (e) {
    e.preventDefault();

    const iva = document.getElementById('IVA').value.trim();
    const subtotal = document.getElementById('subtotal').value.trim();
    const monto = document.getElementById('monto').value.trim();

    if (!iva || !subtotal || !monto) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Por favor, asegúrate que IVA, Subtotal y Monto tengan un valor.',
        });
        return;
    }

    if (!selectedPair || !selectedPair.xml || !selectedPair.pdf) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Por favor selecciona un par de archivos válido (XML + PDF).'
        });
        return;
    }

    const form = document.getElementById('form_Cuota');
    const formData = new FormData(form);

    // Solo añadir el par seleccionado
    formData.append('facturas[]', selectedPair.xml);
    formData.append('facturas[]', selectedPair.pdf);

    console.log('Datos del formulario:');
    for (let pair of formData.entries()) {
        console.log(pair[0], pair[1]);
    }

    fetch('../../modulos/guardado/guardar_cuota.php', {
        method: 'POST',
        body: formData,
    })
        .then(response => response.text()) // <-- cambiar a .text() para ver el contenido
        .then(text => {
            console.log('Respuesta cruda:', text); // <-- revisar esto en consola
            const data = JSON.parse(text); // <-- convertir a JSON manualmente
            console.log('Respuesta del servidor:', data);
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: `Póliza generada: ${data.data.numero_poliza}`,
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al guardar',
                    text: data.message,
                });
            }
            // Eliminar el par enviado de allFiles
            allFiles = allFiles.filter(f =>
                !(f.name === selectedPair.xml.name || f.name === selectedPair.pdf.name)
            );

            // Limpiar selectedPair
            selectedPair = null;

            // Limpiar campos del formulario si quieres
            document.getElementById('IVA').value = '';
            document.getElementById('subtotal').value = '';
            document.getElementById('monto').value = '';
            $('#referencia-select').val(null).trigger('change');
            $('#referencia-select').prop('disabled', true).trigger('change.select2');

            // Volver a renderizar para actualizar la interfaz
            renderFilePairs();
        })

        .catch(error => {
            console.error('Error en el envío:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al enviar los datos.',
            });
        });
});
