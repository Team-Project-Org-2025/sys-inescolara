import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';
import * as C from '../utils/components.js';

const DATA = window.TASK_DATA || {};
const baseUrl = DATA.tasksUrl || `${window.BASE_URL || '/'}tareas`;

let assignmentsTable = null;

const assignRules = {
  nombre_tarea: 'nombre',
  id_trabajador: 'select',
  id_lote: 'select',
  descripcion: null,
  fecha_asignacion: 'fechaFuturaCheck',
};

const completeRules = {
  fecha_cumplimiento: 'fechaFuturaCheck',
  horas_dedicadas: 'numeric',
};

// ============================================================
//  ASIGNACIONES DATATABLE
// ============================================================
function initAssignmentsTable() {
    if (typeof SkeletonHelper !== 'undefined') {
        SkeletonHelper.showTableSkeleton('assignmentsTable', 5, 5);
    }
    assignmentsTable = $('#assignmentsTable').DataTable({
        ajax: {
            url: `${baseUrl}?action=get_assignments`,
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataSrc: 'assignments',
        },
        columns: [
            {
                data: null,
                render: (d) => `${Helpers.escapeHtml(d.nombre_trabajador || '')} ${Helpers.escapeHtml(d.apellido_trabajador || '')}`,
            },
            { data: 'nombre_tarea' },
            { data: 'fecha_asignacion' },
            {
                data: 'estatus_tarea',
                render: (data) => {
                    const map = {
                        pendiente: '<span class="badge bg-warning text-dark badge-estatus">Pendiente</span>',
                        completada: '<span class="badge bg-success badge-estatus">Completada</span>',
                        cancelada: '<span class="badge bg-secondary badge-estatus">Cancelada</span>',
                    };
                    return map[data] || `<span class="badge bg-info badge-estatus">${Helpers.escapeHtml(data)}</span>`;
                },
            },
            {
                data: null,
                orderable: false,
                render: (d) => {
                    const est = d.estatus_tarea;
                    let btnVer = C.btnView('btn-view-assign', 'title="Ver detalle"');
                    let btnCompletar = '';
                    let btnCancelar = '';
                    let btnEditar = '';
                    if (est === 'pendiente') {
                        btnEditar = C.btnCustom({ label: 'Editar', icon: 'fa-pen', className: 'btn-edit-assign', btnClass: 'btn-outline-warning', extraAttrs: 'title="Editar"' });
                        btnCompletar = C.btnComplete('btn-complete-assign', 'title="Completar"');
                        btnCancelar = C.btnCancel('btn-cancel-assign', 'title="Cancelar"');
                    }
                    return C.btnGroup(btnVer, btnEditar, btnCompletar, btnCancelar);
                },
            },
        ],
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        order: [[2, 'desc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
        },
        dom: '<"d-flex justify-content-between align-items-center mb-2"lfB>tip',
        buttons: [
            {
                text: '<i class="fas fa-sync-alt"></i> Actualizar',
                className: 'btn btn-outline-secondary btn-sm',
                action: () => assignmentsTable.ajax.reload(null, false),
            },
        ],
    });
}

// ============================================================
//  CONSUMPTIONS ROW — Add / Remove
// ============================================================
function addConsumptionRow() {
    const $tbody = $('#consumptionsBody');
    const idx = $tbody.children().length;
    const insumos = DATA.insumos || [];
    const hoy = DATA.hoy || new Date().toISOString().split('T')[0];
    let opts = '<option value="">Seleccione...</option>';
    insumos.forEach((i) => {
        const stock = parseFloat(i.stock_actual || 0);
        opts += `<option value="${i.id}" data-stock="${stock}" data-simbolo="${Helpers.escapeHtml(i.simbolo || '')}">
            ${Helpers.escapeHtml(i.nombre_insumo)} (Stock: ${stock} ${Helpers.escapeHtml(i.simbolo || '')})
        </option>`;
    });
    const row = `
        <tr>
            <td>
                <select class="form-select form-select-sm" name="consumptions[${idx}][id_insumo]" required>${opts}</select>
            </td>
            <td class="text-center align-middle stock-display">—</td>
            <td>
                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm cantidad-input" name="consumptions[${idx}][cantidad_usada]" required placeholder="0.00">
                <small class="stock-hint text-muted"></small>
            </td>

            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    $tbody.append(row);
    updateStockHint($tbody.find('tr:last').find('.cantidad-input'));
}

function updateStockHint($input) {
    const $tr = $input.closest('tr');
    const $select = $tr.find('select');
    const $option = $select.find('option:selected');
    const stock = parseFloat($option.data('stock') || 0);
    const cantidad = parseFloat($input.val()) || 0;
    const $hint = $tr.find('.stock-hint');
    if (cantidad > stock) {
        $hint.html('<span class="text-danger fw-semibold"><i class="fas fa-exclamation-triangle"></i> Stock disponible: ' + stock.toFixed(2) + '</span>');
        $input.addClass('is-invalid');
    } else {
        $hint.html(cantidad > 0 ? 'Stock: ' + stock.toFixed(2) : '');
        $input.removeClass('is-invalid');
    }
}

$(document).on('change', '#consumptionsBody select[name$="[id_insumo]"]', function () {
    const $option = $(this).find('option:selected');
    const stock = parseFloat($option.data('stock') || 0);
    const simbolo = $option.data('simbolo') || '';
    const $tr = $(this).closest('tr');
    $tr.find('.stock-display').text(stock.toFixed(2) + ' ' + simbolo);
    updateStockHint($tr.find('.cantidad-input'));
});

$(document).on('input', '#consumptionsBody .cantidad-input', function () {
    updateStockHint($(this));
});

$(document).on('click', '.btn-remove-row', function () {
    $(this).closest('tr').remove();
});

// ============================================================
//  ASSIGN MODAL EVENTS
// ============================================================
$('#btnAssignTask').on('click', function () {
    $('#assignTaskForm')[0].reset();
    $('#assignTaskForm input[name="id_asignacion"]').val('');
    $('#consumptionsBody').empty();
    $('#toolsBody').empty();
    $('#assignTaskForm input[name="fecha_asignacion"]').val(DATA.hoy || new Date().toISOString().split('T')[0]);
    $('#assignTaskModal .modal-title').text('Asignar Tarea');
    $('#assignTaskModal .btn-primary').text('Guardar Asignación');
    $('#assignTaskModal').modal({ focus: false }).modal('show');
});

$('#btnAddConsumptionRow').on('click', addConsumptionRow);

// ============================================================
//  TOOLS — Add / Remove rows
// ============================================================
function addToolRow() {
    const $tbody = $('#toolsBody');
    const idx = $tbody.children().length;
    const herramientas = DATA.herramientas || [];
    let opts = '<option value="">Seleccione...</option>';
    herramientas.forEach((h) => {
        if (h.estado !== 'disponible') return;
        opts += `<option value="${h.id}" data-cantidad="${h.cantidad || 0}">${Helpers.escapeHtml(h.nombre_herramienta)} (${h.cantidad || 0} disp.)</option>`;
    });
    const row = `
        <tr>
            <td>
                <select class="form-select form-select-sm" name="tools[${idx}][id_herramienta]" required>${opts}</select>
            </td>
            <td class="text-center align-middle disp-display">—</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-tool-row"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    $tbody.append(row);
}

$(document).on('change', '#toolsBody select[name$="[id_herramienta]"]', function () {
    const $option = $(this).find('option:selected');
    const cantidad = parseFloat($option.data('cantidad') || 0);
    const $tr = $(this).closest('tr');
    $tr.find('.disp-display').text(cantidad > 0 ? cantidad.toFixed(0) : '—');
});

$(document).on('click', '.btn-remove-tool-row', function () {
    $(this).closest('tr').remove();
});

$('#btnAddToolRow').on('click', addToolRow);

$('#assignTaskForm').on('submit', function (e) {
    e.preventDefault();

    const $form = $(this);
    if (!validateForm($form, assignRules)) return;

    const data = {
        nombre_tarea: $form.find('[name="nombre_tarea"]').val().trim(),
        descripcion: $form.find('[name="descripcion"]').val().trim(),
        id_trabajador: parseInt($form.find('[name="id_trabajador"]').val()) || 0,
        id_lote: parseInt($form.find('[name="id_lote"]').val()) || 0,
        fecha_asignacion: $form.find('[name="fecha_asignacion"]').val() || DATA.hoy,
        consumptions: [],
        tools: [],
    };

    if (!data.nombre_tarea) {
        Helpers.toast('error', 'Debe ingresar el nombre de la tarea.');
        return;
    }
    if (!data.id_trabajador || !data.id_lote) {
        Helpers.toast('error', 'Debe seleccionar trabajador y lote.');
        return;
    }

    let hasConsumptionError = false;
    $('#consumptionsBody tr').each(function () {
        const $row = $(this);
        const idInsumo = parseInt($row.find('select').val()) || 0;
        const cantidad = parseFloat($row.find('input[name$="[cantidad_usada]"]').val()) || 0;

        if (idInsumo) {
            if (cantidad <= 0) {
                hasConsumptionError = true;
                $row.find('.cantidad-input').addClass('is-invalid');
                return;
            }
            const stockDisp = parseFloat($row.find('select option:selected').data('stock') || 0);
            if (cantidad > stockDisp) {
                hasConsumptionError = true;
                $row.find('.cantidad-input').addClass('is-invalid');
            }
            data.consumptions.push({ id_insumo: idInsumo, cantidad_usada: cantidad });
        }
    });

    if (hasConsumptionError) {
        Helpers.toast('error', 'Verifique las cantidades de insumos: deben ser mayores a 0 y no superar el stock disponible.');
        return;
    }

    $('#toolsBody tr').each(function () {
        const $row = $(this);
        const idHerramienta = parseInt($row.find('select').val()) || 0;
        if (!idHerramienta) return;
        data.tools.push({ id_herramienta: idHerramienta });
    });

    const idAsignacion = parseInt($form.find('[name="id_asignacion"]').val()) || 0;
    const isEdit = idAsignacion > 0;
    const action = isEdit ? 'edit_ajax' : 'assign_ajax';
    if (isEdit) data.id_asignacion = idAsignacion;

    Ajax.post(`${baseUrl}?action=${action}`, data)
        .then((r) => {
            if (r.success) {
                Helpers.toast('success', isEdit ? 'Tarea actualizada correctamente' : 'Tarea asignada correctamente');
                $('#assignTaskModal').modal('hide');
                assignmentsTable.ajax.reload(null, false);
            } else {
                Helpers.toast('error', r.message);
            }
        })
        .catch((err) => Helpers.toast('error', err));
});

$('#assignTaskModal').on('hidden.bs.modal', function () {
    Helpers.resetForm($(this).find('form'));
    $('#consumptionsBody').empty();
    $('#toolsBody').empty();
});

// ============================================================
//  COMPLETE ASSIGNMENT
// ============================================================
$(document).on('click', '.btn-complete-assign', function () {
    const row = assignmentsTable.row($(this).closest('tr')).data();
    const id = row.id_asignacion;
    $('#completeAssignId').val(id);
    $('#completeAssignForm input[name="fecha_cumplimiento"]').val(DATA.hoy || new Date().toISOString().split('T')[0]);

    $('#completeToolsContainer').html('<div class="text-muted small py-2"><div class="spinner-border spinner-border-sm" role="status"></div> Cargando herramientas...</div>');
    $('#completeAssignModal').modal({ focus: false }).modal('show');

    $.ajax({
        url: `${baseUrl}?action=get_assignment&id=${id}`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .done((r) => {
            const tools = r.tool_usages || [];
            if (tools.length === 0) {
                $('#completeToolsContainer').html('<p class="text-muted small">No se registraron herramientas en esta asignación.</p>');
                return;
            }
            const estados = ['disponible', 'requiere_mantenimiento', 'dañado'];
            const estadosOpts = estados.map(e =>
                `<option value="${e}">${e.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}</option>`
            ).join('');
            let html = '<table class="table table-sm table-bordered mb-0"><thead><tr><th>Herramienta</th><th>Estado Post-Uso</th></tr></thead><tbody>';
            tools.forEach((t) => {
                html += `<tr>
                    <td>${Helpers.escapeHtml(t.nombre_herramienta || '—')}</td>
                    <td>
                        <select class="form-select form-select-sm tool-estado" data-id-uso="${t.id_uso}">${estadosOpts}</select>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            $('#completeToolsContainer').html(html);
        })
        .fail(() => {
            $('#completeToolsContainer').html('<p class="text-danger small">Error al cargar herramientas.</p>');
        });
});

$('#completeAssignForm').on('submit', function (e) {
    e.preventDefault();
    const $form = $(this);
    if (!validateForm($form, completeRules)) return;
    const data = {
        id: parseInt($form.find('[name="id"]').val()) || 0,
        fecha_cumplimiento: $form.find('[name="fecha_cumplimiento"]').val() || DATA.hoy,
        horas_dedicadas: $form.find('[name="horas_dedicadas"]').val() || '',
        tool_estados: [],
    };
    if (!data.id) { Helpers.toast('error', 'ID inválido'); return; }

    $('#completeToolsContainer .tool-estado').each(function () {
        data.tool_estados.push({
            id_uso: parseInt($(this).data('id-uso')) || 0,
            estado: $(this).val() || 'ok',
        });
    });

    Ajax.post(`${baseUrl}?action=complete_ajax`, data)
        .then((r) => {
            if (r.success) {
                Helpers.toast('success', 'Tarea completada correctamente');
                $('#completeAssignModal').modal('hide');
                assignmentsTable.ajax.reload(null, false);
            } else {
                Helpers.toast('error', r.message);
            }
        })
        .catch((err) => Helpers.toast('error', err));
});

$('#completeAssignModal').on('hidden.bs.modal', function () {
    Helpers.resetForm($(this).find('form'));
    $('#completeToolsContainer').empty();
});

// ============================================================
//  CANCEL ASSIGNMENT
// ============================================================
$(document).on('click', '.btn-cancel-assign', function () {
    const row = assignmentsTable.row($(this).closest('tr')).data();
    const id = row.id_asignacion;
    Helpers.confirmDialog(
        '¿Cancelar asignación?',
        '¿Estás seguro de cancelar esta asignación?',
        () => {
            Ajax.post(`${baseUrl}?action=cancel_ajax`, { id })
                .then((r) => {
                    if (r.success) {
                        Helpers.toast('success', 'Asignación cancelada');
                        assignmentsTable.ajax.reload(null, false);
                    } else {
                        Helpers.toast('error', r.message);
                    }
                })
                .catch((err) => Helpers.toast('error', err));
        },
        'Sí, cancelar'
    );
});

// ============================================================
//  VIEW ASSIGNMENT DETAIL
// ============================================================
$(document).on('click', '.btn-view-assign', function () {
    const row = assignmentsTable.row($(this).closest('tr')).data();
    const id = row ? row.id_asignacion : 0;
    if (!id) return;
    $('#detailAssignBody').html('<div class="text-center py-4"><div class="spinner-border" role="status"></div> Cargando...</div>');
    $('#detailAssignModal').modal({ focus: false }).modal('show');

    $.ajax({
        url: `${baseUrl}?action=get_assignment&id=${id}`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .done((r) => {
            if (!r.success) {
                $('#detailAssignBody').html(`<div class="alert alert-danger mb-0">${Helpers.escapeHtml(r.message)}</div>`);
                return;
            }
            const a = r.assignment || {};
            const consumos = r.consumptions || [];
            const tools = r.tool_usages || [];

            let html = `
                <div class="card border mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Información de la Asignación</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <small class="text-muted d-block">Trabajador</small>
                                <span class="fw-semibold">${Helpers.escapeHtml(a.nombre_trabajador || '')} ${Helpers.escapeHtml(a.apellido_trabajador || '')}</span>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="text-muted d-block">Tarea</small>
                                <span class="fw-semibold">${Helpers.escapeHtml(a.nombre_tarea || '')}</span>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="text-muted d-block">Lote</small>
                                <span class="fw-semibold">${Helpers.escapeHtml(a.codigo_lote || '—')}</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <small class="text-muted d-block">Fecha Asignación</small>
                                <span class="fw-semibold">${Helpers.escapeHtml(a.fecha_asignacion || '—')}</span>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="text-muted d-block">Estatus</small>
                                <span class="fw-semibold">${Helpers.escapeHtml(a.estatus_tarea || '—')}</span>
                            </div>
                            ${a.estatus_tarea === 'completada' ? `
                            <div class="col-md-4 mb-2">
                                <small class="text-muted d-block">Fecha Cumplimiento</small>
                                <span class="fw-semibold">${Helpers.escapeHtml(a.fecha_cumplimiento || '—')}</span>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="text-muted d-block">Horas Dedicadas</small>
                                <span class="fw-semibold">${a.horas_dedicadas ? parseFloat(a.horas_dedicadas).toFixed(2) + ' h' : '—'}</span>
                            </div>` : ''}
                        </div>
                    </div>
                </div>

                <div class="card border-success mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center py-2 bg-success bg-opacity-10 text-success">
                        <h6 class="mb-0"><i class="fas fa-boxes"></i> Insumos Consumidos</h6>
                    </div>
                    <div class="card-body p-2">
                        ${consumos.length === 0 ? '<p class="text-muted mb-0">No se registraron consumos.</p>' : `
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr><th>Insumo</th><th>Cantidad</th><th>Costo Unit.</th><th>Subtotal</th><th>Fecha</th></tr></thead>
                            <tbody>
                                ${(() => {
                                    let total = 0;
                                    const rows = consumos.map(c => {
                                        const sub = parseFloat(c.cantidad_usada || 0) * parseFloat(c.costo_unitario || 0);
                                        total += sub;
                                        return `<tr>
                                            <td>${Helpers.escapeHtml(c.nombre_insumo || '—')}</td>
                                            <td>${parseFloat(c.cantidad_usada || 0).toFixed(2)} ${Helpers.escapeHtml(c.simbolo || '')}</td>
                                            <td>$${parseFloat(c.costo_unitario || 0).toFixed(2)}</td>
                                            <td><strong>$${sub.toFixed(2)}</strong></td>
                                            <td>${Helpers.escapeHtml(c.fecha_consumo || '—')}</td>
                                        </tr>`;
                                    }).join('');
                                    return rows + `<tr class="table-active fw-bold">
                                        <td colspan="3" class="text-end">Total:</td>
                                        <td>$${total.toFixed(2)}</td>
                                        <td></td>
                                    </tr>`;
                                })()}
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-end mt-2">
                            <strong>Gasto total en insumos: $${(() => { let t = 0; consumos.forEach(c => { t += parseFloat(c.cantidad_usada || 0) * parseFloat(c.costo_unitario || 0); }); return t.toFixed(2); })()}</strong>
                        </div>`}
                    </div>
                </div>

                <div class="card border-primary">
                    <div class="card-header d-flex justify-content-between align-items-center py-2 bg-primary bg-opacity-10 text-primary">
                        <h6 class="mb-0"><i class="fas fa-wrench"></i> Uso de Herramientas</h6>
                    </div>
                    <div class="card-body p-2">
                        ${tools.length === 0 ? '<p class="text-muted mb-0">No se registró uso de herramientas.</p>' : `
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr><th>Herramienta</th><th>Fecha</th></tr></thead>
                            <tbody>
                                ${tools.map(t => `<tr>
                                    <td>${Helpers.escapeHtml(t.nombre_herramienta || '—')}</td>
                                    <td>${Helpers.escapeHtml(t.fecha_uso || '—')}</td>
                                </tr>`).join('')}
                            </tbody>
                        </table>`}
                    </div>
                </div>
            `;
            $('#detailAssignBody').html(html);
        })
        .fail(() => {
            $('#detailAssignBody').html('<div class="alert alert-danger mb-0">Error al cargar detalle.</div>');
        });
});

// ============================================================
//  EDIT ASSIGNMENT
// ============================================================
$(document).on('click', '.btn-edit-assign', function () {
    const row = assignmentsTable.row($(this).closest('tr')).data();
    const id = row ? row.id_asignacion : 0;
    if (!id) return;

    $.ajax({
        url: `${baseUrl}?action=get_assignment&id=${id}`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .done((r) => {
            if (!r.success) {
                Helpers.toast('error', r.message);
                return;
            }
            const a = r.assignment || {};
            const consumos = r.consumptions || [];
            const tools = r.tool_usages || [];

            $('#assignTaskForm')[0].reset();
            $('#consumptionsBody').empty();
            $('#toolsBody').empty();

            $('#assignTaskForm input[name="id_asignacion"]').val(a.id_asignacion);
            $('#assignTaskForm input[name="nombre_tarea"]').val(a.nombre_tarea || '');
            $('#assignTaskForm textarea[name="descripcion"]').val(a.descripcion || '');
            $('#assignTaskForm select[name="id_trabajador"]').val(a.id_trabajador || '');
            $('#assignTaskForm select[name="id_lote"]').val(a.id_lote || '');
            $('#assignTaskForm input[name="fecha_asignacion"]').val(a.fecha_asignacion || DATA.hoy);

            consumos.forEach(function (c) {
                addConsumptionRow();
                const $row = $('#consumptionsBody tr:last');
                $row.find('select').val(c.id_insumo).trigger('change');
                $row.find('.cantidad-input').val(c.cantidad_usada);
            });

            tools.forEach(function (t) {
                addToolRow();
                const $row = $('#toolsBody tr:last');
                $row.find('select').val(t.id_herramienta).trigger('change');
            });

            $('#assignTaskModal .modal-title').text('Editar Tarea');
            $('#assignTaskModal .btn-primary').text('Guardar Cambios');
            $('#assignTaskModal').modal({ focus: false }).modal('show');
        })
        .fail(() => {
            Helpers.toast('error', 'Error al cargar datos de la asignación.');
        });
});

// ============================================================
//  INIT
// ============================================================
$(document).ready(function () {
    initAssignmentsTable();
    setupRealTimeValidation($('#assignTaskForm'), assignRules);
    setupRealTimeValidation($('#completeAssignForm'), completeRules);
});
