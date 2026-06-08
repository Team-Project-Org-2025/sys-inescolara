import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}compras`;
  let comprasTable = null;
  let editingId = null;

  const compraRules = {
    id_proveedor: 'select',
    fecha_compra: 'fechaFuturaCheck',
  };

  const CATEGORIAS = [
    { value: 'germinado', label: 'Germinado' },
    { value: 'en_crecimiento', label: 'En Crecimiento' },
    { value: 'para_cosechar', label: 'Para Cosechar' },
    { value: 'maduro', label: 'Maduro' },
  ];

  function addItemRow(tipo, idItem, nombre, cantidad, costoUnitario, categoriaLote, idUbicacion) {
    const subtotal = (parseFloat(cantidad) || 0) * (parseFloat(costoUnitario) || 0);
    const row = `
      <tr>
        <td>
          <select class="form-select form-select-sm item-tipo" required>
            <option value="insumo" ${tipo === 'insumo' ? 'selected' : ''}>Insumo</option>
            <option value="herramienta" ${tipo === 'herramienta' ? 'selected' : ''}>Herramienta</option>
            <option value="planta" ${tipo === 'planta' ? 'selected' : ''}>Planta</option>
          </select>
        </td>
        <td>
          <div class="d-flex flex-column gap-1">
            <div class="d-flex gap-1">
              <select class="form-select form-select-sm item-select flex-grow-1" required>
                <option value="">Seleccione...</option>
              </select>
              <button type="button" class="btn btn-success btn-add-item-quick" title="Crear nueva planta" style="display:none; line-height:1; flex-shrink: 0;">
                <i class="fas fa-plus"></i>
              </button>
            </div>
            <div class="d-flex gap-1 planta-extras" ${tipo !== 'planta' ? 'style="display:none"' : ''}>
              <select class="form-select form-select-sm item-categoria" style="min-width:120px">
                <option value="germinado">Germinado</option>
                <option value="en_crecimiento" ${categoriaLote === 'en_crecimiento' ? 'selected' : ''}>En Crecimiento</option>
                <option value="para_cosechar" ${categoriaLote === 'para_cosechar' ? 'selected' : ''}>Para Cosechar</option>
                <option value="maduro" ${categoriaLote === 'maduro' ? 'selected' : ''}>Maduro</option>
              </select>
              <select class="form-select form-select-sm item-ubicacion" style="min-width:120px">
                <option value="">Ubicación...</option>
                ${(window.UBICACIONES || []).map(u => `<option value="${u.id}" ${idUbicacion == u.id ? 'selected' : ''}>${Helpers.escapeHtml(u.nombre)}</option>`).join('')}
              </select>
            </div>
          </div>
        </td>
        <td>
          <input type="number" class="form-control form-control-sm text-end item-cantidad" step="0.01" min="0.01" value="${cantidad || 1}" required>
        </td>
        <td>
          <input type="number" class="form-control form-control-sm text-end item-costo" step="0.01" min="0" value="${costoUnitario || 0}" required>
        </td>
        <td class="text-end item-subtotal">${Helpers.formatCurrencyBs(subtotal)}</td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item"><i class="fas fa-times"></i></button>
        </td>
      </tr>`;
    $('#itemsBody').append(row);

    const $row = $('#itemsBody tr:last');
    $row.find('.btn-add-item-quick').toggle(tipo === 'planta');
    togglePlantaFields($row, tipo === 'planta');
    loadItemOptions($row, tipo);
    if (idItem) $row.find('.item-select').val(idItem);
    updateItemSubtotal($row);
  }

  function togglePlantaFields($row, isPlanta) {
    $row.find('.planta-extras').toggle(isPlanta);
  }

  function loadItemOptions($row, tipo, callback) {
    const $select = $row.find('.item-select');
    $select.empty().append('<option value="">Seleccione...</option>');

    const urls = {
      insumo: `${window.BASE_URL || '/'}supplies?action=get_supplies`,
      herramienta: `${window.BASE_URL || '/'}tools?action=get_tools`,
      planta: `${window.BASE_URL || '/'}plants?action=get_plants`,
    };

    $.ajax({
      url: urls[tipo],
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).done((r) => {
      if (!r.success) return;
      const listMap = { insumo: 'supplies', herramienta: 'tools', planta: 'plants' };
      const list = r[listMap[tipo]] || [];
      list.forEach((item) => {
        const labelMap = { insumo: 'nombre_insumo', herramienta: 'nombre_herramienta', planta: 'nombre_comun' };
        const idMap = { insumo: item.id_insumo || item.id, herramienta: item.id_herramienta || item.id, planta: item.id };
        const label = item[labelMap[tipo]] || '';
        $select.append(`<option value="${Helpers.escapeHtml(idMap[tipo])}">${Helpers.escapeHtml(label)}</option>`);
      });
      if (typeof callback === 'function') callback();
    });
  }

  function updateItemSubtotal($row) {
    const cant = parseFloat($row.find('.item-cantidad').val()) || 0;
    const costo = parseFloat($row.find('.item-costo').val()) || 0;
    $row.find('.item-subtotal').text(Helpers.formatCurrencyBs(cant * costo));
    updateTotals();
  }

  function updateTotals() {
    let total = 0;
    $('#itemsBody tr').each(function () {
      const cant = parseFloat($(this).find('.item-cantidad').val()) || 0;
      const costo = parseFloat($(this).find('.item-costo').val()) || 0;
      total += cant * costo;
    });
    $('#itemsTotal').text(Helpers.formatCurrencyBs(total));
    $('#itemsTotalFinal').text(Helpers.formatCurrencyBs(total));
    $('#frmTotal').val(total.toFixed(2));
  }

  function getItemsData() {
    const items = [];
    let valid = true;
    $('#itemsBody tr').each(function () {
      const tipo = $(this).find('.item-tipo').val();
      const idItem = parseInt($(this).find('.item-select').val()) || 0;
      const cantidad = parseFloat($(this).find('.item-cantidad').val()) || 0;
      const costo = parseFloat($(this).find('.item-costo').val()) || 0;
      if (!tipo || !idItem || cantidad <= 0) {
        valid = false;
        return;
      }
      const item = { tipo_item: tipo, id_item: idItem, cantidad, costo_unitario: costo };
      if (tipo === 'planta') {
        item.categoria_lote = $(this).find('.item-categoria').val() || 'germinado';
        const ubi = $(this).find('.item-ubicacion').val();
        if (ubi) item.id_ubicacion = parseInt(ubi);
      }
      items.push(item);
    });
    return valid ? items : null;
  }

  // ============================================================
  //  Eventos items
  // ============================================================

  $(document).on('change', '.item-tipo', function () {
    const $row = $(this).closest('tr');
    const tipo = $(this).val();
    loadItemOptions($row, tipo);
    $row.find('.btn-add-item-quick').toggle(tipo === 'planta');
    togglePlantaFields($row, tipo === 'planta');
  });

  $(document).on('input', '.item-cantidad, .item-costo', function () {
    updateItemSubtotal($(this).closest('tr'));
  });



  $(document).on('click', '.btn-remove-item', function () {
    $(this).closest('tr').remove();
    updateTotals();
  });

  $(document).on('click', '.btn-add-item-quick', function () {
    const $row = $(this).closest('tr');
    const $select = $row.find('.item-select');
    const $btn = $(this);

    // Switch to inline input
    $select.hide();
    $btn.hide();
    const $inlineInput = $(`<input type="text" class="form-control form-control-sm flex-grow-1 inline-plant-input" placeholder="Nombre común..." autofocus>`);
    const $saveBtn = $(`<button type="button" class="btn btn-sm btn-success flex-shrink-0 inline-plant-save" title="Guardar"><i class="fas fa-check"></i></button>`);
    const $cancelBtn = $(`<button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0 inline-plant-cancel" title="Cancelar"><i class="fas fa-times"></i></button>`);
    $btn.parent().append($inlineInput, $saveBtn, $cancelBtn);
    $inlineInput.focus();

    function revertInline() {
      $inlineInput.remove();
      $saveBtn.remove();
      $cancelBtn.remove();
      $select.show();
      $btn.show();
    }

    function doCreate() {
      const nombre = $inlineInput.val().trim();
      if (!nombre) {
        $inlineInput.focus();
        return;
      }
      $saveBtn.prop('disabled', true);
      $inlineInput.prop('disabled', true);
      $.ajax({
        url: `${baseUrl}?action=quick_add_planta`,
        method: 'POST',
        data: { nombre_comun: nombre },
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataType: 'json',
      }).done((r) => {
        if (r.success && r.planta) {
          Helpers.toast('success', `"${Helpers.escapeHtml(nombre)}" creada`);
          revertInline();
          loadItemOptions($row, 'planta', () => {
            $select.val(String(r.planta.id)).trigger('change');
          });
        } else {
          Helpers.toast('error', r.message || 'Error al crear la planta');
          $saveBtn.prop('disabled', false);
          $inlineInput.prop('disabled', false).focus();
        }
      }).fail(() => {
        Helpers.toast('error', 'Error de conexión al crear la planta');
        $saveBtn.prop('disabled', false);
        $inlineInput.prop('disabled', false).focus();
      });
    }

    $saveBtn.on('click', doCreate);
    $cancelBtn.on('click', revertInline);
    $inlineInput.on('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); doCreate(); }
      if (e.key === 'Escape') { e.preventDefault(); revertInline(); }
    });
  });

  $('#btnAddItem').on('click', function () {
    addItemRow('insumo', null, '', 1, 0);
  });

  // ============================================================
  //  DataTable
  // ============================================================

  function initDataTable() {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('comprasTable', 5, 10);
    }
    comprasTable = $('#comprasTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_compras`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'compras',
      },
      columns: [
        { data: 'id_compra', render: (data) => `#${data}` },
        { data: 'proveedor_nombre', render: (data) => data || '<span class="text-muted">&mdash;</span>' },
        { data: 'fecha_compra' },
        {
          data: null,
          render: (d) => {
            const tp = d.tipo_comprobante || '';
            const nc = d.numero_comprobante || '';
            return nc ? `${tp} ${nc}` : tp || '<span class="text-muted">&mdash;</span>';
          },
        },
        { data: 'subtotal', className: 'text-end', render: (data) => Helpers.formatCurrencyBs(data) },
        { data: 'iva', className: 'text-end', render: (data) => Helpers.formatCurrencyBs(data) },
        { data: 'total', className: 'text-end', render: (data) => `<strong>${Helpers.formatCurrencyBs(data)}</strong>` },
        { data: 'items_count', className: 'text-center', render: (data) => data || 0 },
        {
          data: 'estado',
          className: 'text-center',
          render: (data) => {
            const badges = { pendiente: 'warning', completada: 'success', cancelada: 'secondary' };
            return `<span class="badge bg-${badges[data] || 'dark'}">${data}</span>`;
          },
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            const isPendiente = data.estado === 'pendiente';
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-info btn-detail"
                        data-id="${Helpers.escapeHtml(data.id_compra)}">
                    <i class="fas fa-eye"></i>
                </button>
                ${isPendiente ? `
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id_compra)}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-success btn-completar"
                        data-id="${Helpers.escapeHtml(data.id_compra)}"
                        data-info="#${Helpers.escapeHtml(data.id_compra)} - ${Helpers.escapeHtml(data.proveedor_nombre || '')}">
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-cancelar"
                        data-id="${Helpers.escapeHtml(data.id_compra)}"
                        data-info="#${Helpers.escapeHtml(data.id_compra)}">
                    <i class="fas fa-ban"></i>
                </button>
                ` : ''}
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id_compra)}"
                        data-info="#${Helpers.escapeHtml(data.id_compra)} - ${Helpers.escapeHtml(data.proveedor_nombre || '')}">
                    <i class="fas fa-trash"></i>
                </button>
              </div>
            `;
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
          action: () => {
            if (typeof SkeletonHelper !== 'undefined') {
              SkeletonHelper.showTableSkeleton('comprasTable', 5, 10);
            }
            comprasTable.ajax.reload(null, false);
          },
        },
      ],
    });
  }

  // ============================================================
  //  Open Add Modal
  // ============================================================

  $('#btnAddCompra').on('click', function () {
    editingId = null;
    $('#compraId').val('');
    $('#compraModalTitle').text('Nueva Compra');
    $('#compraForm')[0].reset();
    $('#frmFecha').val(new Date().toISOString().split('T')[0]);
    $('#itemsBody').empty();
    addItemRow('insumo', null, '', 1, 0);
    updateTotals();
    $('#compraModal').modal('show');
  });

  // ============================================================
  //  Submit Form (Add / Edit)
  // ============================================================

  $('#compraForm').on('submit', function (e) {
    e.preventDefault();
    if (!validateForm($(this), compraRules)) return;

    const items = getItemsData();
    if (!items) {
      Helpers.toast('error', 'Debe agregar al menos un item válido.');
      return;
    }

    let subtotal = 0;
    items.forEach((it) => { subtotal += it.cantidad * it.costo_unitario; });
    $('#frmSubtotal').val(subtotal.toFixed(2));

    const formData = new FormData(this);
    formData.set('items', JSON.stringify(items));

    const action = editingId ? 'edit_ajax' : 'add_ajax';
    if (editingId) formData.set('id', editingId);

    $.ajax({
      url: `${baseUrl}?action=${action}`,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      dataType: 'json',
    })
      .done((response) => {
        if (response.success) {
          Helpers.toast('success', response.message);
          $('#compraModal').modal('hide');
          comprasTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al guardar la compra');
      });
  });

  // ============================================================
  //  Edit
  // ============================================================

  $(document).on('click', '.btn-edit', function () {
    const id = $(this).data('id');
    editingId = id;
    $('#compraModalTitle').text('Editar Compra');
    $('#compraForm')[0].reset();
    $('#itemsBody').empty();

    $.ajax({
      url: `${baseUrl}?action=get_details&id_compra=${id}`,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).done((r) => {
      if (!r.success || !r.compra) {
        Helpers.toast('error', 'No se pudo cargar la compra.');
        return;
      }
      const c = r.compra;
      $('#compraId').val(c.id_compra);
      $('#frmProveedor').val(c.id_proveedor);
      $('#frmFecha').val(c.fecha_compra);
      $('#frmTipoComprobante').val(c.tipo_comprobante || 'Factura');
      $('#frmNumComprobante').val(c.numero_comprobante || '');
      $('#frmObservacion').val(c.observacion || '');

      if (r.details && r.details.length) {
        r.details.forEach((d) => {
          addItemRow(d.tipo_item, d.id_item, d.item_nombre, d.cantidad, d.costo_unitario, d.categoria_lote, d.id_ubicacion);
        });
      } else {
        addItemRow('insumo', null, '', 1, 0);
      }
      updateTotals();
      $('#compraModal').modal('show');
    }).fail(() => Helpers.toast('error', 'Error al cargar datos de la compra.'));
  });

  // ============================================================
  //  Detail
  // ============================================================

  $(document).on('click', '.btn-detail', function () {
    const id = $(this).data('id');
    $.ajax({
      url: `${baseUrl}?action=get_details&id_compra=${id}`,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).done((r) => {
      if (!r.success) {
        Helpers.toast('error', 'Error al cargar detalle.');
        return;
      }
      const c = r.compra || {};
      const details = r.details || [];
      let html = `
        <div class="row mb-3">
          <div class="col-md-6">
            <strong>Proveedor:</strong> ${Helpers.escapeHtml(c.proveedor_nombre || '')}<br>
            <strong>RIF:</strong> ${Helpers.escapeHtml(c.rif_proveedor || '')}<br>
            <strong>Fecha:</strong> ${c.fecha_compra || ''}
          </div>
          <div class="col-md-6 text-md-end">
            <strong>Comprobante:</strong> ${Helpers.escapeHtml(c.tipo_comprobante || '')} ${Helpers.escapeHtml(c.numero_comprobante || '')}<br>
            <strong>Estado:</strong> <span class="badge bg-${c.estado === 'completada' ? 'success' : c.estado === 'cancelada' ? 'secondary' : 'warning'}">${c.estado || ''}</span><br>
            <strong>Total:</strong> ${Helpers.formatCurrencyBs(c.total)}
          </div>
        </div>
        ${c.observacion ? `<div class="mb-3"><strong>Observación:</strong> ${Helpers.escapeHtml(c.observacion)}</div>` : ''}
        <hr>
        <div class="table-responsive">
          <table class="table table-sm table-bordered">
            <thead class="table-light">
              <tr>
                <th style="width:80px">Tipo</th>
                <th>Item</th>
                <th class="text-end" style="width:90px">Cantidad</th>
                <th class="text-end" style="width:100px">Costo Unit.</th>
                <th class="text-end" style="width:100px">Subtotal</th>
              </tr>
            </thead>
            <tbody>`;
      let subtotalItems = 0;
      details.forEach((d) => {
        subtotalItems += parseFloat(d.subtotal) || 0;
        let extra = '';
        if (d.tipo_item === 'planta') {
          const cat = d.categoria_lote || 'germinado';
          const ubi = d.id_ubicacion ? ` (Ubic. #${d.id_ubicacion})` : '';
          extra = `<br><small class="text-muted">${cat}${ubi}</small>`;
        }
        html += `<tr>
          <td><span class="badge bg-${d.tipo_item === 'insumo' ? 'primary' : 'secondary'}">${d.tipo_item}</span></td>
          <td>${Helpers.escapeHtml(d.item_nombre || '')}${extra}</td>
          <td class="text-end">${parseFloat(d.cantidad).toFixed(2)}</td>
          <td class="text-end">${Helpers.formatCurrencyBs(d.costo_unitario)}</td>
          <td class="text-end">${Helpers.formatCurrencyBs(d.subtotal)}</td>
        </tr>`;
      });
      html += `</tbody>
            <tfoot>
              <tr class="fw-bold"><td colspan="4" class="text-end">Total:</td><td class="text-end">${Helpers.formatCurrencyBs(c.total)}</td></tr>
            </tfoot>
          </table>
        </div>`;
      $('#detailBody').html(html);
      $('#detailModal').modal('show');
    }).fail(() => Helpers.toast('error', 'Error al cargar detalle.'));
  });

  // ============================================================
  //  Completar
  // ============================================================

  $(document).on('click', '.btn-completar', function () {
    const id = $(this).data('id');
    const info = $(this).data('info');
    Helpers.confirmDialog(
      '¿Completar compra?',
      `Al completar la compra <strong>${Helpers.escapeHtml(info)}</strong> se actualizará el stock y se crearán lotes para las plantas con los datos de categoría y ubicación configurados en cada item. ¿Desea continuar?`,
      () => {
        Ajax.post(`${baseUrl}?action=completar_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              comprasTable.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          }).catch((err) => Helpers.toast('error', err));
      },
      'Sí, completar'
    );
  });

  // ============================================================
  //  Cancelar
  // ============================================================

  $(document).on('click', '.btn-cancelar', function () {
    const id = $(this).data('id');
    const info = $(this).data('info');
    Helpers.confirmDialog(
      '¿Cancelar compra?',
      `¿Deseas cancelar la compra <strong>${Helpers.escapeHtml(info)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=cancelar_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              comprasTable.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          }).catch((err) => Helpers.toast('error', err));
      },
      'Sí, cancelar'
    );
  });

  // ============================================================
  //  Delete
  // ============================================================

  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const info = $(this).data('info');
    Helpers.confirmDialog(
      '¿Eliminar compra?',
      `¿Deseas eliminar la compra <strong>${Helpers.escapeHtml(info)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              comprasTable.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          }).catch((err) => Helpers.toast('error', err));
      },
      'Sí, eliminar'
    );
  });

  // ============================================================
  //  Modal cleanup
  // ============================================================

  $('#compraModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  // ============================================================
  //  Init
  // ============================================================

  setupRealTimeValidation($('#compraForm'), compraRules);
  initDataTable();
});
