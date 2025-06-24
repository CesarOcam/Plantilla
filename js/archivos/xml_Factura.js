$(document).ready(function () {
    // Inicializar todos los select de referencia
    $('.referencia-select').select2({
        placeholder: 'Click para cambiar...',
        allowClear: false,
        width: '100%'
    });

    // Inicializar todos los select de subcuenta
    $('.subcuenta-select').select2({
        placeholder: 'Seleccionar subcuenta',
        allowClear: false,
        width: '100%'
    });
});

const fileInput = document.getElementById('fileUpload');
const fileRows = document.getElementById('fileRows');
const uploadPrompt = document.getElementById('uploadPrompt');
const uploadAlert = document.getElementById('uploadAlert');
const uploadBox = document.getElementById('uploadBox');
let selectedPair = null; // { xml: File, pdf: File }

let allFiles = [];
let pairs = [];

fileInput.addEventListener('change', function () {
    allFiles = Array.from(fileInput.files);
    renderFilePairs();
});

uploadBox.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadBox.style.borderColor = '#0d6efd';  // azul para feedback
});

uploadBox.addEventListener('dragleave', (e) => {
    e.preventDefault();
    uploadBox.style.borderColor = '';  // vuelve al estilo original
});

uploadBox.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadBox.style.borderColor = '';

    const droppedFiles = Array.from(e.dataTransfer.files);
    allFiles = allFiles.concat(droppedFiles);

    // Opcional: evitar duplicados por nombre
    const uniqueFilesMap = new Map();
    allFiles.forEach(file => uniqueFilesMap.set(file.name, file));
    allFiles = Array.from(uniqueFilesMap.values());

    renderFilePairs();
});

function renderFilePairs() {
    pairs.length = 0;
    const grouped = {};
    fileRows.innerHTML = '';
    uploadAlert.style.display = 'none';
    uploadAlert.innerHTML = '';

    allFiles.forEach(file => {
        const nameParts = file.name.split('.');
        const base = nameParts.slice(0, -1).join('.');
        const ext = nameParts.pop().toLowerCase();
        if (!grouped[base]) grouped[base] = {};
        grouped[base][ext] = file;
    });

    let hasPairs = false;
    let invalidPairs = [];

    Object.keys(grouped).forEach(base => {
        const pair = grouped[base];
        const exts = Object.keys(pair);
        if (exts.length !== 2 || !('xml' in pair) || !('pdf' in pair)) {
            invalidPairs.push(base);
        }
    });

    //Caja de ERROR roja
    if (invalidPairs.length > 0) {
        uploadAlert.style.display = 'block';
        uploadAlert.style.backgroundColor = 'rgba(255, 0, 0, 0.1)';
        uploadAlert.style.border = '1px solid red';
        uploadAlert.style.color = 'red';
        uploadAlert.style.padding = '10px';
        uploadAlert.style.borderRadius = '5px';
        uploadAlert.style.whiteSpace = 'pre-line';
        uploadAlert.textContent = `¡ERROR!\nLos archivos deben formar pares con el mismo nombre base y deben ser un archivo XML y un PDF.\nPares inválidos:\n${invalidPairs.join('\n')}`;
    }

    Object.keys(grouped).forEach(base => {
        const pair = grouped[base];
        if (pair['xml'] && pair['pdf']) {
            hasPairs = true;

            pairs.push({ xml: pair['xml'], pdf: pair['pdf'] });

            const row = document.createElement('div');
            row.className = 'file-row d-flex justify-content-between align-items-center p-2 mb-2 rounded-0 border bg-white';

            row.innerHTML = `
                <div>
                    <strong>${base}</strong><br>
                    <span class="text-muted small">${[pair['xml'].name, pair['pdf'].name].join(' + ')}</span>
                </div>
                `;

            row.addEventListener('mouseenter', () => {
                row.style.backgroundColor = '#f8f9fa';
            });
            row.addEventListener('mouseleave', () => {
                row.style.backgroundColor = '#ffffff';
            });

            fileRows.appendChild(row);
        }
    });

    uploadPrompt.style.display = hasPairs ? 'none' : '';
    updateFileInput(allFiles);
}

//Lógica al seleccionar una fila
document.getElementById('btnCargarTodos').addEventListener('click', () => {
    renderFilePairs();
    if (pairs.length === 0) {
        alert('No hay archivos para procesar');
        return;
    }

    const readXmlPromises = pairs.map(pair => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();

            reader.onload = function (e) {
                try {
                    const xmlText = e.target.result;
                    const parser = new DOMParser();
                    const xmlDoc = parser.parseFromString(xmlText, "application/xml");

                    const CFDI_NS = "http://www.sat.gob.mx/cfd/4";
                    const TFD_NS = "http://www.sat.gob.mx/TimbreFiscalDigital";

                    const root = xmlDoc.documentElement;
                    const comprobante = root;
                    const emisor = xmlDoc.getElementsByTagNameNS(CFDI_NS, 'Emisor')[0];
                    const receptor = xmlDoc.getElementsByTagNameNS(CFDI_NS, 'Receptor')[0];
                    const complemento = xmlDoc.getElementsByTagNameNS(CFDI_NS, 'Complemento')[0];

                    // Aquí es el cambio importante:
                    const timbre = complemento?.getElementsByTagNameNS(TFD_NS, 'TimbreFiscalDigital')[0];
                    const uuid = timbre?.getAttribute('UUID') || 'No encontrado';

                    const serie = comprobante.getAttribute('Serie') || 'No encontrado';
                    const folio = comprobante.getAttribute('Folio') || 'No encontrado';
                    const fechaRaw = comprobante.getAttribute('Fecha') || 'No encontrado';
                    const rfcProveedor = emisor?.getAttribute('Rfc') || 'No encontrado';
                    const nombreProveedor = emisor?.getAttribute('Nombre') || 'No encontrado';
                    const rfcCliente = receptor?.getAttribute('Rfc') || 'No encontrado';
                    const nombreCliente = receptor?.getAttribute('Nombre') || 'No encontrado';
                    const importe = comprobante.getAttribute('Total') || 'No encontrado';

                    let fechaFormateada = 'No encontrado';
                    if (fechaRaw !== 'No encontrado') {
                        const fechaObj = new Date(fechaRaw);
                        const dia = String(fechaObj.getDate()).padStart(2, '0');
                        const mes = String(fechaObj.getMonth() + 1).padStart(2, '0');
                        const anio = String(fechaObj.getFullYear()).slice(-2);
                        fechaFormateada = `${dia}/${mes}/${anio}`;
                    }
                    resolve({
                        serie: serie,
                        folio: folio,
                        rfcCliente: rfcCliente,
                        rfcProveedor: rfcProveedor,
                        proveedor: nombreProveedor,
                        cliente: nombreCliente,
                        fecha: fechaFormateada,
                        importe: importe,
                        uuid: uuid
                    });

                } catch (error) {
                    reject(error);
                }
            };

            reader.onerror = () => reject(reader.error);
            reader.readAsText(pair.xml);
        });
    });

    // Esperamos a que todos los archivos se procesen
    Promise.all(readXmlPromises)
        .then(results => {
            console.log('Todos los datos a enviar:', results);

            // Enviamos todos los datos en una sola llamada AJAX
            return fetch('../../modulos/guardado/guardar_factura.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ archivos: results })
            });
        })
        .then(response => response.json())
        .then(data => {
            if (data.duplicado) {
                Swal.fire({
                    icon: 'warning',
                    title: 'UUID duplicado',
                    html: `El UUID <strong>${data.uuid}</strong> ya existe en el registro con ID <strong>${data.idRegistro}</strong>.`,
                    confirmButtonText: 'Aceptar'
                });
                return;
            }
            console.log('Respuesta del servidor:', data);
            //alert('Archivos procesados exitosamente');
            allFiles.length = 0;
            pairs.length = 0;
            renderFilePairs();

            fetch('../../modulos/consultas_traf/tabla_facturas.php')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('tabla-aduanas-container').innerHTML = html;

                    // Reaplicar Select2 a los nuevos elementos
                    $('#tabla-aduanas-container select').select2({
                        placeholder: 'Seleccionar',
                        allowClear: false,
                        width: '100%'
                    });

                })
                .catch(err => {
                    console.error('Error al recargar la tabla:', err);
                    alert('No se pudo recargar la tabla de facturas.');
                });


        })
        .catch(error => {
            console.error('Error al procesar o enviar los archivos:', error);
            alert('Error al procesar los archivos');
        });
});


document.getElementById('btnVaciarArchivos').addEventListener('click', () => {
    allFiles.length = 0;      // Vacía el arreglo
    pairs.length = 0;         // También puedes vaciar pairs si quieres
    renderFilePairs();        // Vuelve a renderizar para limpiar las filas visibles
    uploadAlert.style.display = 'none';
    uploadAlert.innerHTML = '';

    console.log('Todos los archivos han sido eliminados.');
});

function updateFileInput(files) {
    const dataTransfer = new DataTransfer();
    files.forEach(file => dataTransfer.items.add(file));
    fileInput.files = dataTransfer.files;
}

//------------------------------------------------------------------------------------------------------------

document.getElementById('form_Factura').addEventListener('submit', function (e) {
    e.preventDefault();

    // Referencia al formulario
    const form = e.target;

    // Obtener todas las filas de la tabla (tbody)
    const filas = form.querySelectorAll('tbody tr');

    // Crear nuevo FormData para enviar solo filas con referencia
    const filteredFormData = new FormData();

    filas.forEach(fila => {
        const facturaId = fila.querySelector('input[name="factura_id[]"]')?.value || '';
        const referenciaId = fila.querySelector('select[name="referencia_id[]"]')?.value || '';
        const subcuentaId = fila.querySelector('select[name="subcuentas[]"]')?.value || '';

        if (referenciaId.trim() !== '') {
            filteredFormData.append('factura_id[]', facturaId);
            filteredFormData.append('referencia_id[]', referenciaId);
            filteredFormData.append('subcuentas[]', subcuentaId);
        }
    });

    // Mostrar datos que se van a enviar
    for (let [key, value] of filteredFormData.entries()) {
        console.log(key + ':', value);
    }

    // Enviar fetch con los datos filtrados
    fetch('../../modulos/guardado/guardar_factura_solicitud.php', {
        method: 'POST',
        body: filteredFormData
    })
        .then(async response => {
            const text = await response.text();
            console.log("Respuesta del servidor:", text);
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error("No es JSON válido:\n" + text);
            }
        })
        .then(data => {
            if (data.success) {
                // Mostrar el toast (no bloquea la ejecución)
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Solicitudes enviadas',
                    html: `Nueva solicitud generada`,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });

                // Recargar tabla inmediatamente
                fetch('../../modulos/consultas_traf/tabla_facturas.php')
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('tabla-aduanas-container').innerHTML = html;

                        $('#tabla-aduanas-container select').select2({
                            placeholder: 'Seleccionar',
                            allowClear: false,
                            width: '100%'
                        });
                    })
                    .catch(err => {
                        console.error('Error al recargar la tabla:', err);
                        alert('No se pudo recargar la tabla de facturas.');
                    });

            } else {
                alert('Error: ' + data.message);
            }
        })

        .catch(error => {
            console.error('Error AJAX:', error);
            alert('Error de red o respuesta inválida del servidor. Revisa la consola.');
        });

});

// Delegación de eventos para los botones de eliminar
document.getElementById('tabla-aduanas-container').addEventListener('click', function (event) {
    const btn = event.target.closest('.eliminar-factura');
    if (!btn) return;

    const facturaId = btn.getAttribute('data-id');
    if (!facturaId) return;

    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción eliminará la factura permanentemente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch('../../modulos/eliminar/eliminar_factura.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: facturaId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Factura eliminada',
                        html: `Se eliminó la factura del servidor`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });


                    // Recargar tabla
                    fetch('../../modulos/consultas_traf/tabla_facturas.php')
                        .then(resp => resp.text())
                        .then(html => {
                            document.getElementById('tabla-aduanas-container').innerHTML = html;
                            $('#tabla-aduanas-container select').select2({
                                placeholder: 'Seleccionar',
                                allowClear: false,
                                width: '100%'
                            });
                        });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error al eliminar factura:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de red',
                    text: 'No se pudo eliminar la factura. Intenta nuevamente.'
                });
            });
    });
});




//------------------------------------------------------------------------------------------------------------

