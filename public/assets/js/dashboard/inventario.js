import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}inventario`;
  let consolidatedTable = null;
  let movementsTable = null;
  let adjustmentsTable = null;

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
            const badges = { Planta: 'success', Insumo: 'primary', Herramienta: 'secondary', Lote: 'info' };
            return `<span class="badge bg-${badges[data] || 'dark'}">${data}</span>`;
          },
        },
        { data: 'nombre' },
        {
          data: 'stock',
          render: (data) => data !== null && data !== undefined ? Number(data).toLocaleString() : '<span class="text-muted">—</span>',
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
            const badges = { entrada: 'success', salida: 'danger', venta: 'warning', compra: 'info' };
            return `<span class="badge bg-${badges[data] || 'dark'}">${data}</span>`;
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
            const badges = { entrada: 'success', salida: 'danger' };
            return `<span class="badge bg-${badges[data] || 'dark'}">${data}</span>`;
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

  // Init tables
  initConsolidatedTable();
  initMovementsTable();
  if ($('#adjustmentsTable').length) {
    initAdjustmentsTable();
  }
});
