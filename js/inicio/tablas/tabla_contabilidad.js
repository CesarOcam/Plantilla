
$("#tabla-contabilidad").load("../../php/modulos/inicio/tablas/tab_contabilidad.php", function () {
    let table = $('#miTablaContabilidad').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        pageLength: 15,
        dom: 'Bfrtip',
        dom: 'Bfrtip',
        buttons: [
            'copy',
            {
                extend: 'excel',
                title: 'Reporte de Contabilidad', 
                filename: 'reporte_contabilidad'      
            },
            {
                extend: 'pdf',
                title: 'Reporte de Contabilidad',
                filename: 'reporte_trafico',
                customize: function (doc) {
                    doc.content[0].text = 'Reporte de Contabilidad';
                    doc.content[0].alignment = 'center';
                    doc.content[0].fontSize = 14;
                }
            },
            'print'
        ],
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json",
            emptyTable: "No hay datos para mostrar"
        },
        "order": [[6, "desc"]],

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

            // Footer más compacto
            $('.dataTables_info, .dataTables_paginate, .dataTables_length').css({
                'font-size': '0.7rem',   // texto más chico
                'padding': '0.2rem 0.3rem'
            });

            // Botones de paginación más chicos
            $('.dataTables_paginate .paginate_button').css({
                'padding': '0.20rem 0.8rem',
                'font-size': '0.8rem',
                'margin': '0 0.1rem'
            });
        }
    });

    // Tabla compacta
    $('#miTablaContabilidad').addClass('table-sm');
    $('#miTablaContabilidad').css({
        'font-size': '0.8rem',
        'table-layout': 'auto'
    });
    $('#miTablaContabilidad th, #miTablaContabilidad td').css('padding', '0.25rem 0.5rem');
});