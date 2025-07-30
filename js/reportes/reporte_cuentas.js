fetch('../../php/vistas/treportes/reporte_cuentas_h1.php', {
  method: 'POST',
  body: formData
})
.then(res => res.json())
.then(data => {
  if (data.url) {
    const a = document.createElement('a');
    a.href = data.url;
    a.download = '';
    document.body.appendChild(a);
    a.click();
    a.remove();
  }
});
