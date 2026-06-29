import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { validateField, validateSelect } from '../utils/validation.js';
import * as C from '../utils/components.js';

let preciosTable = null;
let insumosCatalogo = [];
let insumosDetalle = [];
let editingId = 0;

const baseUrl = `${window.BASE_URL || '/'}precios`;

$(document).ready(function () {

  // ============================================================
  //  DATA TABLE
  // ============================================================

  function initDataTable() {
    try {
      if ($.fn.DataTable.isDataTable('#preciosTable')) {
        $('#preciosTable').DataTable().destroy();
      }
      preciosTable = $('#preciosTable').DataTable({
        ajax: {
          url: `${baseUrl}?action=get_prices`,
          method: 'GET',
          dataType: 'json',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          dataSrc: 'prices',
        },
        columns: [
          { data: 'planta_nombre', render: (data) => data || '<span class="text-muted">&mdash;</span>' },
          { data: 'precio_final_sugerido', className: 'text-end', render: (data) => `<strong>${Helpers.formatCurrencyBs(data)}</strong>` },
          { data: 'vigente', className: 'text-center', render: (data) => data == 1 ? '<span class="badge bg-success">Vigente</span>' : '<span class="badge bg-secondary">No Vigente</span>' },
          { data: null, orderable: false, render: (row) => C.btnGroup(
              C.btnView('btn-view'),
              C.btnEdit('btn-edit'),
              C.btnDelete('btn-delete'),
            ),
          },
        ],
        pageLength: 10,
        autoWidth: false,
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
        },
        order: [[0, 'desc']],
        dom: 'lfrtip',
      });
    } catch (e) {
      console.error('Error al inicializar DataTable:', e);
    }
  }

  initDataTable();

  // ============================================================
  //  CARGAR CATÁLOGOS
  // ============================================================

  function cargarInsumos() {
    return Ajax.get(`${baseUrl}?action=get_insumos`)
      .then((r) => {
        if (r.success) insumosCatalogo = r.insumos || [];
      });
  }

  function cargarLotes(selectedId) {
    return Ajax.get(`${baseUrl}?action=get_lotes`)
      .then((r) => {
        if (!r.success) return;
        const $sel = $('#selLote');
        const currentVal = $sel.val();
        $sel.empty().append('<option value="">Seleccione un lote...</option>');
        (r.lotes || []).forEach((l) => {
          $sel.append(`<option value="${l.id_lote}" data-planta="${Helpers.escapeHtml(l.planta_nombre)}" data-categoria="${Helpers.escapeHtml(l.categoria)}" data-cantidad="${l.cantidad_actual}">#${l.id_lote} - ${Helpers.escapeHtml(l.planta_nombre)}</option>`);
        });
        if (selectedId) $sel.val(selectedId);
        else if (currentVal) $sel.val(currentVal);
      });
  }

  // Insumos para selector pequeño
  function llenarSelectorInsumos() {
    const $sel = $('#selInsumo');
    $sel.empty().append('<option value="">Seleccione...</option>');
    insumosCatalogo.forEach((i) => {
      $sel.append(`<option value="${i.id_insumo}" data-nombre="${Helpers.escapeHtml(i.nombre_insumo)}" data-costo="${i.costo_unitario}">${Helpers.escapeHtml(i.nombre_insumo)}</option>`);
    });
  }

  // ============================================================
  //  MODAL NUEVO / EDITAR
  // ============================================================

  function calcularPrecioSugerido() {
    const base = parseFloat($('#precioPlantaBase').val()) || 0;
    const totalInsumos = insumosDetalle.reduce((s, d) => s + (parseFloat(d.monto) || 0), 0);
    const gan = parseFloat($('#porcentajeGanancia').val()) || 0;
    const precio = (base + totalInsumos) * (1 + gan / 100);
    $('#precioFinalSugerido').val(precio.toFixed(2));
  }

  function actualizarTablaInsumos() {
    const $body = $('#insumosDetalleBody');
    if (insumosDetalle.length === 0) {
      $body.html('<tr id="noInsumosRow"><td colspan="3" class="text-center text-muted">No hay insumos agregados</td></tr>');
      $('#totalInsumosLabel').text('$0.00');
      return;
    }
    let html = '';
    let total = 0;
    insumosDetalle.forEach((d, i) => {
      const monto = parseFloat(d.monto) || 0;
      total += monto;
      html += `<tr>
        <td>${Helpers.escapeHtml(d.nombre_insumo)}</td>
        <td class="text-end">${Helpers.formatCurrencyBs(monto)}</td>
        <td class="text-center">
          <button class="btn btn-sm btn-outline-danger btn-remove-insumo" data-idx="${i}"><i class="fas fa-times"></i></button>
        </td>
      </tr>`;
    });
    $body.html(html);
    $('#totalInsumosLabel').text(Helpers.formatCurrencyBs(total));
    calcularPrecioSugerido();
  }

  function abrirModalNuevo() {
    editingId = 0;
    $('#editId').val(0);
    $('#precioModalTitle').text('Nuevo Cálculo de Precio');
    $('#selLote').val('');
    $('#txtCategoria').val('');
    $('#txtCantidad').val('');
    $('#precioPlantaBase').val('');
    insumosDetalle = [];
    $('#porcentajeGanancia').val(30);
    $('#precioFinalSugerido').val('');
    actualizarTablaInsumos();
    cargarLotes();
    llenarSelectorInsumos();
    $('#precioModal').modal('show');
  }

  function abrirModalEditar(row) {
    editingId = row.id;
    $('#editId').val(row.id);
    $('#precioModalTitle').text('Editar Cálculo de Precio');
    $('#precioPlantaBase').val(parseFloat(row.precio_planta_base).toFixed(2));
    $('#porcentajeGanancia').val(parseFloat(row.porcentaje_ganancia).toFixed(2));
    $('#precioFinalSugerido').val(parseFloat(row.precio_final_sugerido).toFixed(2));

    const $lote = $('#selLote');
    cargarLotes(row.id_lote).then(() => {
      if (row.id_lote) {
        $lote.val(row.id_lote).trigger('change');
      }
    });
    llenarSelectorInsumos();

    Ajax.get(`${baseUrl}?action=get_detalle&id_calculo=${row.id}`)
      .then((r) => {
        if (r.success && r.detalles) {
          insumosDetalle = r.detalles.map((d) => ({
            id_detalle: d.id_detalle,
            id_insumo: d.id_insumo,
            nombre_insumo: d.nombre_insumo,
            monto: parseFloat(d.monto),
          }));
        } else {
          insumosDetalle = [];
        }
        actualizarTablaInsumos();
      })
      .catch(() => { insumosDetalle = []; actualizarTablaInsumos(); });

    $('#precioModal').modal('show');
  }


  // ============================================================
  //  EVENTOS MODAL
  // ============================================================

  $('#btnNuevoPrecio').on('click', abrirModalNuevo);

  $(document).on('change', '#selLote', function () {
    const opt = $(this).find('option:selected');
    $('#txtCategoria').val(opt.data('categoria') || '');
    $('#txtCantidad').val(opt.data('cantidad') || '');
  });

  $(document).on('input', '#precioPlantaBase, #porcentajeGanancia', calcularPrecioSugerido);
  $('#btnRecalcular').on('click', calcularPrecioSugerido);

  // Agregar insumo desde selector
  $('#btnAgregarInsumo').on('click', function () {
    llenarSelectorInsumos();
    $('#selInsumo').val('');
    $('#insumoCostoUnitario').val('');
    $('#insumoCantidad').val('');
    $('#insumoMontoTotal').val('');
    $('#insumoSelectorModal').modal('show');
  });

  $('#selInsumo').on('change', function () {
    const opt = $(this).find('option:selected');
    const costo = parseFloat(opt.data('costo')) || 0;
    $('#insumoCostoUnitario').val(costo > 0 ? '$' + costo.toFixed(2) : '');
    calcMontoTotal();
  });

  $('#insumoCantidad').on('input', calcMontoTotal);

  function calcMontoTotal() {
    const opt = $('#selInsumo').find('option:selected');
    const costo = parseFloat(opt.data('costo')) || 0;
    const cant = parseFloat($('#insumoCantidad').val()) || 0;
    const total = cant * costo;
    $('#insumoMontoTotal').val(total > 0 ? '$' + total.toFixed(2) : '');
  }

  $('#btnConfirmarInsumo').on('click', function () {
    const idInsumo = parseInt($('#selInsumo').val()) || 0;
    const cantidad = parseFloat($('#insumoCantidad').val()) || 0;
    if (!idInsumo) { Helpers.toast('error', 'Seleccione un insumo.'); return; }
    if (cantidad <= 0) { Helpers.toast('error', 'Ingrese una cantidad válida.'); return; }

    const insumo = insumosCatalogo.find((i) => i.id_insumo === idInsumo);
    if (!insumo) return;

    const costoUnitario = parseFloat(insumo.costo_unitario) || 0;
    const monto = parseFloat((cantidad * costoUnitario).toFixed(2));

    const existente = insumosDetalle.findIndex((d) => d.id_insumo === idInsumo);
    if (existente >= 0) {
      insumosDetalle[existente].monto = parseFloat((parseFloat(insumosDetalle[existente].monto) + monto).toFixed(2));
    } else {
      insumosDetalle.push({
        id_detalle: 0,
        id_insumo: idInsumo,
        nombre_insumo: insumo.nombre_insumo,
        monto: monto,
      });
    }
    actualizarTablaInsumos();
    $('#insumoSelectorModal').modal('hide');
  });

  // Eliminar insumo
  $(document).on('click', '.btn-remove-insumo', function () {
    const idx = parseInt($(this).data('idx'));
    if (idx >= 0 && idx < insumosDetalle.length) {
      insumosDetalle.splice(idx, 1);
      actualizarTablaInsumos();
    }
  });

  // Guardar
  $('#btnGuardarPrecio').on('click', function () {
    const idLote = parseInt($('#selLote').val()) || 0;
    const precioPlantaBase = parseFloat($('#precioPlantaBase').val()) || 0;
    const porcentajeGanancia = parseFloat($('#porcentajeGanancia').val()) || 0;
    const precioFinalSugerido = parseFloat($('#precioFinalSugerido').val()) || 0;

    if (!idLote) { Helpers.toast('error', 'Seleccione un lote.'); return; }
    if (precioPlantaBase <= 0) { Helpers.toast('error', 'Ingrese un precio base.'); return; }
    if (precioFinalSugerido <= 0) { Helpers.toast('error', 'El precio sugerido debe ser mayor a cero.'); return; }

    const totalInsumos = insumosDetalle.reduce((s, d) => s + (parseFloat(d.monto) || 0), 0);

    const data = {
      id_lote: idLote,
      precio_planta_base: precioPlantaBase,
      costo_total_insumo: totalInsumos,
      porcentaje_ganancia: porcentajeGanancia,
      precio_final_sugerido: precioFinalSugerido,
      fecha_calculo: new Date().toISOString().split('T')[0],
      detalles: insumosDetalle.map((d) => ({
        id_detalle: d.id_detalle || 0,
        id_insumo: d.id_insumo,
        monto: parseFloat(d.monto) || 0,
      })),
    };

    const isEdit = editingId > 0;
    const url = `${baseUrl}?action=${isEdit ? 'edit_ajax' : 'add_ajax'}`;
    if (isEdit) data.id = editingId;

    Ajax.post(url, data)
      .then((r) => {
        if (r.success) {
          Helpers.toast('success', r.message);
          $('#precioModal').modal('hide');
          if (preciosTable) preciosTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', r.message);
        }
      })
      .catch((err) => Helpers.toast('error', err));
  });

  // ============================================================
  //  ACCIONES EN TABLA
  // ============================================================

  $(document).on('click', '.btn-view', function () {
    const row = preciosTable.row($(this).closest('tr')).data();
    const id = row.id;

    Ajax.get(`${baseUrl}?action=get_detalle&id_calculo=${id}`)
      .then((r) => {
        const detalles = (r.success && r.detalles) ? r.detalles : [];
        const filasInsumos = detalles.map((d) =>
          `<tr>
            <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0">${Helpers.escapeHtml(d.nombre_insumo)}</td>
            <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:right;font-weight:500">${Helpers.formatCurrencyBs(d.monto)}</td>
          </tr>`
        ).join('') || '<tr><td colspan="2" style="padding:12px;text-align:center;color:#9e9e9e">Sin insumos registrados</td></tr>';

        const vigenteHtml = row.vigente == 1
          ? '<span style="background:#2e7d32;color:#fff;padding:4px 12px;border-radius:12px;font-size:13px;font-weight:500">Vigente</span>'
          : '<span style="background:#9e9e9e;color:#fff;padding:4px 12px;border-radius:12px;font-size:13px;font-weight:500">No Vigente</span>';

        Swal.fire({
          title: '',
          html: `
            <div style="text-align:left;font-family:'Poppins',sans-serif">
              <div style="background:linear-gradient(135deg,#e5a835,#c48f2a);color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;margin:-20px -20px 0">
                <div style="font-size:18px;font-weight:600">Cálculo de Precio #${id}</div>
                <div style="font-size:13px;opacity:0.9;margin-top:4px">${Helpers.escapeHtml(row.planta_nombre || '')} · Lote #${row.id_lote}</div>
              </div>
              <div style="padding:20px 0">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                  <div style="background:#fafafa;border-radius:10px;padding:14px 16px;border:1px solid #f0f0f0">
                    <div style="font-size:12px;color:#9e9e9e;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Precio Base</div>
                    <div style="font-size:18px;font-weight:600;color:#424242">${Helpers.formatCurrencyBs(row.precio_planta_base)}</div>
                  </div>
                  <div style="background:#fafafa;border-radius:10px;padding:14px 16px;border:1px solid #f0f0f0">
                    <div style="font-size:12px;color:#9e9e9e;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">% Ganancia</div>
                    <div style="font-size:18px;font-weight:600;color:#424242">${parseFloat(row.porcentaje_ganancia).toFixed(1)}%</div>
                  </div>
                  <div style="background:#fafafa;border-radius:10px;padding:14px 16px;border:1px solid #f0f0f0">
                    <div style="font-size:12px;color:#9e9e9e;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Costo Insumos</div>
                    <div style="font-size:18px;font-weight:600;color:#424242">${Helpers.formatCurrencyBs(row.costo_total_insumo)}</div>
                  </div>
                  <div style="background:#fef3e2;border-radius:10px;padding:14px 16px;border:1px solid #f5c85c">
                    <div style="font-size:12px;color:#c48f2a;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Precio Sugerido</div>
                    <div style="font-size:20px;font-weight:700;color:#c48f2a">${Helpers.formatCurrencyBs(row.precio_final_sugerido)}</div>
                  </div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding:0 4px">
                  <div>
                    <span style="font-size:13px;color:#9e9e9e;margin-right:8px">Vigencia:</span>
                    ${vigenteHtml}
                  </div>
                  <div style="font-size:13px;color:#9e9e9e">${row.fecha_calculo || '—'}</div>
                </div>
                <hr style="margin:20px 0;border:none;border-top:1px solid #f0f0f0">
                <div style="font-size:14px;font-weight:600;color:#424242;margin-bottom:12px">Insumos utilizados</div>
                <div style="border:1px solid #f0f0f0;border-radius:10px;overflow:hidden">
                  <table style="width:100%;border-collapse:collapse">
                    <thead>
                      <tr style="background:#fafafa">
                        <th style="padding:10px 12px;text-align:left;font-size:12px;color:#9e9e9e;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #f0f0f0">Insumo</th>
                        <th style="padding:10px 12px;text-align:right;font-size:12px;color:#9e9e9e;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #f0f0f0">Monto</th>
                      </tr>
                    </thead>
                    <tbody>${filasInsumos}</tbody>
                  </table>
                </div>
              </div>
            </div>
          `,
          confirmButtonText: 'Cerrar',
          confirmButtonColor: '#e5a835',
          width: 620,
          padding: '20px',
          showCloseButton: true,
        });
      })
      .catch(() => Helpers.toast('error', 'Error al cargar detalles'));
  });

  $(document).on('click', '.btn-edit', function () {
    const row = preciosTable.row($(this).closest('tr')).data();
    abrirModalEditar(row);
  });

  $(document).on('click', '.btn-delete', function () {
    const row = preciosTable.row($(this).closest('tr')).data();
    const id = row.id;
    const info = `#${row.id_lote} - ${row.planta_nombre || ''}`;

    Helpers.confirmDialog(
      '¿Desactivar cálculo?',
      `¿Deseas desactivar el cálculo de precio <strong>${Helpers.escapeHtml(info)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              if (preciosTable) preciosTable.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          })
          .catch((err) => Helpers.toast('error', err));
      },
      'Sí, desactivar'
    );
  });

  // ============================================================
  //  Cargar catálogos al inicio
  // ============================================================

  cargarInsumos();
});
