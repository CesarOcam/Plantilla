document.addEventListener('click', function (e) {
  const btn = e.target.closest('.upload-file');
  if (!btn) return;

  const tr = btn.closest('tr');
  const polizaId = btn.getAttribute('data-poliza-id');
  const partidaId = btn.getAttribute('data-partida-id') || tr.querySelector('.obs-edit').getAttribute('data-partida-id');
  const origen = tr.getAttribute('data-origen'); 

  document.getElementById('uploadPolizaId').value = polizaId;
  document.getElementById('uploadPartidaId').value = partidaId;
  document.getElementById('uploadOrigen').value = origen; 

  document.getElementById('formUploadArchivo').reset();
  updateDropText([]);
});


const dropArea = document.getElementById('dropArea');
const fileInput = document.getElementById('archivo');
const dropText = document.getElementById('dropText');
const form = document.getElementById('formUploadArchivo');
const typeHint = form.querySelector('.form-text');
const btnSubir = document.getElementById('btnSubirArchivo');

dropArea.addEventListener('click', () => fileInput.click());

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
  dropArea.addEventListener(eventName, e => e.preventDefault());
});

['dragenter', 'dragover'].forEach(eventName => {
  dropArea.addEventListener(eventName, () => dropArea.classList.add('bg-light'));
});
['dragleave', 'drop'].forEach(eventName => {
  dropArea.addEventListener(eventName, () => dropArea.classList.remove('bg-light'));
});

dropArea.addEventListener('drop', (e) => {
  const files = e.dataTransfer.files;
  if (files.length > 0) {
    fileInput.files = files;
    updateDropText(files);
  }
});

fileInput.addEventListener('change', () => {
  updateDropText(fileInput.files);
});

function updateDropText(files) {
  if (files.length === 0) {
    dropText.textContent = 'Arrastra tu archivo aquí';
    typeHint.style.color = '';
    btnSubir.disabled = true;
    return;
  }

  dropText.textContent = Array.from(files).map(f => f.name).join(', ');

  if (files.length > 2) {
    typeHint.textContent = 'Solo se permiten 2 archivos: PDF y XML';
    typeHint.style.color = 'red';
    btnSubir.disabled = true;
    return;
  }

  const pdf = Array.from(files).find(f => f.name.toLowerCase().endsWith('.pdf'));
  const xml = Array.from(files).find(f => f.name.toLowerCase().endsWith('.xml'));

  if (!pdf && !xml) {
    typeHint.textContent = 'Debes subir un PDF y un XML';
    typeHint.style.color = 'red';
    btnSubir.disabled = true;
  } else if (!pdf) {
    typeHint.textContent = 'Falta el archivo PDF';
    typeHint.style.color = 'red';
    btnSubir.disabled = true;
  } else if (!xml) {
    typeHint.textContent = 'Falta el archivo XML';
    typeHint.style.color = 'red';
    btnSubir.disabled = true;
  } else {
    const pdfBase = pdf.name.replace(/\.pdf$/i, '');
    const xmlBase = xml.name.replace(/\.xml$/i, '');
    if (pdfBase !== xmlBase) {
      typeHint.textContent = 'PDF y XML deben tener el mismo nombre base';
      typeHint.style.color = 'red';
      btnSubir.disabled = true;
    } else {
      typeHint.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Archivos correctos, listos para enviar';
      typeHint.style.color = '';
      btnSubir.disabled = false;
    }
  }
}

btnSubir.addEventListener('click', async () => {
  const files = fileInput.files;
  if (files.length !== 2) return;

  // Obtener el XML
  const xmlFile = Array.from(files).find(f => f.name.toLowerCase().endsWith('.xml'));
  if (!xmlFile) return alert('No se encontró el archivo XML');

  // Leer el XML y extraer UUID
  let uuid = null;
  let serie = null;
  let folio = null;

  uuid = await new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const xmlText = e.target.result;
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(xmlText, "application/xml");

        const CFDI_NS = "http://www.sat.gob.mx/cfd/4";
        const TFD_NS = "http://www.sat.gob.mx/TimbreFiscalDigital";

        // Extraer UUID
        const complemento = xmlDoc.getElementsByTagNameNS(CFDI_NS, 'Complemento')[0];
        const timbre = complemento?.getElementsByTagNameNS(TFD_NS, 'TimbreFiscalDigital')[0];
        const uuidValue = timbre?.getAttribute('UUID');

        // Extraer Serie y Folio
        const comprobante = xmlDoc.getElementsByTagNameNS(CFDI_NS, 'Comprobante')[0];
        if (comprobante) {
          serie = comprobante.getAttribute('Serie') || '';
          folio = comprobante.getAttribute('Folio') || '';
        }

        if (!uuidValue) reject(new Error('No se pudo extraer el UUID del XML'));
        else resolve(uuidValue);
      } catch (err) {
        reject(err);
      }
    };
    reader.onerror = () => reject(reader.error);
    reader.readAsText(xmlFile);
  }).catch(err => {
    alert(err.message);
    throw err;
  });

  // Preparar FormData y enviar al backend
  const formData = new FormData(form);
  formData.append('UUID', uuid);
  formData.append('Serie', serie);
  formData.append('Folio', folio);

  console.log(uuid);
  console.log(serie);
  console.log(folio);


  try {
    const resp = await fetch('../../modulos/actualizar/subir_fact_movimientos.php', {
      method: 'POST',
      body: formData
    });

    const text = await resp.text();
    console.log('Respuesta PHP cruda:', text); // <-- útil para depurar
    const json = JSON.parse(text);

    if (!json.ok) {
      if (json.uuid && json.referencia) {
        Swal.fire({
          icon: 'warning',
          title: 'UUID duplicado',
          html: `El UUID <strong>${json.uuid}</strong> ya existe en la referencia <strong>${json.referencia}</strong>.`,
          confirmButtonText: 'Aceptar'
        });
      } else if (json.referencia) {
        Swal.fire({
          icon: 'warning',
          title: 'Archivos duplicados',
          html: `Ya existe un par de archivos con ese nombre en la referencia <strong>${json.referencia}</strong>.`,
          confirmButtonText: 'Aceptar'
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error al subir archivos',
          text: json.msg || 'Error desconocido'
        });
      }
      return;
    }

    // Todo correcto
    const modalEl = document.getElementById('modalUploadArchivo');
    bootstrap.Modal.getInstance(modalEl).hide();

    const partidaId = document.getElementById('uploadPartidaId').value;
    const fila = document.querySelector(`tr[data-partida-id="${partidaId}"]`);
    if (fila) {
      const tdArchivo = fila.querySelector('td:last-child');
      tdArchivo.innerHTML = `<i class="bi bi-check-circle-fill text-success fs-5" style="cursor:pointer;" data-bs-toggle="tooltip" data-bs-placement="top" title="Archivo guardado"></i>`;
      new bootstrap.Tooltip(tdArchivo.querySelector('i'));
    }

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Archivos subidos correctamente',
      showConfirmButton: false,
      timer: 500,
      timerProgressBar: true
    }).then(() => location.reload());

    form.reset();
    updateDropText([]);

  } catch (err) {
    console.error('Error al subir archivos:', err);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al subir archivos. Revisa la consola.'
    });
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const refId = new URLSearchParams(window.location.search).get('id');
  const activeTab = localStorage.getItem('activeTab-' + refId);

  if (activeTab) {
    const tabTrigger = document.querySelector(`button[data-bs-target="${activeTab}"]`);
    if (tabTrigger) {
      new bootstrap.Tab(tabTrigger).show();
    }
  }

  document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(btn => {
    btn.addEventListener('shown.bs.tab', e => {
      localStorage.setItem('activeTab-' + refId, e.target.getAttribute('data-bs-target'));
    });
  });
});
