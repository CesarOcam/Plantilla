document.getElementById('btn_complementaria').addEventListener('click', function () {
    const id = this.getAttribute('data-id');
    fetch('../../modulos/guardado/crear_complementaria.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({ id: id })
    })
        .then(res => res.text()) // <-- ojo, texto crudo
        .then(text => {
            console.log('Respuesta cruda:', text); // <--- aquí verás si hay algún error PHP
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    Swal.fire({
                        title: 'Cuenta Complementaria generada',
                        text: data.mensaje,
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        window.location.href = "../../vistas/consultas/consulta_referencia.php";
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.mensaje,
                        icon: 'error',
                        confirmButtonText: 'Cerrar'
                    });
                }
            } catch (e) {
                console.error('JSON inválido:', e, text);
                alert('Respuesta inválida del servidor');
            }
        })
        .catch(err => {
            console.error('Error en la solicitud:', err);
            alert('Error en la solicitud');
        });

});
