import * as Helpers from '../utils/helpers.js';

const baseUrl = `${window.BASE_URL || '/'}reports`;

const PALETTE = [
  '#2e7d32', '#e5a835', '#0ea5e9', '#dc2626', '#8b5cf6',
  '#06b6d4', '#f59e0b', '#ec4899', '#14b8a6', '#f97316',
  '#6366f1', '#22c55e', '#ef4444', '#3b82f6', '#a855f7',
];

let reportsTable = null;
let reportChart = null;
let currentModule = '';
let currentChartData = null;
let currentXhr = null;
let currentFilterModule = '';
let filterDefs = [];
let activeFields = [];
let filterDebounce = null;

const currencyFields = [
  'total', 'subtotal', 'iva', 'costo_unitario_actual', 'costo_unitario', 'costo_agua_lote',
  'precio_planta_base', 'monto_total', 'precio_unitario', 'costo_mano_obra', 'costo_total_insumo',
  'precio_final_sugerido', 'saldo_pendiente', 'compra_total', 'total_pagado', 'total_compras',
  'total_ventas', 'impacto_economico', 'monto', 'ganancia',
];

function pad(n) { return String(n).padStart(2, '0'); }

function today() { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; }

function daysAgo(n) {
  const d = new Date(); d.setDate(d.getDate() - n);
  return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
}

function yearStart() { const y = new Date().getFullYear(); return `${y}-01-01`; }

function getTimeRange(period) {
  if (period === 'today') return { d: today(), h: today() };
  if (period === '7d') return { d: daysAgo(7), h: today() };
  if (period === '30d') return { d: daysAgo(30), h: today() };
  if (period === 'year') return { d: yearStart(), h: today() };
  return {};
}

function isCurrency(key) {
  return currencyFields.includes(key);
}

function isCount(key) {
  return /^(total_plantas|total_lotes|total_inicial|total_detalles|total_insumos|numero_|items|stock_|cantidad|tareas_|ganancia_)/.test(key);
}

function fmtCurrency(val) {
  const n = parseFloat(val);
  if (isNaN(n)) return val ?? '-';
  return `Bs. ${n.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function fmtDate(val) {
  if (!val) return '-';
  const m = val.split(' ')[0].split('-');
  if (m.length === 3) return `${m[2]}/${m[1]}/${m[0]}`;
  try { return new Date(val).toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' }); }
  catch { return val; }
}

function getRenderer(key) {
  if (isCurrency(key)) return v => fmtCurrency(v);
  if (/^fecha_|fecha/.test(key)) return v => fmtDate(v);
  if (isCount(key)) return v => {
    const n = parseFloat(v);
    if (isNaN(n)) return v ?? '-';
    return `<span class="badge ${n <= 0 ? 'bg-danger' : n < 10 ? 'bg-warning text-dark' : 'bg-success'}">${n}</span>`;
  };
  if (/^(es_|tiene_|is_|activo|status|estado)/.test(key)) return v =>
    (!v || v === '0' || v === 'inactivo') ? `<span class="badge bg-light text-muted">${v || 'Inactivo'}</span>` : `<span class="badge bg-success">${v}</span>`;
  return v => v ?? '-';
}

function buildColumns(columns, keys) {
  if (!columns) return [];
  return columns.map((label, idx) => ({
    title: label,
    data: keys?.[idx] ?? `col_${idx}`,
    render: getRenderer(keys?.[idx] ?? `col_${idx}`),
    defaultContent: '',
  }));
}

function destroyTable() {
  if ($.fn.DataTable?.isDataTable('#reportsTable')) {
    try { $('#reportsTable').DataTable().destroy(true); } catch {}
  }
  if ($('#reportsTable').length === 0) $('#tableContainer').html('<table id="reportsTable" class="table w-100"><thead><tr></tr></thead><tbody></tbody></table>');
  reportsTable = null;
}

function destroyChart() {
  if (reportChart) { try { reportChart.destroy(); } catch {} reportChart = null; currentChartData = null; }
}

function abortRequest() {
  if (currentXhr?.readyState !== 4) try { currentXhr.abort(); } catch {}
}

function showLoading() {
  $('#reportsTable, #chartCard, #reportActions, #summaryBar').addClass('d-none');
  $('#reportPlaceholder').removeClass('d-none').html(
    '<div class="text-center py-4"><i class="fas fa-spinner fa-spin mb-2 d-block" style="font-size:1.5rem;color:var(--color-secondary);"></i><p style="color:var(--text-secondary);font-size:0.85rem;margin:0;">Cargando...</p></div>'
  );
}

function buildChartConfig(data, type) {
  const labels = data.labels || [];
  const colors = PALETTE.slice(0, labels.length);
  const fmt = ctx => ` ${ctx.label}: ${Number(ctx.parsed.y ?? ctx.parsed).toLocaleString('es-VE')}`;
  const baseScales = { x: { grid: { display: false }, ticks: { font: { size: 9 } } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } } };
  if (type === 'pie' || type === 'doughnut') {
    return {
      type, data: { labels, datasets: [{ data: data.values, backgroundColor: colors, borderColor: '#fff', borderWidth: 2 }] },
      options: { responsive: true, maintainAspectRatio: true, animation: { animateRotate: true, duration: 400 }, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, usePointStyle: true } }, tooltip: { callbacks: { label: fmt } } } },
    };
  }
  if (type === 'line') {
    return {
      type: 'line',
      data: { labels, datasets: [{ label: data.label || '', data: data.values, borderColor: '#2e7d32', backgroundColor: 'rgba(46,125,50,0.06)', fill: true, tension: 0.3, pointRadius: 2 }] },
      options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false }, tooltip: { callbacks: { label: fmt } } }, scales: baseScales },
    };
  }
  return {
    type: 'bar',
    data: { labels, datasets: [{ label: data.label || '', data: data.values, backgroundColor: colors, borderRadius: 3, barPercentage: 0.6 }] },
    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false }, tooltip: { callbacks: { label: fmt } } }, scales: baseScales },
  };
}

function renderChart(chartData, chartType) {
  if (!chartData?.labels?.length) { $('#chartCard').addClass('d-none'); return; }
  destroyChart();
  reportChart = new Chart(document.getElementById('reportChart').getContext('2d'), buildChartConfig(chartData, chartType || 'bar'));
  currentChartData = chartData;
  $('#chartTitle').html(`<i class="fas fa-chart-pie" style="color:var(--color-primary);"></i> ${chartData.label || 'Gráfico'}`);
  $('#chartTypeSelector').val(chartType || chartData.type || 'bar');
  $('#chartCard').removeClass('d-none');
}

let loadId = 0;

function loadReport(module, filters, timeRange) {
  abortRequest();
  const rid = ++loadId;
  if (!module) { resetUI(); return; }
  const same = currentModule === module && reportsTable !== null;
  currentModule = module;
  if (!same) { destroyTable(); destroyChart(); }
  showLoading();

  const params = new URLSearchParams({ module });
  Object.entries(filters).forEach(([k, v]) => { if (v !== '' && v != null) params.set(k, v); });
  if (timeRange?.d) params.set('fecha_desde', timeRange.d);
  if (timeRange?.h) params.set('fecha_hasta', timeRange.h);

  currentXhr = $.ajax({
    url: `${baseUrl}?action=get_report_data&${params}`,
    method: 'GET', dataType: 'json',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    success(res) {
      if (rid !== loadId) return;
      try {
        $('#reportPlaceholder').addClass('d-none');
        $('#reportActions').removeClass('d-none');
        $('#reportTitle').html(`<i class="fas fa-table" style="color:var(--color-secondary);"></i> ${$('#reportModuleSelect option:selected').text()}`);

        if (same && reportsTable) {
          $('#reportsTable').removeClass('d-none');
          reportsTable.clear().rows.add(res.rows || []).draw();
        } else {
          $('#reportsTable').removeClass('d-none');
          reportsTable = $('#reportsTable').DataTable({
            data: res.rows || [], columns: buildColumns(res.columns, res.keys),
            pageLength: 25, scrollX: true, responsive: false, autoWidth: false, order: [],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            dom: '<"d-flex justify-content-between align-items-center mb-1"lf>tip',
            drawCallback() { updateSummary(this.api().page.info().recordsDisplay); },
          });
        }
        if (res.chart?.labels?.length) {
          renderChart(res.chart, $('#chartTypeSelector').val() || 'bar');
        } else { destroyChart(); $('#chartCard').addClass('d-none'); }
      } catch (e) { showError(Helpers.escapeHtml(e.message || 'Error al mostrar')); }
    },
    error(xhr, status) {
      if (status === 'abort' || rid !== loadId) return;
      let msg = 'Error al cargar el reporte';
      try { msg = JSON.parse(xhr.responseText).message || msg; } catch {}
      Helpers.toast('error', msg);
      showError(msg);
    },
  });
}

function showError(msg) {
  $('#reportPlaceholder').removeClass('d-none').html(`
    <i class="fas fa-exclamation-circle mb-2 d-block" style="font-size:2.5rem;color:var(--color-error);opacity:0.6;"></i>
    <h6 style="color:var(--text-secondary);">Error</h6>
    <p style="color:var(--text-muted);font-size:0.85rem;margin:0;">${msg}</p>
  `);
}

function resetUI() {
  destroyTable(); destroyChart(); abortRequest();
  clearTimeout(filterDebounce);
  filterDefs = []; activeFields = [];
  $('#chartCard, #reportActions, #summaryBar').addClass('d-none');
  $('#filtersWrap').addClass('d-none');
  $('#filtersBar').empty();
  $('#reportsTable').addClass('d-none');
  $('#reportPlaceholder').removeClass('d-none').html(`
    <i class="fas fa-chart-bar mb-2 d-block" style="font-size:2.5rem;color:var(--text-muted);opacity:0.3;"></i>
    <h6 style="color:var(--text-secondary);font-weight:500;">Selecciona un módulo</h6>
    <p style="color:var(--text-muted);font-size:0.85rem;margin:0;">Los datos se cargarán automáticamente.</p>
  `);
}

function updateSummary(count) {
  if (count != null) {
    $('#summaryCount').text(count === 1 ? '1 registro' : `${Number(count).toLocaleString('es-VE')} registros`);
    $('#summaryTimestamp').text(new Date().toLocaleDateString('es-VE', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }));
    $('#summaryBar').removeClass('d-none');
  } else { $('#summaryBar').addClass('d-none'); }
}

function collectFilters() {
  const f = {};
  $('#filtersBar .report-filter').each(function () {
    const $el = $(this), field = $el.data('field'), type = $el.data('type');
    if (!field) return;
    if (type === 'date-range') {
      const d = $(`#filter_${field}_desde`).val(), h = $(`#filter_${field}_hasta`).val();
      if (d) f[`${field}_desde`] = d;
      if (h) f[`${field}_hasta`] = h;
    } else if (type === 'number') {
      const v = $el.val();
      if (v !== '' && v != null) {
        f[field] = v;
        const op = $el.closest('.filter-chip').find('.report-filter-op').val();
        if (op && op !== '>=') f[`${field}_op`] = op;
      }
    } else {
      const v = $el.val();
      if (v !== '' && v != null) f[field] = v;
    }
  });
  return f;
}

function getCurrentTimeRange() {
  const active = $('#timeFilterBar .time-preset.active');
  if (!active.length) return {};
  const period = active.data('period');
  if (period === 'custom') {
    const d = $('#timeDesde').val(), h = $('#timeHasta').val();
    if (!d || !h) return {};
    return { d, h };
  }
  return getTimeRange(period);
}

function reloadWithTime() {
  const m = $('#reportModuleSelect').val();
  if (!m) return;
  loadReport(m, collectFilters(), getCurrentTimeRange());
}

function loadModules() {
  $.ajax({
    url: `${baseUrl}?action=get_modules`,
    method: 'GET', dataType: 'json',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    success(res) {
      if (!res?.success || !res.modules) return;
      const $sel = $('#reportModuleSelect').empty().append('<option value="">— Seleccionar módulo —</option>');
      res.modules.forEach(mod => $sel.append(`<option value="${mod.id}">${mod.nombre}</option>`));
    },
    error() { Helpers.toast('error', 'Error al cargar módulos'); },
  });
}

const OP_LABELS = { '>=': '≥', '<=': '≤', '>': '>', '<': '<', '=': '=', '!=': '≠' };

function escapeAttr(v) {
  return Helpers.escapeHtml(String(v ?? ''));
}

function chipHtml(f) {
  let control = '';
  if (f.type === 'select') {
    control = `<select class="report-filter" data-field="${escapeAttr(f.field)}" data-type="select">`;
    control += '<option value="">Todos</option>';
    (f.options || []).forEach(o => { control += `<option value="${escapeAttr(o.value)}">${escapeAttr(o.label)}</option>`; });
    control += '</select>';
  } else if (f.type === 'date-range') {
    control = `<input type="date" class="report-filter" id="filter_${escapeAttr(f.field)}_desde" data-field="${escapeAttr(f.field)}" data-type="date-range" title="Desde">
      <span class="text-muted" style="font-size:0.7rem;">→</span>
      <input type="date" class="report-filter" id="filter_${escapeAttr(f.field)}_hasta" data-field="${escapeAttr(f.field)}" data-type="date-range" title="Hasta">`;
  } else if (f.type === 'number') {
    control = '<select class="report-filter-op">';
    Object.entries(OP_LABELS).forEach(([op, l]) => { control += `<option value="${op}" ${op === '>=' ? 'selected' : ''}>${l}</option>`; });
    control += '</select>';
    control += `<input type="number" class="report-filter" data-field="${escapeAttr(f.field)}" data-type="number" step="any" placeholder="—">`;
  } else {
    control = `<input type="text" class="report-filter" data-field="${escapeAttr(f.field)}" data-type="text" placeholder="Buscar...">`;
  }
  return `<div class="filter-chip" data-field="${escapeAttr(f.field)}">
    <span class="filter-chip-label">${escapeAttr(f.label)}</span>
    <div class="filter-chip-control">${control}</div>
    <button type="button" class="filter-chip-remove" title="Quitar filtro">&times;</button>
  </div>`;
}

function updateAddFilterOptions() {
  const $sel = $('#addFilterSelect');
  const current = $sel.val();
  let html = '<option value="">+ Agregar filtro</option>';
  filterDefs.forEach(f => {
    if (!activeFields.includes(f.field)) html += `<option value="${escapeAttr(f.field)}">${escapeAttr(f.label)}</option>`;
  });
  $sel.html(html).val(current && !html.includes(`value="${escapeAttr(current)}"`) ? '' : current);
}

function toggleChipValue(el) {
  const chip = $(el).closest('.filter-chip');
  if (!chip.length) return;
  const has = chip.find('.report-filter').toArray().some(input => {
    const v = $(input).val();
    return v !== '' && v != null;
  });
  chip.toggleClass('has-value', has);
}

function renderFilters() {
  const $bar = $('#filtersBar').empty();
  activeFields.forEach(field => {
    const def = filterDefs.find(d => d.field === field);
    if (def) $bar.append(chipHtml(def));
  });
  updateAddFilterOptions();
}

function loadFilters(module) {
  if (!module) return;
  currentFilterModule = module;
  $.ajax({
    url: `${baseUrl}?action=get_filters&module=${encodeURIComponent(module)}`,
    method: 'GET', dataType: 'json',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    success(res) {
      if (currentFilterModule !== module || !res?.success) return;
      filterDefs = res.filters || [];
      activeFields = filterDefs.map(d => d.field);
      const hasFilters = filterDefs.length > 0;
      $('#filtersWrap').toggleClass('d-none', !hasFilters);
      if (hasFilters) renderFilters();
      loadReport(module, {}, getCurrentTimeRange());
    },
    error() { Helpers.toast('error', 'Error al cargar filtros'); },
  });
}

function addFilter(field) {
  const def = filterDefs.find(d => d.field === field);
  if (!def || activeFields.includes(field)) return;
  activeFields.push(field);
  $('#filtersBar').append(chipHtml(def));
  updateAddFilterOptions();
  scheduleReload();
}

function removeFilter(field) {
  activeFields = activeFields.filter(f => f !== field);
  $(`#filtersBar .filter-chip[data-field="${CSS.escape(field)}"]`).remove();
  updateAddFilterOptions();
  scheduleReload();
}

function clearFilters() {
  $('#filtersBar .report-filter').each(function () {
    const $el = $(this), type = $el.data('type');
    if (type === 'date-range') {
      $(`#filter_${$el.data('field')}_desde`).val('');
      $(`#filter_${$el.data('field')}_hasta`).val('');
    } else { $el.val(''); }
  });
  $('#filtersBar .report-filter-op').val('>=');
  scheduleReload();
}

function scheduleReload() {
  clearTimeout(filterDebounce);
  filterDebounce = setTimeout(reloadWithTime, 350);
}

function exportCsv() {
  if (!reportsTable) { Helpers.toast('warning', 'No hay datos'); return; }
  const data = reportsTable.data().toArray();
  if (!data.length) { Helpers.toast('warning', 'No hay datos'); return; }
  const headers = reportsTable.columns().header().to$().map((i, el) => $(el).text().trim()).get();
  let csv = headers.join(',') + '\n';
  data.forEach(row => {
    csv += headers.map((_, idx) => `"${String(reportsTable.cell(row, idx).data() ?? '').replace(/"/g, '""')}"`).join(',') + '\n';
  });
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `${($('#reportModuleSelect option:selected').text() || 'reporte').replace(/\s+/g, '_')}.csv`;
  a.click();
  URL.revokeObjectURL(a.href);
  Helpers.toast('success', 'Exportado');
}

function exportPdf() {
  const module = $('#reportModuleSelect').val();
  if (!module) { Helpers.toast('warning', 'Selecciona un módulo'); return; }
  const filters = collectFilters();
  const params = new URLSearchParams({ module, action: 'generate_pdf' });
  Object.entries(filters).forEach(([k, v]) => { if (v !== '' && v != null) params.set(k, v); });
  const tr = getCurrentTimeRange();
  if (tr.d) params.set('fecha_desde', tr.d);
  if (tr.h) params.set('fecha_hasta', tr.h);
  window.open(`${baseUrl}?${params}`, '_blank');
}

$(document).ready(function () {
  $('#reportsTable, #chartCard, #reportActions, #summaryBar, #filtersWrap').addClass('d-none');
  loadModules();

  $('#reportModuleSelect').on('change', function () {
    const m = $(this).val();
    if (!m) { resetUI(); return; }
    loadFilters(m);
  });

  $('#timeFilterBar').on('click', '.time-preset', function () {
    const $this = $(this);
    if ($this.hasClass('active') && $this.data('period') !== 'all') return;
    $this.addClass('active').siblings('.time-preset').removeClass('active');
    $('#timeCustomWrap').toggleClass('show', $this.data('period') === 'custom');
    reloadWithTime();
  });

  $('#timeCustomApply').on('click', function () {
    const d = $('#timeDesde').val(), h = $('#timeHasta').val();
    if (!d || !h) { Helpers.toast('warning', 'Selecciona ambas fechas'); return; }
    reloadWithTime();
  });

  $('#filtersBar')
    .on('change', '.report-filter, .report-filter-op', function () { toggleChipValue(this); scheduleReload(); })
    .on('input', '.report-filter', function () { toggleChipValue(this); scheduleReload(); })
    .on('click', '.filter-chip-remove', function () {
      const chip = $(this).closest('.filter-chip');
      removeFilter(chip.data('field'));
    });

  $('#addFilterSelect').on('change', function () {
    const field = $(this).val();
    $(this).val('');
    if (field) addFilter(field);
  });

  $('#btnClearFilters').on('click', clearFilters);
  $('#chartTypeSelector').on('change', function () { if (currentChartData) renderChart(currentChartData, $(this).val()); });
  $('#btnRefresh').on('click', reloadWithTime);
  $('#btnCsv').on('click', exportCsv);
  $('#btnPdf').on('click', exportPdf);
});
