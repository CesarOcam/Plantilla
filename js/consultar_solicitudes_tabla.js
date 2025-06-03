//TABLA DINAMICA DE PAGO DE SOLICITUDES
document.addEventListener('DOMContentLoaded', function () {
    const botones = document.querySelectorAll('.btn-aceptar');

    botones.forEach(boton => {
        boton.addEventListener('click', function () {
            bootstrap.Tooltip.getInstance(this)?.dispose();

            const idSolicitud = this.getAttribute('data-id');
            if (!idSolicitud) return;

            fetch(`../../modulos/consultas_traf/obtener_solicitud.php?id=${idSolicitud}`)
                .then(response => response.text())
                .then(text => {
                    console.log("Respuesta bruta del servidor:", text);
                    try {
                        const data = JSON.parse(text);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            // Rellenar campos
                            document.getElementById('EmpresaId').value = data.EmpresaNombre || '';
                            document.getElementById('NoSolicitud').value = data.Id || '';
                            document.getElementById('Fecha').value = data.Fecha || '';
                            document.getElementById('BeneficiarioId').value = data.BeneficiarioNombre || '';
                            document.getElementById('FechaAlta').value = data.FechaAlta || '';
                            document.getElementById('AduanaId').value = data.AduanaNombre || '';

                            // Llenar tabla de partidas
                            const cuerpoTabla = document.querySelector('#tabla-partidas tbody');
                            const tablaPartidas = document.querySelector('#tabla-partidas');

                            if (!cuerpoTabla || !tablaPartidas) {
                                console.error("No se encontró la tabla o su tbody.");
                                return;
                            }

                            const pieTablaExistente = document.querySelector('#tabla-partidas tfoot');
                            if (pieTablaExistente) pieTablaExistente.remove(); // Eliminar tfoot anterior
                            cuerpoTabla.innerHTML = ''; // Limpiar tbody

                            let totalCargo = 0;
                            let totalAbono = 0;

                            if (data.Partidas && data.Partidas.length > 0) {
                                data.Partidas.forEach(p => {
                                    totalCargo += parseFloat(p.Cargo || 0);
                                    totalAbono += parseFloat(p.Abono || 0);
                                    const fila = `
                                    <tr class="text-center" style="background-color: rgba(0, 0, 0, 0.05);">
                                        <td>${p.SubcuentaNombre || ''}</td>
                                        <td>${p.ReferenciaNumero || ''}</td>
                                        <td>$ ${parseFloat(p.Cargo).toFixed(2)}</td>
                                        <td></td>
                                        <td>${p.RazonSocialExportador || ''}</td>
                                        <td>${p.Observaciones || ''}</td>
                                    </tr>`;
                                    cuerpoTabla.insertAdjacentHTML('beforeend', fila);
                                });

                                // Crear opciones dinámicamente
                                let opcionesSubcuenta = '<option value="">Seleccionar subcuenta</option>';
                                subcuentas.forEach(s => {
                                    opcionesSubcuenta += `<option value="${s.Id}">${s.Numero} - ${s.Nombre}</option>`;
                                });

                                const filaEditable = `
                                <tr class="text-center align-middle">
                                    <!-- Subcuenta -->
                                    <td>
                                        <select name="SubcuentaId_pago" class="form-control form-control-sm select-subcuenta text-center" style="width: 100%;">
                                            ${opcionesSubcuenta}
                                        </select>
                                    </td>
                                <!-- Referencia vacía -->
                                <td colspan="2"></td>

                                <!-- Abono centrado -->
                                <td>
                                    $ ${totalCargo.toFixed(2)}
                                </td>

                                <!-- Exportador vacío -->
                                <td></td>

                                <!-- Observaciones centradas -->
                                <td>
                                    <input type="text" name="Observaciones_pago"
                                        class="form-control form-control-sm text-center" 
                                        placeholder="Observaciones" />
                                </td>
                            </tr>
                                `;
                                cuerpoTabla.insertAdjacentHTML('beforeend', filaEditable);

                                // Inicializar Select2 en la nueva fila
                                setTimeout(() => {
                                    $('.select-subcuenta').select2({
                                        placeholder: 'Seleccionar subcuenta',
                                        width: '100%',
                                        allowClear: false
                                    });
                                }, 0);
                                // Crear tfoot
                                let pieTabla = document.querySelector('#tabla-partidas tfoot');
                                if (!pieTabla) {
                                    pieTabla = document.createElement('tfoot');
                                    pieTabla.style.backgroundColor = '#f1f1f1';
                                    pieTabla.classList.add('tfoot-total-pagar');
                                    tablaPartidas.appendChild(pieTabla);
                                }

                                const totalRow = `
                                <tr class="fw-bold text-center align-middle" style="height: 45px; text-align: center;">
                                    <td colspan="2">Total: </td>
                                    <td>$ ${totalCargo.toFixed(2)}</td>
                                    <td>$ ${totalCargo.toFixed(2)}</td>
                                    <td colspan="2"></td>
                                </tr>`;
                                pieTabla.innerHTML = totalRow;

                            } else {
                                cuerpoTabla.innerHTML = `
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Sin subcuentas asociadas</td>
                                </tr>`;
                            }

                            // Cerrar modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('modalSolicitudes'));
                            if (modal) modal.hide();
                        }
                    } catch (err) {
                        console.error("No se pudo convertir en JSON:", err);
                        console.log("Contenido recibido:", text);
                        alert("Error inesperado en el servidor.");
                    }
                })
                .catch(error => {
                    console.error('Error al obtener solicitud:', error);
                    if (error.response) {
                        // Si usas axios o similar
                        console.error('Response error:', error.response);
                    }
                });
        });
    });
});
