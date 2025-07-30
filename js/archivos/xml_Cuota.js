const fileInput = document.getElementById('fileUpload');
const fileRows = document.getElementById('fileRows');
const uploadPrompt = document.getElementById('uploadPrompt');
const uploadAlert = document.getElementById('uploadAlert');
const uploadBox = document.getElementById('uploadBox');
let selectedPair = null; // { xml: File, pdf: File }

let allFiles = [];

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

            const row = document.createElement('div');
            row.className = 'file-row d-flex justify-content-between align-items-center p-2 mb-2 rounded-0 border bg-white';
            row.style.cursor = 'pointer';

            row.innerHTML = `
                <div>
                    <strong>${base}</strong><br>
                    <span class="text-muted small">${[pair['xml'].name, pair['pdf'].name].join(' + ')}</span>
                </div>
                <button type="button" class="btn btn-link p-0 btn-trash me-3"
                    style="color: #a19b9b; font-size: 1.2rem;"
                    title="Eliminar">
                    <i class="fas fa-trash-alt"></i>
                </button>
                `;

            row.addEventListener('mouseenter', () => {
                row.style.backgroundColor = '#f8f9fa';
            });
            row.addEventListener('mouseleave', () => {
                row.style.backgroundColor = '#ffffff';
            });

            row.querySelector('.btn-trash').addEventListener('click', (e) => {
                e.stopPropagation();
                allFiles = allFiles.filter(f =>
                    !(f.name === `${base}.xml` || f.name === `${base}.pdf`)
                );

                // Limpiar formulario
                document.getElementById('IVA').value = '';
                document.getElementById('subtotal').value = '';
                document.getElementById('monto').value = '';
                document.getElementById('observaciones').value = '';

                // Desactivar y resetear el select2
                $('#referencia-select').val(null).trigger('change');
                $('#referencia-select').prop('disabled', true);
                $('#referencia-select').trigger('change.select2');

                // Ocultar alerta si estaba mostrada
                uploadAlert.style.display = 'none';
                uploadAlert.innerHTML = '';


                renderFilePairs();
            });


            function setValoresXML(totalImpuestosTrasladados, subtotal, total) {
                const ivaInput = document.getElementById('IVA');
                const subtotalInput = document.getElementById('subtotal');
                const montoInput = document.getElementById('monto');

                if (ivaInput && subtotalInput && montoInput) {
                    ivaInput.value = Number(totalImpuestosTrasladados).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    ivaInput.dispatchEvent(new Event('input'));

                    subtotalInput.value = Number(subtotal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    subtotalInput.dispatchEvent(new Event('input'));

                    montoInput.value = Number(total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    montoInput.dispatchEvent(new Event('input'));

                    // Activar Select2
                    $('#referencia-select').prop('disabled', false);
                    $('#referencia-select').trigger('change.select2');
                } else {
                    console.error('Inputs no encontrados para setear valores XML');
                }
            }


            //Lógica al seleccionar una fila
            row.addEventListener('click', () => {
                const xmlFile = pair['xml'];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const xmlText = e.target.result;
                    const parser = new DOMParser();
                    const xmlDoc = parser.parseFromString(xmlText, "application/xml");

                    const root = xmlDoc.documentElement;
                    const CFDI_NS = root.namespaceURI;

                    const comprobante = root;
                    const emisor = xmlDoc.getElementsByTagNameNS(CFDI_NS, 'Emisor')[0];
                    const impuestosNodes = comprobante.getElementsByTagNameNS(CFDI_NS, 'Impuestos');
                    let totalImpuestosTrasladados = null;

                    for (const impuestos of impuestosNodes) {
                        const valor = impuestos.getAttribute('TotalImpuestosTrasladados');
                        if (valor) {
                            totalImpuestosTrasladados = valor;
                            break;
                        }
                    }

                    // Si no se encontró atributo, sumamos importes de traslados
                    if (!totalImpuestosTrasladados) {
                        for (const impuestos of impuestosNodes) {
                            const traslados = impuestos.getElementsByTagNameNS(CFDI_NS, 'Traslados');
                            if (traslados.length > 0) {
                                const trasladoList = traslados[0].getElementsByTagNameNS(CFDI_NS, 'Traslado');
                                let suma = 0;
                                for (const traslado of trasladoList) {
                                    suma += parseFloat(traslado.getAttribute('Importe') || 0);
                                }
                                totalImpuestosTrasladados = suma.toFixed(2);
                                break;
                            }
                        }
                    }

                    if (!totalImpuestosTrasladados) totalImpuestosTrasladados = 'No especificado';

                    const Rfc = emisor ? emisor.getAttribute('Rfc') : 'No encontrado';
                    const subtotal = comprobante.getAttribute('SubTotal') || 'No encontrado';
                    const total = comprobante.getAttribute('Total') || 'No encontrado';

                    if (Rfc != 'ALM2205042T1') {
                        uploadAlert.style.display = 'block';
                        uploadAlert.style.backgroundColor = 'rgba(255, 0, 0, 0.1)';
                        uploadAlert.style.border = '1px solid red';
                        uploadAlert.style.color = 'red';
                        uploadAlert.style.padding = '10px';
                        uploadAlert.style.borderRadius = '5px';
                        uploadAlert.style.whiteSpace = 'pre-line';
                        uploadAlert.textContent = `¡ERROR!\nEl RFC es invalido`;
                    }

                    document.getElementById('IVA').value = Number(totalImpuestosTrasladados).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    document.getElementById('subtotal').value = Number(subtotal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    document.getElementById('monto').value = Number(total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    document.getElementById('observaciones').value = base;



                    setValoresXML(totalImpuestosTrasladados, subtotal, total);

                    console.log(`RFC: ${Rfc}\nSubtotal: ${subtotal}\nImpuestos: ${totalImpuestosTrasladados}\nTotal: ${total}`);
                };

                reader.readAsText(xmlFile);

                selectedPair = { //Se guarda el par seleccionado para enviarlo al ajax
                    xml: pair['xml'],
                    pdf: pair['pdf']
                };


            });

            fileRows.appendChild(row);
        }
    });

    uploadPrompt.style.display = hasPairs ? 'none' : '';
    updateFileInput(allFiles);
}

function updateFileInput(files) {
    const dataTransfer = new DataTransfer();
    files.forEach(file => dataTransfer.items.add(file));
    fileInput.files = dataTransfer.files;
}

//------------------------------------------------------------------------------------------------------------

$(document).ready(function () {
    // Inicializar Select2
    $('#referencia-select').select2({
        placeholder: 'Seleccionar referencia',
        allowClear: false,
        width: '100%'
    });

    // Desactivar select2 inicialmente
    $('#referencia-select').prop('disabled', true);
    $('#referencia-select').trigger('change.select2'); // Para que Select2 refresque el estado

    $('#referencia-select').on('change', function () {
        const referenciaId = this.value;
        const aduanaInput = $('#aduana');
        const aduanaHidden = $('#aduanaHidden');
        const exportadorInput = $('#exportador');
        const exportadorHidden = $('#exportadorHidden');

        fetch(`registro_cuota.php?referencia_id=${referenciaId}`)
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    throw new Error("La respuesta no es JSON válida.");
                }
                return response.json();
            })
            .then(data => {
                if (data) {
                    aduanaInput.val(data.nombre_corto_aduana || '');
                    exportadorInput.val(data.razonSocial_exportador || '');
                    aduanaHidden.val(data.id2201aduanas || '');
                    exportadorHidden.val(data.id01clientes_exportadores || '');
                } else {
                    aduanaInput.val('');
                    exportadorInput.val('');
                    aduanaHidden.val('');
                    exportadorHidden.val('');
                }
            })
            .catch(error => {
                console.error('Error al cargar la aduana/exportador:', error);
                aduanaInput.val('');
                exportadorInput.val('');
            });
    });



});