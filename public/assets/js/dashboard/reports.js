import * as Helpers from '../utils/helpers.js';

const baseUrl = `${window.BASE_URL || '/'}reports`;

let reportsTable = null;

const formatValue = (val) => {
  if (val === null || val === undefined) return '-';
  return val;
};

const renderers = {
  stock_actual: (val) => {
    const num = parseFloat(val);
    if (isNaN(num)) return formatValue(val);
    const cls = num <= 0 ? 'bg-danger' : num < 10 ? 'bg-warning text-dark' : 'bg-success';
    return `<span class="badge ${cls}">${num}</span>`;
  },
  monto_total: (val) => {
    const num = parseFloat(val);
    if (isNaN(num)) return formatValue(val);
    return `Bs. ${num.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  },
  costo_unitario_actual: (val) => {
    const num = parseFloat(val);
    if (isNaN(num)) return formatValue(val);
    return `Bs. ${num.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  },
  fecha_venta: (val) => {
    if (!val) return '-';
    try {
      const d = new Date(val);
      return d.toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch { return val; }
  },
};

function buildColumns(labels, firstRow) {
  const keys = Object.keys(firstRow);
  return labels.map((label, idx) => {
    const key = keys[idx];
    const render = renderers[key];
    return {
      title: label,
      data: key,
      render: render ? (val) => render(val) : (val) => formatValue(val),
    };
  });
}

$(document).ready(function () {
  const $reportType = $('#reportType');
  const $table = $('#reportsTable');
  const $placeholder = $('#reportPlaceholder');

  $reportType.on('change', function () {
    const type = $(this).val();
    if (!type) {
      $table.hide();
      $placeholder.show();
      if (reportsTable) {
        reportsTable.destroy();
        reportsTable = null;
      }
      return;
    }
    loadReport(type);
  });

  function loadReport(type) {
    if (reportsTable) {
      reportsTable.destroy();
      reportsTable = null;
    }

    $table.hide();
    $placeholder.show();
    $placeholder.html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin mb-3 d-block" style="font-size:2rem;color:var(--color-secondary);"></i><p style="color:var(--text-secondary);">Cargando reporte...</p></div>');

    $.ajax({
      url: `${baseUrl}?action=get_report&type=${encodeURIComponent(type)}`,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      success: function (res) {
        if (!res.success || !res.data || res.data.length === 0) {
          $placeholder.show().html(`
            <i class="fas fa-inbox mb-3 d-block" style="font-size:3rem;color:var(--text-muted);opacity:0.4;"></i>
            <h5 style="color:var(--text-secondary);">Sin datos</h5>
            <p style="color:var(--text-muted);font-size:0.9rem;">No se encontraron registros para este reporte.</p>
          `);
          return;
        }

        const columns = buildColumns(res.labels, res.data[0]);

        $placeholder.hide();
        $table.show();

        reportsTable = $table.DataTable({
          data: res.data,
          columns: columns,
          pageLength: 25,
          responsive: true,
          autoWidth: false,
          order: [],
          language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
          },
          dom: '<"d-flex justify-content-between align-items-center mb-2"lfB>tip',
          buttons: [
            {
              text: '<i class="fas fa-file-excel"></i> Exportar Excel',
              className: 'btn btn-outline-success btn-sm',
              action: function () {
                exportTableToExcel(this);
              },
            },
            {
              text: '<i class="fas fa-sync-alt"></i> Actualizar',
              className: 'btn btn-outline-secondary btn-sm',
              action: function () {
                loadReport(type);
              },
            },
          ],
        });
      },
      error: function (xhr) {
        let msg = 'Error al cargar el reporte';
        try {
          const res = JSON.parse(xhr.responseText);
          msg = res.message || msg;
        } catch {}
        Helpers.toast('error', msg);
        $placeholder.show().html(`
          <i class="fas fa-exclamation-circle mb-3 d-block" style="font-size:3rem;color:var(--color-error);opacity:0.6;"></i>
          <h5 style="color:var(--text-secondary);">Error al cargar</h5>
          <p style="color:var(--text-muted);font-size:0.9rem;">${Helpers.escapeHtml(msg)}</p>
        `);
      },
    });
  }

  function exportTableToExcel(api) {
    const data = api.data().toArray();
    if (data.length === 0) {
      Helpers.toast('warning', 'No hay datos para exportar');
      return;
    }
    const headers = api.columns().header().to$().map((i, el) => $(el).text().trim()).get();
    const csvRows = [headers.join(',')];
    data.forEach((row) => {
      const values = headers.map((_, idx) => {
        const val = api.cell(row, idx).data();
        const str = String(val ?? '').replace(/"/g, '""');
        return `"${str}"`;
      });
      csvRows.push(values.join(','));
    });
    const csv = csvRows.join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    const reportLabel = $reportType.find('option:selected').text().trim() || 'reporte';
    link.download = `${reportLabel.replace(/\s+/g, '_')}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
    Helpers.toast('success', 'Exportado correctamente');
  }
});
