fetch('../../php/modulos/inicio/tablas/tab_contabilidad.php')
    .then(resp => resp.text())
    .then(html => {
        document.getElementById('tabla-contabilidad').innerHTML = html;
    }).catch(err => console.error(err));

    $("#tabla-contabilidad").load("../../php/modulos/inicio/tablas/tab_contabilidad.php", function () {
    let table = $('#miTablaContabilidad').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        pageLength: 15,
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print'],
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json"
        },

        initComplete: function () {
            // Botones más pequeños
            $('.dt-button').css({
                'padding': '0.20rem 0.30rem',
                'font-size': '0.75rem',
                'margin-right': '0.1rem'
            });

            // Input de búsqueda y select de paginación más pequeños
            $('.dataTables_filter input, .dataTables_length select').css({
                'height': '1.5rem',
                'font-size': '0.65rem',
                'padding': '0.1rem 0.15rem'
            });

            // Reducir espacio de los contenedores de botones
            $('.dataTables_wrapper .dt-buttons').css({
                'margin-bottom': '0.1rem'
            });
        }
    });

    // Tabla compacta
    $('#miTablaContabilidad').addClass('table-sm');
    $('#miTablaContabilidad').css({
        'font-size': '0.8rem',
        'table-layout': 'auto' // permite que la tabla ajuste columnas automáticamente
    });
    $('#miTablaContabilidad th, #miTablaContabilidad td').css('padding', '0.25rem 0.5rem');
});
