import * as Ayuda from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

const urlBase = `${window.BASE_URL || '/'}ornatos`;
let tablaOrnatos = null;
let editando = false;

const ornatoRules = {
  id_cliente: 'select',
  tipo_ornato: 'select',
  fecha: 'fechaFuturaCheck',
  ubicacion: null,
  descripcion: null,
};

$(document).ready(function () {
    inicializarTabla();
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
            { data: 'id_ornato' },
            { data: 'nombre_cliente', render: (d) => d || '<span class="text-muted">--</span>' },
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
                    const d = Ayuda.escapeHtml;
                    return `
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary btn-editar">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-eliminar">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    `;
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
        $('#cuerpoDetalle').empty();
        editando = false;
        $('#inputMontoTotal').val('0.00');
        $('#inputMontoTotalHidden').val('0.00');
        $('#totalDetalle').text('$0.00');
    });

    setupRealTimeValidation($('#formOrnato'), ornatoRules);
}

function abrirModalParaAgregar()
{
    editando = false;
    $('#tituloModal').text('Agregar Ornato');
    $('#inputFecha').val(new Date().toISOString().split('T')[0]);
    $('#inputMontoTotal').val('0.00');
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
    $('#inputCliente').val(row.id_cliente);
    $('#inputTipo').val(row.tipo_ornato);
    $('#inputFecha').val(row.fecha);
    $('#inputUbicacion').val(row.ubicacion || '');
    $('#inputDescripcion').val(row.descripcion || '');
    $('#inputMontoTotal').val(Ayuda.formatCurrency(row.monto_total));
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
    $('#inputMontoTotal').val(Ayuda.formatCurrency(total));
    $('#inputMontoTotalHidden').val(total.toFixed(2));
}

function guardarOrnato($form)
{
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
