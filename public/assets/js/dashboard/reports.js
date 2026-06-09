import * as Helpers from '../utils/helpers.js';

const baseUrl = `${window.BASE_URL || '/'}reports`;

let reportsTable = null;
let reportChart = null;
let currentModule = '';
let currentChartData = null;
let currentXhr = null;
let currentFilterModule = '';
let suppressChartChange = false;

const formatValue = (val) => {
  if (val === null || val === undefined) return '-';
  return val;
};

const currencyFields = ['total', 'subtotal', 'iva', 'costo_unitario_actual', 'costo_unitario', 'monto_total', 'precio_unitario', 'costo_mano_obra', 'costo_total_insumo', 'precio_final_sugerido', 'monto_total', 'saldo_pendiente', 'monto_', 'compra_total', 'total_pagado'];

function isCurrencyField(key) {
  return currencyFields.some((f) => key === f || key.startsWith(f) || key.endsWith(f));
}

function formatCurrency(val) {
  const num = parseFloat(val);
  if (isNaN(num)) return formatValue(val);
  return `Bs. ${num.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatDate(val) {
  if (!val) return '-';
  const parts = val.split(' ')[0].split('-');
  if (parts.length === 3) {
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
  }
  try {
    const d = new Date(val);
    return d.toLocaleDateString('es-VE', {
      day: '2-digit', month: '2-digit', year: 'numeric',
    });
  } catch { return val; }
}

function getRenderer(key) {
  if (isCurrencyField(key)) return (val) => formatCurrency(val);
  if (/^fecha_|fecha/.test(key) || key === 'fecha_venta' || key === 'fecha_siembra' || key === 'fecha_asignacion' || key === 'fecha_cumplimiento' || key === 'fecha_compra' || key === 'fecha_adquisicion' || key === 'fecha_ultimo_mantenimiento' || key === 'fecha_recoleccion' || key === 'fecha_calculo' || key === 'fecha_vencimiento') {
    return (val) => formatDate(val);
  }
  if (key === 'stock_actual' || key === 'cantidad_actual' || key === 'stock_lotes') {
    return (val) => {
      const num = parseFloat(val);
      if (isNaN(num)) return formatValue(val);
      const cls = num <= 0 ? 'bg-danger' : num < 10 ? 'bg-warning text-dark' : 'bg-success';
      return `<span class="badge ${cls}">${num}</span>`;
    };
  }
  return (val) => formatValue(val);
}

function buildColumns(columns, keys) {
  if (!columns) return [];
  return columns.map((label, idx) => {
    const key = keys && keys[idx] ? keys[idx] : `col_${idx}`;
    return {
      title: label,
      data: key,
      render: getRenderer(key),
      defaultContent: '',
    };
  });
}

function ensureTable() {
  if ($('#reportsTable').length === 0) {
    $('#tableContainer').html('<table id="reportsTable" class="table table-striped table-hover w-100"><thead><tr></tr></thead><tbody></tbody></table>');
  }
}

function destroyTable() {
  if ($.fn.DataTable && $.fn.DataTable.isDataTable('#reportsTable')) {
    try { $('#reportsTable').DataTable().destroy(true); } catch (e) {}
  }
  ensureTable();
  reportsTable = null;
}

function destroyChart() {
  if (reportChart) {
    try { reportChart.destroy(); } catch (e) {}
    reportChart = null;
  }
  currentChartData = null;
}

function abortRequest() {
  if (currentXhr && currentXhr.readyState !== 4) {
    try { currentXhr.abort(); } catch (e) {}
  }
}

function showLoading() {
  $('#reportsTable').addClass('d-none');
  $('#chartContainer').addClass('d-none');
  $('#chartTypeSelector').addClass('d-none');
  $('#reportHeader').addClass('d-none');
  $('#reportPlaceholder').removeClass('d-none');
  $('#reportPlaceholder').html(
    '<div class="text-center py-5"><i class="fas fa-spinner fa-spin mb-3 d-block" style="font-size:2rem;color:var(--color-secondary);"></i><p style="color:var(--text-secondary);">Cargando reporte...</p></div>'
  );
}

function buildChartConfig(chartData, chartType) {
  const type = chartType || chartData.type || 'bar';
  const colors = [
    '#2ecc71', '#3498db', '#e74c3c', '#f39c12', '#9b59b6',
    '#1abc9c', '#e67e22', '#2980b9', '#c0392b', '#16a085',
    '#8e44ad', '#d35400', '#27ae60', '#f1c40f', '#2c3e50',
  ];

  if (type === 'pie' || type === 'doughnut' || type === 'polarArea') {
    return {
      type: type,
      data: {
        labels: chartData.labels,
        datasets: [{
          data: chartData.values,
          backgroundColor: colors.slice(0, chartData.labels.length),
          borderColor: '#fff',
          borderWidth: type === 'polarArea' ? 1 : 2,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { position: 'bottom', labels: { font: { size: 11 } } },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
              },
            },
          },
        },
      },
    };
  }

  if (type === 'line') {
    return {
      type: 'line',
      data: {
        labels: chartData.labels,
        datasets: [{
          label: chartData.label || 'Valor',
          data: chartData.values,
          borderColor: '#3498db',
          backgroundColor: 'rgba(52, 152, 219, 0.1)',
          fill: true,
          tension: 0.3,
          pointBackgroundColor: '#2980b9',
          pointRadius: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => ` ${chartData.label || ''}: ${Number(ctx.parsed.y).toLocaleString('es-VE')}`,
            },
          },
        },
        scales: {
          x: { ticks: { font: { size: 10 }, maxRotation: 45 } },
          y: { beginAtZero: true, ticks: { font: { size: 10 } } },
        },
      },
    };
  }

  return {
    type: 'bar',
    data: {
      labels: chartData.labels,
      datasets: [{
        label: chartData.label || 'Total',
        data: chartData.values,
        backgroundColor: colors.slice(0, chartData.labels.length),
        borderRadius: 4,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => ` ${chartData.label || ''}: ${Number(ctx.parsed.y).toLocaleString('es-VE')}`,
          },
        },
      },
      scales: {
        x: { ticks: { font: { size: 10 }, maxRotation: 45 } },
        y: { beginAtZero: true, ticks: { font: { size: 10 } } },
      },
    },
  };
}

function renderChart(chartData, chartType) {
  if (!chartData || !chartData.labels || chartData.labels.length === 0) {
    $('#chartContainer').addClass('d-none');
    $('#chartTypeSelector').addClass('d-none');
    return;
  }

  const ctx = document.getElementById('reportChart').getContext('2d');
  const config = buildChartConfig(chartData, chartType);

  destroyChart();
  reportChart = new Chart(ctx, config);
  currentChartData = chartData;
  $('#chartTitle').text(chartData.label || 'Gráfico');
  $('#chartTypeSelector').removeClass('d-none');
  suppressChartChange = true;
  $('#chartTypeSelector').val(chartType || chartData.type || 'bar');
  suppressChartChange = false;
  $('#chartContainer').removeClass('d-none');
}

let loadReportId = 0;

function loadReport(module, filters) {
  abortRequest();
  const reportId = ++loadReportId;

  if (!module) {
    destroyTable();
    destroyChart();
    $('#reportsTable').addClass('d-none');
    $('#chartContainer').addClass('d-none');
    $('#chartTypeSelector').addClass('d-none');
    $('#reportHeader').addClass('d-none');
    $('#reportPlaceholder').removeClass('d-none');
    $('#reportPlaceholder').html(`
      <i class="fas fa-chart-bar mb-3 d-block" style="font-size:3rem;color:var(--text-muted);opacity:0.4;"></i>
      <h5 style="color:var(--text-secondary);">Selecciona un módulo de reporte</h5>
      <p style="color:var(--text-muted);font-size:0.9rem;">Los datos se cargarán automáticamente al seleccionar un módulo.</p>
    `);
    return;
  }

  const sameModule = currentModule === module && reportsTable !== null;
  currentModule = module;

  $('#tableContainer').removeClass('col-lg-12').addClass('col-lg-8');
  if (!sameModule) {
    destroyTable();
    destroyChart();
  }

  showLoading();

  const params = new URLSearchParams();
  params.set('module', module);
  Object.entries(filters).forEach(([k, v]) => {
    if (v !== '' && v !== null && v !== undefined) {
      params.set(k, v);
    }
  });

  currentXhr = $.ajax({
    url: `${baseUrl}?action=get_report_data&${params.toString()}`,
    method: 'GET',
    dataType: 'json',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    success: function (res) {
      if (reportId !== loadReportId) return;

      try {
        $('#reportPlaceholder').addClass('d-none');
        $('#reportHeader').removeClass('d-none');
        $('#reportTitle').text(`Reporte - ${$('#reportModule option:selected').text()}`);

        if (sameModule && reportsTable) {
          $('#reportsTable').removeClass('d-none');
          void $('#reportsTable')[0].offsetHeight;
          reportsTable.clear();
          reportsTable.rows.add(res.rows || []);
          reportsTable.draw();
        } else {
          const columns = buildColumns(res.columns, res.keys);
          $('#reportsTable').removeClass('d-none');
          void $('#reportsTable')[0].offsetHeight;
          reportsTable = $('#reportsTable').DataTable({
            data: res.rows || [],
            columns: columns,
            pageLength: 25,
            scrollX: true,
            responsive: false,
            autoWidth: false,
            order: [],
            language: {
              url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
            },
            dom: '<"d-flex justify-content-between align-items-center mb-2"lf>tip',
          });
        }

        if (res.chart && res.chart.labels && res.chart.labels.length > 0) {
          const chartType = $('#chartTypeSelector').val() || 'bar';
          renderChart(res.chart, chartType);
        } else {
          destroyChart();
          $('#chartContainer').addClass('d-none');
          $('#chartTypeSelector').addClass('d-none');
          $('#tableContainer').removeClass('col-lg-8').addClass('col-lg-12');
        }
      } catch (e) {
        console.error('Error rendering report:', e);
        $('#reportPlaceholder').removeClass('d-none').html(`
          <i class="fas fa-exclamation-circle mb-3 d-block" style="font-size:3rem;color:var(--color-error);opacity:0.6;"></i>
          <h5 style="color:var(--text-secondary);">Error al mostrar</h5>
          <p style="color:var(--text-muted);font-size:0.9rem;">${Helpers.escapeHtml(e.message || 'Error desconocido')}</p>
        `);
      }
    },
    error: function (xhr, status) {
      if (status === 'abort' || reportId !== loadReportId) return;
      let msg = 'Error al cargar el reporte';
      try {
        const r = JSON.parse(xhr.responseText);
        msg = r.message || msg;
      } catch {}
      Helpers.toast('error', msg);
      $('#reportPlaceholder').removeClass('d-none').html(`
        <i class="fas fa-exclamation-circle mb-3 d-block" style="font-size:3rem;color:var(--color-error);opacity:0.6;"></i>
        <h5 style="color:var(--text-secondary);">Error al cargar</h5>
        <p style="color:var(--text-muted);font-size:0.9rem;">${Helpers.escapeHtml(msg)}</p>
      `);
    },
  });
}

function collectFilters() {
  const filters = {};
  $('#filtersContainer .report-filter').each(function () {
    const $el = $(this);
    const field = $el.data('field');
    const type = $el.data('type');
    if (!field) return;
    if (type === 'date-range') {
      const desde = $(`#filter_${field}_desde`).val();
      const hasta = $(`#filter_${field}_hasta`).val();
      if (desde) filters[`${field}_desde`] = desde;
      if (hasta) filters[`${field}_hasta`] = hasta;
    } else {
      const val = $el.val();
      if (val !== '' && val !== null && val !== undefined) {
        filters[field] = val;
      }
    }
  });
  return filters;
}

function loadModules() {
  $.ajax({
    url: `${baseUrl}?action=get_modules`,
    method: 'GET',
    dataType: 'json',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    success: function (res) {
      if (!res.success || !res.modules) return;
      const $sel = $('#reportModule');
      $sel.empty().append('<option value="">-- Seleccionar Módulo --</option>');
      res.modules.forEach((mod) => {
        $sel.append(`<option value="${mod.id}">${mod.nombre}</option>`);
      });
    },
    error: function () {
      Helpers.toast('error', 'Error al cargar módulos');
    },
  });
}

function loadFilters(module) {
  if (!module) {
    $('#filtersContainer').html('<div class="text-muted small py-2">Selecciona un módulo para ver los filtros disponibles.</div>');
    return;
  }

  currentFilterModule = module;

  $.ajax({
    url: `${baseUrl}?action=get_filters&module=${encodeURIComponent(module)}`,
    method: 'GET',
    dataType: 'json',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    success: function (res) {
      if (currentFilterModule !== module) return;
      if (!res.success || !res.filters) return;
      renderFilters(res.filters);
      if (currentFilterModule !== module) return;
      loadReport(module, {});
    },
    error: function () {
      Helpers.toast('error', 'Error al cargar filtros');
    },
  });
}

function renderFilters(filters) {
  const $container = $('#filtersContainer');
  $container.empty();

  if (!filters || filters.length === 0) {
    $container.html('<div class="text-muted small py-2">No hay filtros disponibles para este módulo.</div>');
    return;
  }

  const $row = $('<div class="row g-2"></div>');

  filters.forEach((f) => {
    const $col = $('<div class="col-auto"></div>');
    let html = '';

    if (f.type === 'select') {
      html = `<label class="form-label small mb-1 fw-medium">${f.label}</label>`;
      html += `<select class="form-select form-select-sm report-filter" data-field="${f.field}" data-type="select" style="min-width:140px;">`;
      (f.options || []).forEach((opt) => {
        html += `<option value="${opt.value}">${opt.label}</option>`;
      });
      html += '</select>';
    } else if (f.type === 'date-range') {
      html = `<label class="form-label small mb-1 fw-medium">${f.label}</label>`;
      html += `<div class="d-flex gap-1 align-items-center">`;
      html += `<input type="date" class="form-control form-control-sm report-filter" id="filter_${f.field}_desde" data-field="${f.field}" data-type="date-range" placeholder="Desde" style="width:140px;">`;
      html += `<span class="text-muted small">a</span>`;
      html += `<input type="date" class="form-control form-control-sm report-filter" id="filter_${f.field}_hasta" data-field="${f.field}" data-type="date-range" placeholder="Hasta" style="width:140px;">`;
      html += '</div>';
    } else if (f.type === 'number') {
      html = `<label class="form-label small mb-1 fw-medium">${f.label}</label>`;
      html += `<input type="number" class="form-control form-control-sm report-filter" data-field="${f.field}" data-type="number" style="width:120px;" placeholder="${f.label}">`;
    } else {
      html = `<label class="form-label small mb-1 fw-medium">${f.label}</label>`;
      html += `<input type="text" class="form-control form-control-sm report-filter" data-field="${f.field}" data-type="text" style="width:160px;" placeholder="${f.label}">`;
    }

    $col.html(html);
    $row.append($col);
  });

  const $btnCol = $('<div class="col-auto d-flex align-items-end pb-1 gap-1"></div>');
  $btnCol.append(
    '<button id="btnApplyFilters" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filtrar</button>' +
    '<button id="btnClearFilters" class="btn btn-outline-secondary btn-sm ms-1"><i class="fas fa-eraser"></i> Limpiar</button>'
  );
  $row.append($btnCol);
  $container.append($row);
}

function exportCsv() {
  if (!reportsTable) {
    Helpers.toast('warning', 'No hay datos para exportar');
    return;
  }
  const data = reportsTable.data().toArray();
  if (data.length === 0) {
    Helpers.toast('warning', 'No hay datos para exportar');
    return;
  }
  const headers = reportsTable.columns().header().to$().map((i, el) => $(el).text().trim()).get();
  const csvRows = [headers.join(',')];
  data.forEach((row) => {
    const values = headers.map((_, idx) => {
      const val = reportsTable.cell(row, idx).data();
      const str = String(val ?? '').replace(/"/g, '""');
      return `"${str}"`;
    });
    csvRows.push(values.join(','));
  });
  const csv = csvRows.join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  const moduleLabel = $('#reportModule option:selected').text().trim() || 'reporte';
  link.download = `${moduleLabel.replace(/\s+/g, '_')}.csv`;
  link.click();
  URL.revokeObjectURL(link.href);
  Helpers.toast('success', 'Exportado correctamente');
}

function exportPdf() {
  const module = $('#reportModule').val();
  if (!module) {
    Helpers.toast('warning', 'Selecciona un módulo primero');
    return;
  }

  const filters = collectFilters();
  const params = new URLSearchParams();
  params.set('module', module);
  params.set('action', 'generate_pdf');
  Object.entries(filters).forEach(([k, v]) => {
    if (v !== '' && v !== null) params.set(k, v);
  });

  window.location.href = `${baseUrl}?${params.toString()}`;
}

function clearFilters() {
  $('#filtersContainer .report-filter').each(function () {
    const $el = $(this);
    const type = $el.data('type');
    if (type === 'date-range') {
      $(`#filter_${$el.data('field')}_desde`).val('');
      $(`#filter_${$el.data('field')}_hasta`).val('');
    } else if ($el.is('select')) {
      $el.val('');
    } else {
      $el.val('');
    }
  });
  const module = $('#reportModule').val();
  if (module) loadReport(module, {});
}

function changeChartType() {
  const chartType = $('#chartTypeSelector').val();
  if (currentChartData) {
    renderChart(currentChartData, chartType);
  }
}

$(document).ready(function () {
  $('#reportsTable').addClass('d-none');
  $('#chartContainer').addClass('d-none');
  $('#chartTypeSelector').addClass('d-none');
  loadModules();

  $('#reportModule').on('change', function () {
    const module = $(this).val();
    if (!module) {
      destroyTable();
      destroyChart();
      abortRequest();
      $('#chartTypeSelector').addClass('d-none');
      $('#filtersContainer').html('<div class="text-muted small py-2">Selecciona un módulo para ver los filtros disponibles.</div>');
      $('#reportsTable').addClass('d-none');
      $('#chartContainer').addClass('d-none');
      $('#reportHeader').addClass('d-none');
      $('#reportPlaceholder').removeClass('d-none');
      $('#reportPlaceholder').html(`
        <i class="fas fa-chart-bar mb-3 d-block" style="font-size:3rem;color:var(--text-muted);opacity:0.4;"></i>
        <h5 style="color:var(--text-secondary);">Selecciona un módulo de reporte</h5>
        <p style="color:var(--text-muted);font-size:0.9rem;">Los datos se cargarán automáticamente al seleccionar un módulo.</p>
      `);
      return;
    }
    loadFilters(module);
  });

  $(document).on('click', '#btnApplyFilters', function () {
    const module = $('#reportModule').val();
    if (!module) return;
    const filters = collectFilters();
    loadReport(module, filters);
  });

  $(document).on('click', '#btnClearFilters', function () {
    clearFilters();
  });

  $(document).on('change', '#chartTypeSelector', function () {
    if (suppressChartChange) return;
    changeChartType();
  });

  $('#btnRefresh').on('click', function () {
    const module = $('#reportModule').val();
    if (!module) return;
    const filters = collectFilters();
    loadReport(module, filters);
  });

  $('#btnCsv').on('click', exportCsv);
  $('#btnPdf').on('click', exportPdf);
});
