import * as Ayuda from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';
import * as C from '../utils/components.js';

const urlBase = `${window.BASE_URL || '/'}ornatos`;
let tablaOrnatos = null;
let editando = false;

const ornatoRules = {
  tipo_ornato: 'select',
  fecha: 'fechaFuturaCheck',
  ubicacion: null,
  descripcion: null,
};

$(document).ready(function () {
    inicializarTabla();
    initBuscarCliente();
    configurarEventos();
});

function inicializarTabla()
{
    tablaOrnatos = $('#tablaOrnatos').DataTable({
        ajax: {
            url: `${urlBase}?accion=listar`,
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataSrc: 'ornatos',
        },
        columns: [
            { data: 'nombre_cliente', render: (d) => d || '<span class="text-muted">--</span>' },
            {
                data: null,
                render: (r) => r.tipo_cedula_cliente ? `${r.tipo_cedula_cliente}-${r.cedula_cliente}` : '—'
            },
            {
                data: 'tipo_ornato',
                render: (d) => {
                    const badge = d === 'Venta' ? 'bg-success' : 'bg-info';
                    return `<span class="badge ${badge}">${d}</span>`;
                },
            },
            { data: 'fecha' },
            { data: 'ubicacion', render: (d) => d || '<span class="text-muted">--</span>' },
            {
                data: 'monto_total',
                render: (d) => Ayuda.formatCurrency(d),
            },
            {
                data: null,
                orderable: false,
                render: () => {
                    return C.btnGroup(
                        C.btnEdit('btn-editar'),
                        C.btnDelete('btn-eliminar'),
                    );
                },
            },
        ],
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
        },
        dom: '<"d-flex justify-content-between align-items-center mb-2"lfB>tip',
        buttons: [
            {
                text: '<i class="fas fa-sync-alt"></i> Actualizar',
                className: 'btn btn-outline-secondary btn-sm',
                action: () => { tablaOrnatos.ajax.reload(null, false); },
            },
        ],
    });
}

// ==================== CLIENTE ====================

let clienteInput, clienteResultados, clienteHidden, clienteSeleccionado, clienteSeleccionadoTexto, limpiarClienteBtn;

function initBuscarCliente() {
    clienteInput = document.getElementById('buscarClienteOrnato');
    clienteResultados = document.getElementById('clienteResultadosOrnato');
    clienteHidden = document.getElementById('idClienteOrnato');
    clienteSeleccionado = document.getElementById('clienteSeleccionadoOrnato');
    clienteSeleccionadoTexto = document.getElementById('clienteSeleccionadoTextoOrnato');
    limpiarClienteBtn = document.getElementById('limpiarClienteOrnato');
    if (!clienteInput) return;
    let timeout;

    clienteInput.addEventListener('input', () => {
        clearTimeout(timeout);
        const q = clienteInput.value.trim();
        if (q.length < 2) {
            clienteResultados.style.display = 'none';
            return;
        }
        timeout = setTimeout(() => buscarClientes(q), 300);
    });

    clienteInput.addEventListener('blur', () => setTimeout(() => clienteResultados.style.display = 'none', 300));
    clienteInput.addEventListener('focus', () => {
        if (clienteResultados.children.length > 0) clienteResultados.style.display = 'block';
    });

    if (limpiarClienteBtn) {
        limpiarClienteBtn.addEventListener('click', () => limpiarCliente());
    }
}

async function buscarClientes(q) {
    try {
        const urlBase = `${window.BASE_URL || '/'}ornatos`;
        const res = await fetch(`${urlBase}?accion=buscar_clientes&q=${encodeURIComponent(q)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        const cont = clienteResultados;
        cont.innerHTML = '';
        cont.style.display = 'none';

        if (!data.success || !data.clientes?.length) return;

        data.clientes.forEach(cl => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action py-1 d-flex justify-content-between align-items-center';
            const cedula = cl.tipo_cedula_cliente ? `${cl.tipo_cedula_cliente}-${cl.cedula_cliente}` : '';
            item.innerHTML = `
                <div><strong>${cl.nombre_cliente}</strong> <small class="text-muted">${cedula}</small></div>
            `;
            item.addEventListener('click', () => {
                seleccionarCliente(cl.id_cliente, cl.nombre_cliente, cedula);
                cont.style.display = 'none';
            });
            cont.appendChild(item);
        });
        cont.style.display = 'block';
    } catch (e) {
        console.error('Error buscando clientes:', e);
    }
}

function seleccionarCliente(id, nombre, cedula) {
    clienteHidden.value = id;
    clienteInput.value = '';
    clienteInput.placeholder = nombre;
    clienteInput.classList.add('is-valid');
    clienteSeleccionadoTexto.textContent = cedula ? `${nombre} — ${cedula}` : nombre;
    clienteSeleccionado.classList.remove('d-none');
    clienteResultados.style.display = 'none';
}

function limpiarCliente() {
    clienteHidden.value = '';
    clienteInput.value = '';
    clienteInput.placeholder = 'Buscar por C.I., nombre o apellido...';
    clienteInput.classList.remove('is-valid');
    clienteSeleccionado.classList.add('d-none');
}

function configurarEventos()
{
    $('#btnNuevoOrnato').on('click', function () {
        abrirModalParaAgregar();
    });

    $(document).on('click', '.btn-editar', function () {
        const row = tablaOrnatos.row($(this).closest('tr')).data();
        abrirModalParaEditar(row);
    });

    // Submit del formulario
    $('#formOrnato').on('submit', function (e) {
        e.preventDefault();
        if (!validateForm($(this), ornatoRules)) return;
        guardarOrnato($(this));
    });

    // Eliminar
    $(document).on('click', '.btn-eliminar', function () {
        const row = tablaOrnatos.row($(this).closest('tr')).data();
        const id = row.id_ornato;
        const cliente = row.nombre_cliente || `#${id}`;

        Ayuda.confirmDialog(
            '¿Eliminar ornato?',
            `¿Está seguro de que desea eliminar este ornato de <strong>${Ayuda.escapeHtml(cliente)}</strong>?`,
            () => {
                Ajax.post(`${urlBase}?accion=eliminar`, { id })
                    .then((respuesta) => {
                        if (respuesta.success) {
                            Ayuda.toast('success', 'Ornato eliminado correctamente');
                            tablaOrnatos.ajax.reload(null, false);
                        } else {
                            Ayuda.toast('error', respuesta.message);
                        }
                    })
                    .catch((err) => {
                        Ayuda.toast('error', err || 'Error al eliminar');
                    });
            },
            'Sí, eliminar',
        );
    });

    // Agregar fila de detalle
    $(document).on('click', '#btnAgregarPlanta', function () {
        agregarFilaDetalle(null);
    });

    // Quitar fila de detalle
    $(document).on('click', '.btn-quitar-fila', function () {
        $(this).closest('tr').remove();
        recalcularTotal();
    });

    // Auto-llenar precio al seleccionar lote
    $(document).on('change', '.select-lote', function () {
        const $select = $(this);
        const $fila = $select.closest('tr');
        const $opcion = $select.find('option:selected');
        const precio = $opcion.data('precio');

        if (precio !== undefined && precio !== '' && !isNaN(parseFloat(precio))) {
            $fila.find('.input-precio').val(parseFloat(precio).toFixed(2));
        } else {
            $fila.find('.input-precio').val('0.00');
        }
        recalcularFila($fila);
    });

    // Recalcular al cambiar cantidad o precio
    $(document).on('input', '.input-cantidad, .input-precio', function () {
        recalcularFila($(this).closest('tr'));
    });

    // Reset modal al cerrar
    $('#modalOrnato').on('hidden.bs.modal', function () {
        Ayuda.resetForm($('#formOrnato'));
        limpiarCliente();
        $('#cuerpoDetalle').empty();
        editando = false;
        $('#inputMontoTotal').text('0.00');
        $('#inputMontoTotalHidden').val('0.00');
        $('#totalDetalle').text('$0.00');
    });

    setupRealTimeValidation($('#formOrnato'), ornatoRules);
}

function abrirModalParaAgregar()
{
    editando = false;
    $('#tituloModal').text('Agregar Ornato');
    limpiarCliente();
    $('#inputFecha').val(new Date().toISOString().split('T')[0]);
    $('#inputMontoTotal').text('0.00');
    $('#inputMontoTotalHidden').val('0.00');
    $('#totalDetalle').text('$0.00');
    $('#cuerpoDetalle').empty();
    // Agregar una fila por defecto
    agregarFilaDetalle(null);
    $('#modalOrnato').modal({ focus: false }).modal('show');
}

function abrirModalParaEditar(row)
{
    editando = true;
    $('#tituloModal').text('Editar Ornato');
    $('#inputId').val(row.id_ornato);
    const cedulaCompleta = row.tipo_cedula_cliente ? `${row.tipo_cedula_cliente}-${row.cedula_cliente}` : '';
    seleccionarCliente(row.id_cliente, row.nombre_cliente, cedulaCompleta);
    $('#inputTipo').val(row.tipo_ornato);
    $('#inputFecha').val(row.fecha);
    $('#inputUbicacion').val(row.ubicacion || '');
    $('#inputDescripcion').val(row.descripcion || '');
    $('#inputMontoTotal').text(Ayuda.formatCurrency(row.monto_total));
    $('#inputMontoTotalHidden').val(row.monto_total);

    // Cargar detalles vía AJAX
    $('#cuerpoDetalle').empty();
    Ajax.get(`${urlBase}?accion=detalles&id=${row.id_ornato}`)
        .then((respuesta) => {
            if (respuesta.success && respuesta.detalles && respuesta.detalles.length > 0) {
                respuesta.detalles.forEach((item) => {
                    agregarFilaDetalle(item);
                });
            } else {
                agregarFilaDetalle(null);
            }
            recalcularTotal();
        })
        .catch(() => {
            agregarFilaDetalle(null);
        });

    $('#modalOrnato').modal({ focus: false }).modal('show');
}

function agregarFilaDetalle(item)
{
    const $template = $($('#templateFilaDetalle').html());
    const $cuerpo = $('#cuerpoDetalle');

    if (item) {
        $template.find('.select-lote').val(item.id_lote);
        $template.find('.input-cantidad').val(item.cantidad);
        $template.find('.input-precio').val(parseFloat(item.precio_unitario || 0).toFixed(2));
        const sub = parseFloat(item.sub_total || 0);
        $template.find('.span-subtotal').text(Ayuda.formatCurrency(sub));
        $template.find('.input-subtotal-hidden').val(sub.toFixed(2));
    }

    $cuerpo.append($template);

    if (!item) {
        recalcularFila($cuerpo.find('tr:last'));
    }
}

function recalcularFila($fila)
{
    const cantidad = parseInt($fila.find('.input-cantidad').val()) || 0;
    const precio = parseFloat($fila.find('.input-precio').val()) || 0;
    const subtotal = cantidad * precio;

    $fila.find('.span-subtotal').text(Ayuda.formatCurrency(subtotal));
    $fila.find('.input-subtotal-hidden').val(subtotal.toFixed(2));
    recalcularTotal();
}

function recalcularTotal()
{
    let total = 0;
    $('#cuerpoDetalle .input-subtotal-hidden').each(function () {
        total += parseFloat($(this).val()) || 0;
    });
    $('#totalDetalle').text(Ayuda.formatCurrency(total));
    $('#inputMontoTotal').text(Ayuda.formatCurrency(total));
    $('#inputMontoTotalHidden').val(total.toFixed(2));
}

function guardarOrnato($form)
{
    if (!clienteHidden?.value) {
        Ayuda.toast('error', 'Debe seleccionar un cliente.');
        clienteInput?.focus();
        return;
    }

    const formData = new FormData($form[0]);

    // Recorrer las filas de detalle y construir el array de items
    const items = [];
    $('#cuerpoDetalle tr').each(function () {
        const $fila = $(this);
        const idLote = parseInt($fila.find('.select-lote').val()) || 0;
        const cantidad = parseInt($fila.find('.input-cantidad').val()) || 0;
        const precio = parseFloat($fila.find('.input-precio').val()) || 0;
        const subtotal = parseFloat($fila.find('.input-subtotal-hidden').val()) || 0;

        if (idLote > 0 && cantidad > 0) {
            items.push({
                id_lote: idLote,
                cantidad: cantidad,
                precio_unitario: precio,
                sub_total: subtotal,
            });
        }
    });

    formData.set('monto_total', $('#inputMontoTotalHidden').val());
    formData.append('items', JSON.stringify(items));

    // Eliminar los inputs nombrados items[] del FormData (porque usamos JSON)
    // Los inputs con name="items[...]" se envían automáticamente por FormData,
    // pero nosotros usamos JSON, así que los ignoramos en el backend.
    // Para evitar duplicados, el controlador ya verifica si items es array o string.

    const accion = editando ? 'actualizar' : 'guardar';
    const metodo = editando ? 'Editar' : 'Agregar';

    Ajax.post(`${urlBase}?accion=${accion}`, formData)
        .then((respuesta) => {
            if (respuesta.success) {
                Ayuda.toast('success', respuesta.message);
                $('#modalOrnato').modal('hide');
                tablaOrnatos.ajax.reload(null, false);
            } else {
                Ayuda.toast('error', respuesta.message);
            }
        })
        .catch((err) => {
            Ayuda.toast('error', err || `Error al ${metodo.toLowerCase()} el ornato`);
        });
}
