console.log("reporte_cuentas_mes.js cargado");

document.addEventListener("DOMContentLoaded", function () {
    const meses = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
    ];

    const subcuentaSelect = document.getElementById("subcuentaInput");

    // Escuchar cambio de tabs en los botones .nav-link
    document.querySelectorAll(".nav-link").forEach((tabLink) => {
        tabLink.addEventListener("shown.bs.tab", (e) => {
            const tabId = e.target.getAttribute("data-bs-target").substring(1); // quitar '#'
            const index = [...document.querySelectorAll(".tab-pane")].findIndex(p => p.id === tabId);
            console.log("Tab cambiado:", { tabId, mes: index + 1 });
            if (index >= 0) cargarDatos(tabId, index + 1);
        });
    });

    // Escuchar cambio en select de subcuenta
    $('#subcuentaInput').on('change.select2', function () {
        const subcuentaSelect = this;
        const activeTab = document.querySelector(".tab-pane.show.active");
        if (!activeTab) return;
        const index = [...document.querySelectorAll(".tab-pane")].indexOf(activeTab);

        const selectedOption = subcuentaSelect.selectedOptions[0];
        const optionId = selectedOption ? selectedOption.id : null;

        console.log("Subcuenta cambiada:", {
            cuentaId: subcuentaSelect.value,
            optionId: optionId,
            tabId: activeTab.id,
            mes: index + 1
        });

        if (index >= 0) cargarDatos(activeTab.id, index + 1);
    });

    function cargarDatos(tabId, mes) {
        const cuentaId = subcuentaSelect.value;
        if (!cuentaId) {
            console.log("No hay subcuenta seleccionada.");
            return;
        }
        console.log("Cargando datos para:", { cuentaId, mes, tabId });

        fetch("../../../php/modulos/reportes/tablas_cuentas_mes.php", {
            method: "POST",
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cuentaId, mes })
        })
            .then(res => {
                if (!res.ok) throw new Error("Error en la respuesta HTTP " + res.status);
                return res.text();
            })
            .then(html => {
                const tabContent = document.querySelector(`#${tabId} .tabla-scroll`);
                if (!tabContent) return;
                tabContent.innerHTML = html; // reemplaza el contenido directamente
            })
            .catch(err => {
                console.error("Error al cargar datos:", err);
            });
    }

});
