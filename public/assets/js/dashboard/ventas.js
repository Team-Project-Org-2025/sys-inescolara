const urlBaseVentas = `${window.BASE_URL || '/'}ventas`;

const Ventas = {
    tabla: null,

    init() {
        this.fechaAutomatica();
        this.initDataTable();
        this.initBuscarLote();
        this.initPagos();
        this.initPagarCompleto();
        this.initForm();
        this.initAcciones();
    },

    fechaAutomatica() {
        const now = new Date();
        const local = now.toISOString().slice(0, 16);
        const el = document.getElementById('fechaVenta');
        if (el) {
            el.value = local;
            el.setAttribute('readonly', true);
        }
    },

    // ==================== DATATABLE ====================

    initDataTable() {
        this.tabla = $('#ventasTable').DataTable({
            ajax: {
                url: `${urlBaseVentas}?accion=listar`,
                method: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                dataSrc: (res) => {
                    if (res.success) {
                        this.actualizarStats(res.ventas);
                        return res.ventas;
                    }
                    return [];
                }
            },
            columns: [
                { data: 'id_venta', className: 'text-center' },
                { data: 'referencia' },
                { data: 'nombre_cliente', defaultContent: '—' },
                {
                    data: null,
                    render: (r) => r.nombre_trabajador ? `${r.nombre_trabajador} ${r.apellido_trabajador || ''}` : '—'
                },
                {
                    data: 'fecha_venta',
                    render: (d) => d ? new Date(d).toLocaleString('es-ES') : '—'
                },
                {
                    data: null,
                    className: 'text-end',
                    render: (r) => `Bs. ${parseFloat(r.monto_total || 0).toLocaleString('es-ES', { minimumFractionDigits: 2 })}`
                },
                {
                    data: 'estado',
                    className: 'text-center',
                    render: (d) => {
                        const map = { completada: 'success', pendiente: 'warning', cancelada: 'danger' };
                        return `<span class="badge bg-${map[d] || 'secondary'}">${d}</span>`;
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    orderable: false,
                    render: (r) => {
                        const btns = [
                            `<button class="btn btn-sm btn-info ver-detalle" data-id="${r.id_venta}" title="Ver"><i class="fas fa-eye"></i></button>`,
                            `<a href="${urlBaseVentas}?accion=comprobante&id=${r.id_venta}" class="btn btn-sm btn-success btn-pdf-download" title="PDF"><i class="fas fa-file-pdf"></i></a>`
                        ];
                        if (r.estado === 'pendiente') {
                            btns.push(`<button class="btn btn-sm btn-danger cancelar-venta" data-id="${r.id_venta}" title="Anular"><i class="fas fa-ban"></i></button>`);
                        }
                        return `<div class="d-flex gap-1 justify-content-center">${btns.join('')}</div>`;
                    }
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
            order: [[0, 'desc']],
            pageLength: 25,
        });
    },

    actualizarStats(ventas) {
        const total = ventas.length;
        const completadas = ventas.filter(v => v.estado === 'completada').length;
        const pendientes = ventas.filter(v => v.estado === 'pendiente').length;
        const totalBs = ventas.reduce((s, v) => s + parseFloat(v.monto_total || 0), 0);

        document.getElementById('totalVentas').textContent = total;
        document.getElementById('totalCompletadas').textContent = completadas;
        document.getElementById('totalPendientes').textContent = pendientes;
        document.getElementById('totalIngresos').textContent = `Bs. ${totalBs.toLocaleString('es-ES', { minimumFractionDigits: 2 })}`;
    },

    // ==================== LOTES ====================

    initBuscarLote() {
        const input = document.getElementById('buscarLote');
        const resultados = document.getElementById('resultadosLotes');
        let timeout;

        input.addEventListener('input', () => {
            clearTimeout(timeout);
            const q = input.value.trim();
            if (q.length < 2) {
                resultados.style.display = 'none';
                return;
            }
            timeout = setTimeout(() => this.buscarLotes(q), 300);
        });

        input.addEventListener('blur', () => setTimeout(() => resultados.style.display = 'none', 300));
        input.addEventListener('focus', () => {
            if (resultados.children.length > 0) resultados.style.display = 'block';
        });
    },

    async buscarLotes(q) {
        try {
            const res = await fetch(`${urlBaseVentas}?accion=buscar_lotes&q=${encodeURIComponent(q)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            const cont = document.getElementById('resultadosLotes');
            cont.innerHTML = '';
            cont.style.display = 'none';

            if (!data.success || !data.lotes?.length) return;

            data.lotes.forEach(l => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action py-2 d-flex justify-content-between align-items-center';
                item.innerHTML = `
                    <div><strong>${l.planta_nombre || ''}</strong> ${l.especie_nombre ? `<small class="text-muted">(${l.especie_nombre})</small>` : ''}</div>
                    <div class="text-end small">Stock: <strong>${l.cantidad_actual}</strong> | Bs. ${parseFloat(l.precio_unitario || 0).toLocaleString('es-ES', { minimumFractionDigits: 2 })}</div>
                `;
                item.addEventListener('click', () => {
                    this.agregarProducto(l);
                    cont.style.display = 'none';
                    input.value = '';
                });
                cont.appendChild(item);
            });
            cont.style.display = 'block';
        } catch (e) {
            console.error('Error buscando lotes:', e);
        }
    },

    agregarProducto(lote) {
        const cont = document.getElementById('productosContainer');
        document.getElementById('sinProductos').style.display = 'none';

        const div = document.createElement('div');
        div.className = 'card border-0 bg-light mb-2';
        div.dataset.idLote = lote.id_lote;
        div.innerHTML = `
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong>${lote.planta_nombre || ''}</strong>
                        ${lote.especie_nombre ? `<span class="text-muted">(${lote.especie_nombre})</span>` : ''}
                        <span class="badge bg-info ms-1" style="font-size:.65rem;">Stock ${lote.cantidad_actual}</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger quitar-producto py-0 px-1" title="Quitar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-4">
                        <small class="text-muted d-block" style="font-size:.7rem;line-height:1;letter-spacing:.5px;">CANTIDAD</small>
                        <div class="input-group input-group-sm mt-1">
                            <input type="number" class="form-control cantidad-producto text-center" value="1" min="1" max="${lote.cantidad_actual}">
                            <span class="input-group-text px-1" style="font-size:.7rem;">/ ${lote.cantidad_actual}</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block" style="font-size:.7rem;line-height:1;letter-spacing:.5px;">PRECIO UNIT.</small>
                        <input type="number" class="form-control form-control-sm precio-producto text-end mt-1" value="${parseFloat(lote.precio_unitario || 0).toFixed(2)}" step="0.01" min="0">
                    </div>
                    <div class="col-4 text-end">
                        <small class="text-muted d-block" style="font-size:.7rem;line-height:1;letter-spacing:.5px;">SUBTOTAL</small>
                        <div class="subtotal-producto fw-bold mt-1" style="font-size:1rem;">Bs. ${parseFloat(lote.precio_unitario || 0).toLocaleString('es-ES', { minimumFractionDigits: 2 })}</div>
                    </div>
                </div>
            </div>
        `;

        const cant = div.querySelector('.cantidad-producto');
        const precio = div.querySelector('.precio-producto');
        const sub = div.querySelector('.subtotal-producto');

        const recalcular = () => {
            const c = parseInt(cant.value) || 0;
            const p = parseFloat(precio.value) || 0;
            sub.textContent = `Bs. ${(c * p).toLocaleString('es-ES', { minimumFractionDigits: 2 })}`;
            this.calcularTotales();
        };

        cant.addEventListener('input', recalcular);
        precio.addEventListener('input', recalcular);
        div.querySelector('.quitar-producto').addEventListener('click', () => {
            div.remove();
            this.calcularTotales();
            if (cont.children.length === 0) document.getElementById('sinProductos').style.display = 'block';
        });

        cont.appendChild(div);
        this.calcularTotales();
    },

    // ==================== TOTALES ====================

    calcularTotales() {
        let total = 0;
        document.querySelectorAll('#productosContainer .card').forEach(f => {
            const c = parseInt(f.querySelector('.cantidad-producto')?.value) || 0;
            const p = parseFloat(f.querySelector('.precio-producto')?.value) || 0;
            total += c * p;
        });

        const sinIva = total / 1.16;
        const iva = sinIva * 0.16;

        document.getElementById('resumenSubtotal').textContent = `Bs. ${sinIva.toLocaleString('es-ES', { minimumFractionDigits: 2 })}`;
        document.getElementById('resumenIva').textContent = `Bs. ${iva.toLocaleString('es-ES', { minimumFractionDigits: 2 })}`;
        document.getElementById('resumenTotal').textContent = `Bs. ${total.toLocaleString('es-ES', { minimumFractionDigits: 2 })}`;

        this.actualizarBalancePagos(total);
    },

    // ==================== PAGOS ====================

    initPagos() {
        document.getElementById('agregarPago').addEventListener('click', () => {
            const cont = document.getElementById('pagosContainer');
            const filas = cont.querySelectorAll('.pago-row');
            if (filas.length === 0) return;
            const nuevo = filas[0].cloneNode(true);
            nuevo.querySelector('.monto-pago').value = '';
            nuevo.querySelector('.ref-pago').value = '';
            nuevo.querySelector('.quitar-pago').style.display = 'inline-block';
            this.toggleRef(nuevo.querySelector('.metodo-pago'));
            cont.appendChild(nuevo);
        });

        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('metodo-pago')) {
                this.toggleRef(e.target);
            }
        });

        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('monto-pago')) {
                e.target.value = e.target.value.replace(/[^0-9.,]/g, '').replace(/,/g, '.');
                const total = parseFloat(document.getElementById('resumenTotal').textContent.replace(/[^0-9,]/g, '').replace(',', '.')) || 0;
                this.actualizarBalancePagos(total);
            }
            if (e.target.classList.contains('ref-pago')) {
                e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6);
            }
        });

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.quitar-pago');
            if (!btn) return;
            const fila = btn.closest('.pago-row');
            if (document.querySelectorAll('.pago-row').length > 1) {
                fila.remove();
                const total = parseFloat(document.getElementById('resumenTotal').textContent.replace(/[^0-9,]/g, '').replace(',', '.')) || 0;
                this.actualizarBalancePagos(total);
            }
        });

        document.querySelector('.metodo-pago') && this.toggleRef(document.querySelector('.metodo-pago'));
    },

    toggleRef(select) {
        const row = select.closest('.pago-row');
        const refCol = row?.querySelector('.ref-pago')?.closest('.col-2');
        if (!refCol) return;
        if (select.value === 'efectivo' || select.value === 'punto') {
            refCol.classList.add('d-none');
            row.querySelector('.ref-pago').value = '';
        } else {
            refCol.classList.remove('d-none');
        }
    },

    initPagarCompleto() {
        document.getElementById('pagarCompleto').addEventListener('click', () => {
            const el = document.getElementById('resumenTotal');
            const total = parseFloat(el.textContent.replace(/[^0-9,]/g, '').replace(',', '.')) || 0;
            const primerPago = document.querySelector('.monto-pago');
            if (primerPago) {
                primerPago.value = total.toFixed(2);
                primerPago.dispatchEvent(new Event('input'));
            }
        });
    },

    actualizarBalancePagos(total) {
        let pagado = 0;
        document.querySelectorAll('.monto-pago').forEach(inp => {
            pagado += parseFloat(inp.value) || 0;
        });

        document.getElementById('totalPagado').textContent = `Bs. ${pagado.toLocaleString('es-ES', { minimumFractionDigits: 2 })}`;

        const pendiente = total - pagado;
        const el = document.getElementById('saldoPendiente');
        el.textContent = `Bs. ${Math.max(0, pendiente).toLocaleString('es-ES', { minimumFractionDigits: 2 })}`;
        el.style.color = Math.abs(pendiente) < 0.01 ? '#198754' : '#dc3545';
    },

    // ==================== FORMULARIO ====================

    initForm() {
        document.getElementById('ventaForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.guardarVenta();
        });
    },

    async guardarVenta() {
        const productos = [];
        document.querySelectorAll('#productosContainer .card').forEach(row => {
            const cant = parseInt(row.querySelector('.cantidad-producto')?.value) || 0;
            const precio = parseFloat(row.querySelector('.precio-producto')?.value) || 0;
            if (cant > 0 && precio > 0) {
                productos.push({
                    id_lote: parseInt(row.dataset.idLote),
                    cantidad: cant,
                    precio_unitario: precio,
                });
            }
        });

        if (productos.length === 0) {
            Swal.fire('Error', 'Debe agregar al menos un producto.', 'warning');
            return;
        }

        const pagos = [];
        document.querySelectorAll('.pago-row').forEach(row => {
            const monto = parseFloat(row.querySelector('.monto-pago').value) || 0;
            if (monto > 0) {
                pagos.push({
                    metodo: row.querySelector('.metodo-pago').value,
                    monto: monto,
                    referencia: row.querySelector('.ref-pago').value || null,
                });
            }
        });

        if (pagos.length === 0) {
            Swal.fire('Error', 'Debe registrar al menos un pago.', 'warning');
            return;
        }

        const form = document.getElementById('ventaForm');
        const formData = new FormData(form);
        formData.set('productos', JSON.stringify(productos));
        formData.set('pagos', JSON.stringify(pagos));

        if (!formData.get('fecha_venta')) {
            formData.set('fecha_venta', new Date().toISOString().slice(0, 19).replace('T', ' '));
        }

        try {
            const res = await fetch(`${urlBaseVentas}?accion=guardar`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                const result = await Swal.fire({
                    icon: 'success',
                    title: 'Venta registrada',
                    text: `Venta ${data.referencia} registrada correctamente.`,
                    showCancelButton: true,
                    confirmButtonText: 'Descargar Comprobante',
                    cancelButtonText: 'Cerrar',
                });

                if (result.isConfirmed) {
                    const a = document.createElement('a');
                    a.href = `${urlBaseVentas}?accion=comprobante&id=${data.id}`;
                    a.download = `comprobante-${data.referencia}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }

                const modal = bootstrap.Modal.getInstance(document.getElementById('ventaModal'));
                modal.hide();
                form.reset();
                document.getElementById('productosContainer').innerHTML = '';
                document.getElementById('sinProductos').style.display = 'block';
                this.fechaAutomatica();
                this.reiniciarPagos();
                this.calcularTotales();
                this.tabla.ajax.reload();
            } else {
                Swal.fire('Error', data.message || 'Error al guardar la venta.', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Error de conexión al guardar la venta.', 'error');
            console.error(e);
        }
    },

    reiniciarPagos() {
        const cont = document.getElementById('pagosContainer');
        cont.innerHTML = `
            <div class="pago-row mb-2 pb-2 border-bottom">
                <div class="row g-1 align-items-center">
                    <div class="col-5">
                        <select class="form-select form-select-sm metodo-pago">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="pago_movil">Pago Móvil</option>
                            <option value="punto">Punto</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <input type="number" class="form-control form-control-sm monto-pago" placeholder="Monto" step="0.01" min="0">
                    </div>
                    <div class="col-2">
                        <input type="text" class="form-control form-control-sm ref-pago" placeholder="Ref.">
                    </div>
                    <div class="col-1 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger quitar-pago py-0 px-1" style="display:none;"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            </div>
        `;
    },

    // ==================== ACCIONES ====================

    initAcciones() {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.ver-detalle');
            if (btn) this.verDetalle(parseInt(btn.dataset.id));
        });

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.cancelar-venta');
            if (btn) this.cancelarVenta(parseInt(btn.dataset.id));
        });

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-pdf-download');
            if (btn) {
                e.preventDefault();
                const a = document.createElement('a');
                a.href = btn.href;
                a.download = '';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        });
    },

    async verDetalle(id) {
        try {
            const res = await fetch(`${urlBaseVentas}?accion=detalles&id=${id}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();

            if (!data.success) {
                Swal.fire('Error', data.message || 'Error al obtener detalle.', 'error');
                return;
            }

            const v = data.venta;
            const detalles = v.detalles || [];
            const pagos = v.pagos || [];

            let html = `
                <div class="row mb-3">
                    <div class="col-6"><strong>Referencia:</strong> ${v.referencia || ''}</div>
                    <div class="col-6 text-end"><strong>Fecha:</strong> ${v.fecha_venta ? new Date(v.fecha_venta).toLocaleString('es-ES') : ''}</div>
                    <div class="col-6 mt-2"><strong>Cliente:</strong> ${v.nombre_cliente || '—'}</div>
                    <div class="col-6 mt-2"><strong>Vendedor:</strong> ${(v.nombre_trabajador || '') + ' ' + (v.apellido_trabajador || '')}</div>
                    <div class="col-6 mt-2"><strong>Tipo:</strong> ${v.tipo_venta || ''}</div>
                    <div class="col-6 mt-2"><strong>Estado:</strong> ${v.estado || ''}</div>
                    ${v.observaciones ? `<div class="col-12 mt-2"><strong>Observaciones:</strong> ${v.observaciones}</div>` : ''}
                </div>
                <hr>
                <h6 class="fw-bold">Productos</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>#</th><th>Planta</th><th class="text-center">Cant.</th><th class="text-end">Precio Unit.</th><th class="text-end">Subtotal</th></tr>
                    </thead>
                    <tbody>`;

            detalles.forEach((d, i) => {
                const sub = parseFloat(d.cantidad) * parseFloat(d.precio_unitario);
                html += `<tr>
                    <td>${i + 1}</td>
                    <td>${d.planta_nombre || ''} ${d.especie_nombre ? `<small class="text-muted">(${d.especie_nombre})</small>` : ''}</td>
                    <td class="text-center">${d.cantidad}</td>
                    <td class="text-end">Bs. ${parseFloat(d.precio_unitario).toLocaleString('es-ES', { minimumFractionDigits: 2 })}</td>
                    <td class="text-end">Bs. ${sub.toLocaleString('es-ES', { minimumFractionDigits: 2 })}</td>
                </tr>`;
            });

            html += `</tbody></table>
                <div class="row">
                    <div class="col-12 col-lg-6 offset-lg-6">
                        <div class="d-flex justify-content-between"><span>Subtotal (sin IVA):</span><strong>Bs. ${(v.monto_sin_iva || 0).toLocaleString('es-ES', { minimumFractionDigits: 2 })}</strong></div>
                        <div class="d-flex justify-content-between"><span>IVA (${v.iva_porcentaje || 16}%):</span><strong>Bs. ${(v.monto_iva || 0).toLocaleString('es-ES', { minimumFractionDigits: 2 })}</strong></div>
                        <hr>
                        <div class="d-flex justify-content-between fs-5 fw-bold"><span>TOTAL:</span><span class="text-primary">Bs. ${((v.monto_sin_iva || 0) + (v.monto_iva || 0)).toLocaleString('es-ES', { minimumFractionDigits: 2 })}</span></div>
                    </div>
                </div>`;

            if (pagos.length > 0) {
                html += `<hr><h6 class="fw-bold">Pagos</h6>
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr><th>Método</th><th class="text-end">Monto</th><th>Referencia</th></tr>
                        </thead>
                        <tbody>`;
                pagos.forEach(p => {
                    html += `<tr><td>${p.metodo || ''}</td><td class="text-end">Bs. ${parseFloat(p.monto).toLocaleString('es-ES', { minimumFractionDigits: 2 })}</td><td>${p.referencia || '—'}</td></tr>`;
                });
                html += `</tbody></table>`;
            }

            document.getElementById('detalleContenido').innerHTML = html;
            const btnPdf = document.getElementById('btnDescargarPdf');
            btnPdf.onclick = (e) => {
                e.preventDefault();
                const a = document.createElement('a');
                a.href = `${urlBaseVentas}?accion=comprobante&id=${id}`;
                a.download = `comprobante-${id}.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            };
            bootstrap.Modal.getOrCreateInstance(document.getElementById('detalleModal')).show();

        } catch (e) {
            Swal.fire('Error', 'Error al cargar detalle.', 'error');
            console.error(e);
        }
    },

    async cancelarVenta(id) {
        const result = await Swal.fire({
            title: '¿Anular esta venta?',
            text: 'Se restaurará el stock de los productos vendidos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar',
        });

        if (!result.isConfirmed) return;

        try {
            const fd = new FormData();
            fd.append('id', id);

            const res = await fetch(`${urlBaseVentas}?accion=cancelar`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            const data = await res.json();

            if (data.success) {
                Swal.fire('Anulada', 'Venta anulada y stock restaurado.', 'success');
                this.tabla.ajax.reload();
            } else {
                Swal.fire('Error', data.message || 'Error al anular la venta.', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Error de conexión.', 'error');
            console.error(e);
        }
    }
};

document.addEventListener('DOMContentLoaded', () => Ventas.init());
