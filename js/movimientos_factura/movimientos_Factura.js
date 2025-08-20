document.addEventListener('click', function (e) {
  const btn = e.target.closest('.upload-file');
  if (!btn) return;

  const polizaId = btn.getAttribute('data-poliza-id');
  const partidaId = btn.closest('tr').querySelector('.obs-edit').getAttribute('data-partida-id');

  document.getElementById('uploadPolizaId').value = polizaId;
  document.getElementById('uploadPartidaId').value = partidaId;

  document.getElementById('formUploadArchivo').reset();
  updateDropText([]);
});

const dropArea = document.getElementById('dropArea');
const fileInput = document.getElementById('archivo');
const dropText = document.getElementById('dropText');
const form = document.getElementById('formUploadArchivo');
const typeHint = form.querySelector('.form-text');
const btnSubir = document.getElementById('btnSubirArchivo');

// Clic en área drag abre input
dropArea.addEventListener('click', () => fileInput.click());

// Evitar comportamiento por defecto
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
  dropArea.addEventListener(eventName, e => e.preventDefault());
});

// Efectos visuales
['dragenter', 'dragover'].forEach(eventName => {
  dropArea.addEventListener(eventName, () => dropArea.classList.add('bg-light'));
});
['dragleave', 'drop'].forEach(eventName => {
  dropArea.addEventListener(eventName, () => dropArea.classList.remove('bg-light'));
});

// Manejar archivo arrastrado
dropArea.addEventListener('drop', (e) => {
  const files = e.dataTransfer.files;
  if (files.length > 0) {
    fileInput.files = files;
    updateDropText(files);
  }
});

// Cambiar texto cuando se selecciona un archivo manualmente
fileInput.addEventListener('change', () => {
  updateDropText(fileInput.files);
});

// Función para mostrar archivos seleccionados y validar nombres
function updateDropText(files) {
  if (files.length === 0) {
    dropText.textContent = 'Arrastra tu archivo aquí';
    typeHint.style.color = '';
    btnSubir.disabled = true;
    return;
  }

  dropText.textContent = Array.from(files).map(f => f.name).join(', ');

  // Validar cantidad de archivos
  if (files.length > 2) {
    typeHint.textContent = 'Solo se permiten 2 archivos: PDF y XML';
    typeHint.style.color = 'red';
    btnSubir.disabled = true;
    return;
  }

  // Validar PDF y XML
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
      // Todo correcto: mostrar palomita verde y resetear color
      typeHint.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Archivos correctos, listos para enviar';
      typeHint.style.color = '';
      btnSubir.disabled = false;
    }
  }
}

btnSubir.addEventListener('click', async () => {
  const files = fileInput.files;
  if (files.length !== 2) return;

  const formData = new FormData(form);

  try {
    const resp = await fetch('../../modulos/actualizar/subir_fact_movimientos.php', {
      method: 'POST',
      body: formData
    });

    const text = await resp.text();
    const json = JSON.parse(text);

    if (!json.ok) {
      alert('Error al subir archivos: ' + (json.msg || ''));
      return;
    }

    // --- Cerrar modal ---
    const modalEl = document.getElementById('modalUploadArchivo');
    bootstrap.Modal.getInstance(modalEl).hide();

    // --- Actualizar fila correspondiente ---
    const partidaId = document.getElementById('uploadPartidaId').value;
    const fila = document.querySelector(`tr[data-partida-id="${partidaId}"]`);
    if (fila) {
      const tdArchivo = fila.querySelector('td:last-child');
      tdArchivo.innerHTML = `<i class="bi bi-check-circle-fill text-success fs-5" style="cursor:pointer;" data-bs-toggle="tooltip" data-bs-placement="top" title="Archivo guardado: ${json.archivo}"></i> <span class="small text-truncate" style="max-width: 120px;" title="${json.archivo}">${json.archivo}</span>`;
      new bootstrap.Tooltip(tdArchivo.querySelector('i'));
    }

    // --- Guardar tab activo antes de recargar ---
    const activeTabEl = document.querySelector('button[data-bs-toggle="tab"].active');
    if (activeTabEl) {
      localStorage.setItem('activeTab', activeTabEl.getAttribute('data-bs-target'));
    }

    // --- Toast y recarga ---
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Archivos subidos correctamente',
      showConfirmButton: false,
      timer: 500,
      timerProgressBar: true
    }).then(() => location.reload());

    // Resetear formulario y drag area
    form.reset();
    updateDropText([]);

  } catch (err) {
    console.error('Error al subir archivos:', err);
    alert('Error al subir archivos.');
  }
});

// --- Restaurar tab activo al cargar la página ---
document.addEventListener('DOMContentLoaded', () => {
  const activeTab = localStorage.getItem('activeTab');
  if (activeTab) {
    const tabTrigger = document.querySelector(`button[data-bs-target="${activeTab}"]`);
    if (tabTrigger) {
      new bootstrap.Tab(tabTrigger).show();
    }
  }
});
