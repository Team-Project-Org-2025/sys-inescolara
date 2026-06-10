import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}inventario`;
  let consolidatedTable = null;
  let movementsTable = null;
  let adjustmentsTable = null;

  const adjustmentRules = {
    id_insumo: 'select',
    id_trabajador: 'select',
    tipo_ajuste: 'select',
    cantidad: 'cantidad',
    fecha_ajuste: 'fechaFuturaCheck'
  };

  const initConsolidatedTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('consolidatedTable', 5, 6);
    }
    consolidatedTable = $('#consolidatedTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_consolidated`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'data',
      },
      columns: [
        {
          data: 'tipo',
          render: (data) => {
            const types = { Planta: 'planta', Insumo: 'insumo', Herramienta: 'herramienta' };
            const cls = types[data] || 'insumo';
            return `<span class="inv-type-badge inv-type-${cls}">${data}</span>`;
          },
        },
        { data: 'nombre' },
        {
          data: 'stock',
          render: (data, type, row) => {
            if (data === null || data === undefined) return '<span class="text-muted">—</span>';
            const num = Number(data);
            const thresholdLow = 10;
            const thresholdCritical = 3;
            let cls = 'normal';
            let label = 'Normal';
            if (num <= thresholdCritical) { cls = 'critical'; label = 'Crítico'; }
            else if (num <= thresholdLow) { cls = 'low'; label = 'Bajo'; }
            return `<span class="stock-badge stock-badge-${cls}"><span class="stock-dot stock-dot-${cls}"></span>${num.toLocaleString()} <span class="text-muted" style="font-weight:400;font-size:0.7rem;">(${label})</span></span>`;
          },
        },
        {
          data: 'unidad',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'ubicacion',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'precio',
          render: (data) => data !== null ? `Bs ${Number(data).toFixed(2)}` : '<span class="text-muted">—</span>',
        },
      ],
      pageLength: 15,
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
          action: () => {
            if (typeof SkeletonHelper !== 'undefined') {
              SkeletonHelper.showTableSkeleton('consolidatedTable', 5, 6);
            }
            consolidatedTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  const initMovementsTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('movementsTable', 5, 7);
    }
    movementsTable = $('#movementsTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_movements`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'data',
      },
      columns: [
        {
          data: 'tipo_movimiento',
          render: (data) => {
            const icons = { entrada: 'fa-arrow-down', salida: 'fa-arrow-up', venta: 'fa-shopping-cart', compra: 'fa-truck' };
            const icon = icons[data] || 'fa-circle';
            const cls = data === 'entrada' ? 'move-type-entrada' : 'move-type-salida';
            return `<span class="move-type ${cls}"><i class="fas ${icon}"></i>${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
          },
        },
        { data: 'tipo_item' },
        {
          data: 'cliente',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        { data: 'gestor' },
        {
          data: 'detalle',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        { data: 'fecha' },
        {
          data: 'observacion',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
      ],
      pageLength: 15,
      responsive: true,
      autoWidth: false,
      order: [[5, 'desc']],
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
              SkeletonHelper.showTableSkeleton('movementsTable', 5, 7);
            }
            movementsTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  const initAdjustmentsTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('adjustmentsTable', 5, 6);
    }
    adjustmentsTable = $('#adjustmentsTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_adjustments`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'data',
      },
      columns: [
        { data: 'nombre_insumo' },
        { data: 'trabajador' },
        {
          data: 'tipo_ajuste',
          render: (data) => {
            const cls = data === 'entrada' ? 'move-type-entrada' : 'move-type-salida';
            const icon = data === 'entrada' ? 'fa-arrow-down' : 'fa-arrow-up';
            return `<span class="move-type ${cls}"><i class="fas ${icon}"></i>${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
          },
        },
        { data: 'cantidad', render: (data) => Number(data).toLocaleString() },
        { data: 'motivo' },
        { data: 'fecha_ajuste' },
      ],
      pageLength: 10,
      responsive: true,
      autoWidth: false,
      order: [[5, 'desc']],
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
              SkeletonHelper.showTableSkeleton('adjustmentsTable', 5, 6);
            }
            adjustmentsTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  // Open adjustment modal
  $('#btnNewAdjustment').on('click', function () {
    $('#adjustmentModal').modal({ focus: false }).modal('show');
  });

  // Save adjustment
  $('#adjustmentForm').on('submit', function (e) {
    e.preventDefault();
    if (!validateForm($(this), adjustmentRules)) return;
    const formData = new FormData(this);

    $.ajax({
      url: `${baseUrl}?action=add_adjustment`,
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
          $('#adjustmentModal').modal('hide');
          if (adjustmentsTable) adjustmentsTable.ajax.reload(null, false);
          if (consolidatedTable) consolidatedTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al registrar ajuste');
      });
  });

  // Reset form on modal close
  $('#adjustmentModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  if ($('#adjustmentForm').length) {
    setupRealTimeValidation($('#adjustmentForm'), adjustmentRules);
  }

  // Init tables
  initConsolidatedTable();
  initMovementsTable();
  if ($('#adjustmentsTable').length) {
    initAdjustmentsTable();
  }
});
