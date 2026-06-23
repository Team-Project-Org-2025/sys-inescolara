import * as Helpers from '../utils/helpers.js';
import * as C from '../utils/components.js';

const actionBadge = (action) => {
  const colors = { CREATE: 'bg-success', UPDATE: 'bg-primary', DELETE: 'bg-danger', LOGIN: 'bg-info', LOGOUT: 'bg-secondary' };
  const labels = { CREATE: 'Creación', UPDATE: 'Actualización', DELETE: 'Eliminación', LOGIN: 'Inicio de sesión', LOGOUT: 'Cierre de sesión' };
  return `<span class="badge ${colors[action] || 'bg-dark'}">${labels[action] || action}</span>`;
};

const tableLabel = (table) => {
  const map = {
    plantas: 'Plantas', lote: 'Lotes', especie: 'Especies', ubicacion: 'Ubicaciones',
    insumo: 'Insumos', herramienta: 'Herramientas', unidad_medida: 'Unidades de Medida',
    cliente: 'Clientes', proveedores: 'Proveedores', trabajadores: 'Trabajadores',
    usuario: 'Usuarios', roles: 'Roles', permisos: 'Permisos',
    venta: 'Ventas', compra: 'Compras', calculo_precio: 'Cálculo de Precios',
    cuentas_pagar: 'Cuentas por Pagar', cuentas_cobrar: 'Cuentas por Cobrar',
    ajuste_inventario: 'Ajustes de Inventario', merma: 'Mermas',
    asignar_tarea: 'Asignación de Tareas', tarea: 'Tareas',
    movimiento_planta: 'Movimientos de Plantas', ornato: 'Ornatos',
    ampliacion: 'Ampliación', recoleccion_semillas: 'Recolección de Semillas',
    trazabilidad: 'Trazabilidad', notificaciones: 'Notificaciones',
    auditoria_logs: 'Bitácora', password_resets: 'Restablecimiento de Contraseñas',
  };
  return map[table] || table.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
};

const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  const d = new Date(dateStr.replace(' ', 'T') + 'Z');
  return d.toLocaleString('es-VE', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}auditlog`;
  let auditlogTable = null;

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('auditlogTable', 10, 6);
    }
    auditlogTable = $('#auditlogTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_auditlogs`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'auditlogs',
      },
      columns: [
        { data: 'fecha_accion', render: (data) => formatDate(data) },
        { data: 'nombre_usuario' },
        { data: 'accion', render: (data) => actionBadge(data) },
        { data: 'tabla_afectada', render: (data) => tableLabel(data) },
        { data: 'id_registro_afectado', render: (data) => data ? `<span class="badge bg-dark bg-opacity-10 text-dark">#${data}</span>` : '—' },
        {
          data: null,
          orderable: false,
          render: (data) => {
            const hasOld = data.valor_anterior && data.valor_anterior !== 'null';
            const hasNew = data.valor_nuevo && data.valor_nuevo !== 'null';
            if (!hasOld && !hasNew) return '—';
            return C.btnView('btn-detail');
          },
        },
      ],
      order: [[0, 'desc']],
      pageLength: 25,
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
              SkeletonHelper.showTableSkeleton('auditlogTable', 10, 6);
            }
            auditlogTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  // Ver detalle
  $(document).on('click', '.btn-detail', function () {
    const row = auditlogTable.row($(this).closest('tr')).data();
    if (!row) return;

    const renderJsonTable = (jsonStr, label) => {
      if (!jsonStr || jsonStr === 'null') return '';
      let parsed;
      try { parsed = JSON.parse(jsonStr); } catch { return ''; }
      if (!parsed || typeof parsed !== 'object') return '';

      let fields = '';
      for (const [key, val] of Object.entries(parsed)) {
        const displayKey = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        const displayVal = val !== null && val !== '' ? Helpers.escapeHtml(String(val)) : '<span class="text-muted">—</span>';
        fields += `<tr><td class="text-nowrap" style="width:35%;font-weight:500;">${displayKey}</td><td>${displayVal}</td></tr>`;
      }

      return `
        <h6 class="${label === 'Anterior' ? 'text-danger' : 'text-success'} mb-2">Valor ${label}</h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <tbody>${fields}</tbody>
          </table>
        </div>
        <details class="mt-2">
          <summary class="text-muted small" style="cursor:pointer;">Ver JSON original</summary>
          <pre class="bg-light p-2 rounded mt-1 mb-0" style="font-size:0.75rem;max-height:150px;overflow-y:auto;">${Helpers.escapeHtml(JSON.stringify(parsed, null, 2))}</pre>
        </details>`;
    };

    let html = renderJsonTable(row.valor_anterior, 'Anterior');
    html += renderJsonTable(row.valor_nuevo, 'Nuevo');
    if (!html) html = '<p class="text-muted">No hay datos de cambio registrados.</p>';

    $('#detailModalBody').html(html);
    $('#detailModal').modal({ focus: false }).modal('show');
  });

  // Limpiar modal al cerrar
  $('#detailModal').on('hidden.bs.modal', function () {
    $('#detailModalBody').html('');
  });

  initDataTable();
});
