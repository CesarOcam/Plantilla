document.getElementById('select-ejecutivo')?.addEventListener('change', function() {
  const usuario = this.value;

  fetch('../../php/modulos/inicio/tablas/tab_contabilidad.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'usuario=' + encodeURIComponent(usuario)
  })
  .then(response => response.text())
  .then(html => {
    document.getElementById('tabla-trafico').innerHTML = html;
  })
  .catch(err => console.error('Error cargando la tabla:', err));
});