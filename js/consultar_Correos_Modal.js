$('#btn_EnvioCG').on('click', function() {
  const id = $(this).data('id');

  // Mostrar texto mientras carga el contenido
  $('#modalEnvioCG .modal-body').html('Cargando...');

  // Crear instancia del modal y mostrarlo
  const myModal = new bootstrap.Modal(document.getElementById('modalEnvioCG'));
  myModal.show();

  // Petición AJAX para cargar las tablas
  $.ajax({
    url: '../../modulos/consultas/tablas_correos_cg.php',
    type: 'GET',
    data: { id: id },
    success: function(response) {
      $('#modalEnvioCG .modal-body').html(response);
    },
    error: function() {
      $('#modalEnvioCG .modal-body').html('<p>Error al cargar las tablas.</p>');
    }
  });
});
