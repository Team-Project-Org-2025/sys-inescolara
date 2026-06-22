$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}inventario`;
  let consolidatedTable = null;

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

  // Init tables
  initConsolidatedTable();
});
