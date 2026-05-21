import * as Helpers from '../utils/helpers.js';

const actionBadge = (action) => {
  const map = {
    CREATE: 'bg-success',
    UPDATE: 'bg-primary',
    DELETE: 'bg-danger',
    LOGIN: 'bg-info',
    LOGOUT: 'bg-secondary',
  };
  return `<span class="badge ${map[action] || 'bg-dark'}">${action}</span>`;
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
        {
          data: 'fecha_accion',
          render: (data) => formatDate(data),
        },
        { data: 'nombre_usuario' },
        {
          data: 'accion',
          render: (data) => actionBadge(data),
        },
        { data: 'tabla_afectada' },
        { data: 'id_registro_afectado' },
        {
          data: null,
          orderable: false,
          render: (data) => {
            const hasOld = data.valor_anterior && data.valor_anterior !== 'null';
            const hasNew = data.valor_nuevo && data.valor_nuevo !== 'null';
            if (!hasOld && !hasNew) return '—';
            return `<button class="btn btn-sm btn-outline-info btn-detail" data-id="${data.id_log}"><i class="fas fa-eye"></i> Ver</button>`;
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
    const id = $(this).data('id');
    const tableData = auditlogTable.rows().data().toArray();
    const row = tableData.find((r) => r.id_log == id);
    if (!row) return;

    let html = '';
    if (row.valor_anterior && row.valor_anterior !== 'null') {
      html += `<h6 class="text-danger mb-2">Valor Anterior</h6><pre class="bg-light p-3 rounded" style="font-size:0.8rem;max-height:200px;overflow-y:auto;">${Helpers.escapeHtml(JSON.stringify(JSON.parse(row.valor_anterior), null, 2))}</pre>`;
    }
    if (row.valor_nuevo && row.valor_nuevo !== 'null') {
      html += `<h6 class="text-success mb-2 mt-3">Valor Nuevo</h6><pre class="bg-light p-3 rounded" style="font-size:0.8rem;max-height:200px;overflow-y:auto;">${Helpers.escapeHtml(JSON.stringify(JSON.parse(row.valor_nuevo), null, 2))}</pre>`;
    }
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
