$(document).ready(function() {
    // Cuando se envíe el formulario
    $("#form_Clientes").on("submit", function(e) {
        // Prevenir que se recargue la página
        e.preventDefault();

        // Crear un objeto FormData para obtener todos los datos del formulario
        var formData = $(this).serialize(); // serializa todos los datos del formulario

        // Imprimir los valores del formulario en la consola
        console.log(formData);

        // Enviar los datos por Ajax
        $.ajax({
            url: '../../modulos/guardado/guardar_cliente.php', // Archivo PHP donde se guardarán los datos
            type: 'POST',
            data: formData, // Datos del formulario
            success: function(response) {
                // Aquí puedes manejar la respuesta del servidor
                console.log('Respuesta del servidor:', response);
            },
            error: function(xhr, status, error) {
                // En caso de error
                console.log('Error en la solicitud Ajax:', error);
            }
        });
    });
});
