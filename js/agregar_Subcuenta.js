$(document).on('keydown', '#tablaSubcuentas tbody input', function(e) {
    if (e.key === "Enter") {
        e.preventDefault(); // Evita comportamiento por defecto
        $('#btn_guardar').click(); // Simula click en el botón principal
    }
});


const ultimoNumero = '<?php echo $ultimoNumero; ?>';

document.getElementById('btnAgregarSubcuenta').addEventListener('click', function() {
    const tabla = document.getElementById('tablaSubcuentas').querySelector('tbody');
    
    const nuevaFila = document.createElement('tr');

    nuevaFila.innerHTML = `
        <td><input type="text" class="form-control form-control-sm" placeholder="Número" required></td>
        <td><input type="text" class="form-control form-control-sm" placeholder="Nombre" required></td>
        <td><input type="number" class="form-control form-control-sm" placeholder="Saldo" step="0.01" required></td>
        <td>
            <button class="btn btn-sm btn-outline-danger btnCancelar">Borrar</button>
        </td>
    `;

    tabla.appendChild(nuevaFila);

    // Scroll hasta abajo
    const contenedor = document.getElementById('tabla-subcuentas-detalle');
    requestAnimationFrame(() => {
        contenedor.scrollTop = contenedor.scrollHeight;
    });

    // Botones
    nuevaFila.querySelector('.btnCancelar').addEventListener('click', function() {
        nuevaFila.remove();
    });

    nuevaFila.querySelector('.btnGuardar').addEventListener('click', function() {
        const numero = nuevaFila.querySelector('td:nth-child(1) input').value;
        const nombre = nuevaFila.querySelector('td:nth-child(2) input').value;
        const saldo = nuevaFila.querySelector('td:nth-child(3) input').value;

        if(!numero || !nombre || !saldo) {
            alert('Todos los campos son obligatorios');
            return;
        }

        

        nuevaFila.innerHTML = `
            <td>${numero}</td>
            <td>${nombre}</td>
            <td>$ ${parseFloat(saldo).toFixed(2)}</td>
            <td></td>
        `;
    });
});