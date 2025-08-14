document.getElementById('btn_enviarCG').addEventListener('click', function () {
    const referenciaId = this.dataset.referenciaId;

    const mailsLogistico = Array.from(document.querySelectorAll('input[name="mails_logistico[]"]:checked'))
        .map(input => input.value);

    const mailsAmex = Array.from(document.querySelectorAll('input[name="mails_amex[]"]:checked'))
        .map(input => input.value);

    // Mostrar Swal con spinner
    Swal.fire({
        title: 'Enviando correo...',
        html: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Enviar por AJAX
    fetch('../../modulos/actualizar/enviar_cg.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: referenciaId,
            mails_logistico: mailsLogistico,
            mails_amex: mailsAmex
        })
    })
        .then(res => res.text())
        .then(text => {
            Swal.close(); // Cerrar el spinner
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '!Correó enviado!',
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: `Error al enviar el correo: ${data.message}${data.debug ? `<br><br><b>Debug:</b> ${data.debug}` : ''}`
                    });
                }
            } catch (err) {
                console.error('Respuesta no es JSON:', text);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'La respuesta del servidor no es JSON. Revisa la consola.'
                });
            }
        })
        .catch(err => {
            Swal.close();
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor'
            });
        });
});
