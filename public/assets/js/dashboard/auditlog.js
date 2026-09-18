import * as Helpers from '../utils/helpers.js';
import * as C from '../utils/components.js';

const actionBadge = (action) => {
  const colors = {
    CREATE: 'bg-success',
    UPDATE: 'bg-primary',
    DELETE: 'bg-danger',
    LOGIN: 'bg-info',
    LOGOUT: 'bg-secondary',
    RESTORE: 'bg-warning',
  };
  const labels = {
    CREATE: 'Creación',
    UPDATE: 'Actualización',
    DELETE: 'Eliminación',
    LOGIN: 'Inicio sesión',
    LOGOUT: 'Cierre sesión',
    RESTORE: 'Restauración',
  };
  return `<span class="badge ${colors[action] || 'bg-dark'}">${labels[action] || action}</span>`;
};

const tableLabel = (table) => {
  const map = {
    plantas: 'Plantas',
    lote: 'Lotes',
    especie: 'Especies',
    ubicacion: 'Ubicaciones',
    insumo: 'Insumos',
    herramienta: 'Herramientas',
    unidad_medida: 'Unidades Medida',
    cliente: 'Clientes',
    proveedores: 'Proveedores',
    trabajadores: 'Trabajadores',
    usuario: 'Usuarios',
    roles: 'Roles',
    permisos: 'Permisos',
    venta: 'Ventas',
    compra: 'Compras',
    calculo_precio: 'Cálculo Precios',
    cuentas_pagar: 'Cuentas Pagar',
    cuentas_cobrar: 'Cuentas Cobrar',
    ajuste_inventario: 'Ajustes Inventario',
    merma: 'Mermas',
    asignar_tarea: 'Asignación Tareas',
    tarea: 'Tareas',
    movimiento_planta: 'Mov. Plantas',
    ornato: 'Ornatos',
    ampliacion: 'Ampliación',
    recoleccion_semillas: 'Recolección Semillas',
    trazabilidad: 'Trazabilidad',
    notificaciones: 'Notificaciones',
    auditoria_logs: 'Bitácora',
    password_resets: 'Restablecer Pass',
  };
  return map[table] || table.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  const d = new Date(dateStr.replace(' ', 'T'));
  return d.toLocaleString('es-VE', {
    timeZone: 'America/Caracas',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};

const diffObjects = (oldObj, newObj) => {
  const allKeys = new Set([...Object.keys(oldObj || {}), ...Object.keys(newObj || {})]);
  const fieldLabels = {
    id: 'ID',
    nombre: 'Nombre',
    nombre_cientifico: 'Nombre Científico',
    nombre_comun: 'Nombre Común',
    descripcion: 'Descripción',
    cantidad: 'Cantidad',
    precio: 'Precio',
    costo: 'Costo',
    total: 'Total',
    fecha: 'Fecha',
    fecha_creacion: 'Fecha Creación',
    fecha_actualizacion: 'Fecha Actualización',
    activo: 'Activo',
    estado: 'Estado',
    tipo: 'Tipo',
    stock: 'Stock',
    unidad: 'Unidad',
    medida: 'Medida',
    telefono: 'Teléfono',
    email: 'Correo',
    direccion: 'Dirección',
    rif: 'RIF',
    cedula: 'Cédula',
    usuario_id: 'Usuario',
    username: 'Usuario',
    password: 'Contraseña',
    rol_id: 'Rol',
    nota: 'Nota',
    observacion: 'Observación',
    motivo: 'Motivo',
    referencia: 'Referencia',
    lote_id: 'Lote',
    planta_id: 'Planta',
    proveedor_id: 'Proveedor',
    cliente_id: 'Cliente',
    trabajador_id: 'Trabajador',
    imagen: 'Imagen',
    codigo: 'Código',
    porcentaje: 'Porcentaje',
  };
  const rows = [];
  for (const key of allKeys) {
    const oldVal = oldObj?.[key];
    const newVal = newObj?.[key];
    if (oldVal === newVal) continue;
    const displayKey =
      fieldLabels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    const oldDisplay =
      oldVal !== null && oldVal !== '' && oldVal !== undefined
        ? `<span class="text-danger">${Helpers.escapeHtml(String(oldVal))}</span>`
        : '<span class="text-muted">—</span>';
    const newDisplay =
      newVal !== null && newVal !== '' && newVal !== undefined
        ? `<span class="text-success fw-bold">${Helpers.escapeHtml(String(newVal))}</span>`
        : '<span class="text-muted">—</span>';
    rows.push({ key: displayKey, old: oldDisplay, new: newDisplay });
  }
  return rows;
};

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}auditlog`;
  let auditlogTable = null;

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('auditlogTable', 10, 6);
    }
    auditlogTable = $('#auditlogTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: `${baseUrl}?action=get_auditlogs_data`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data: (d) => {
          d.fecha_desde = $('#fecha_desde').val();
          d.fecha_hasta = $('#fecha_hasta').val();
          d.id_usuario = $('#id_usuario').val();
          d.accion = $('#accion').val();
          d.tabla_afectada = $('#tabla_afectada').val();
        },
        dataSrc: 'data',
      },
      columns: [
        { data: 'fecha_accion', render: (data) => formatDate(data) },
        { data: 'nombre_usuario', defaultContent: 'Sistema' },
        { data: 'accion', render: (data) => actionBadge(data) },
        { data: 'tabla_afectada', render: (data) => tableLabel(data) },
        {
          data: 'id_registro_afectado',
          defaultContent: '—',
          render: (data) => (data ? `#${data}` : '—'),
        },
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
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100],
      ],
      responsive: true,
      autoWidth: false,
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
      dom: '<"d-flex justify-content-between align-items-center mb-2"lfB>tip',
      buttons: [
        {
          text: '<i class="fas fa-sync-alt"></i> Actualizar',
          className: 'btn btn-outline-secondary btn-sm',
          action: () => {
            if (typeof SkeletonHelper !== 'undefined')
              SkeletonHelper.showTableSkeleton('auditlogTable', 10, 6);
            auditlogTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  // Aplicar filtros
  $('#auditlogFilters').on('submit', function (e) {
    e.preventDefault();
    if (auditlogTable) auditlogTable.ajax.reload(null, false);
  });

  // Limpiar filtros
  $('#btnClearFilters').on('click', function () {
    $('#auditlogFilters')[0].reset();
    if (auditlogTable) auditlogTable.ajax.reload(null, false);
  });

  // Exportar CSV
  $('#btnExportCsv').on('click', function () {
    const params = new URLSearchParams({
      action: 'export_csv',
      fecha_desde: $('#fecha_desde').val(),
      fecha_hasta: $('#fecha_hasta').val(),
      id_usuario: $('#id_usuario').val(),
      accion: $('#accion').val(),
      tabla_afectada: $('#tabla_afectada').val(),
      search: $('#auditlogTable_filter input').val() || '',
    });
    window.location.href = `${baseUrl}?${params.toString()}`;
  });

  // Ver detalle
  $(document).on('click', '.btn-detail', function () {
    const row = auditlogTable.row($(this).closest('tr')).data();
    if (!row) return;

    const actionLabels = {
      CREATE: 'creó',
      UPDATE: 'actualizó',
      DELETE: 'eliminó',
      LOGIN: 'inició sesión',
      LOGOUT: 'cerró sesión',
      RESTORE: 'restauró',
    };
    let html = `<div class="alert alert-info py-2 mb-3 small">${Helpers.escapeHtml(row.nombre_usuario || 'Sistema')} ${actionLabels[row.accion] || row.accion}`;
    if (row.tabla_afectada)
      html += ` un registro en <strong>${tableLabel(row.tabla_afectada)}</strong>`;
    if (row.id_registro_afectado) html += ` (ID #${Helpers.escapeHtml(row.id_registro_afectado)})`;
    html += ` el ${formatDate(row.fecha_accion)}</div>`;

    let oldObj = null,
      newObj = null;
    try {
      if (row.valor_anterior) oldObj = JSON.parse(row.valor_anterior);
    } catch {}
    try {
      if (row.valor_nuevo) newObj = JSON.parse(row.valor_nuevo);
    } catch {}

    const diffRows = diffObjects(oldObj, newObj);

    if (diffRows.length > 0) {
      html += `<h6 class="mt-3 mb-2"><i class="fas fa-exchange-alt text-primary me-1"></i>Cambios detectados</h6>
        <div class="table-responsive"><table class="table table-sm table-bordered mb-0">
          <thead class="table-light"><tr><th style="width:35%">Campo</th><th>Anterior</th><th>Nuevo</th></tr></thead><tbody>`;
      for (const r of diffRows) {
        html += `<tr><td class="fw-medium">${r.key}</td><td>${r.old}</td><td>${r.new}</td></tr>`;
      }
      html += '</tbody></table></div>';
    } else if (oldObj || newObj) {
      if (newObj) {
        html += `<h6 class="mt-3 mb-2"><i class="fas fa-check-circle text-success me-1"></i>Valor actual</h6>
          <div class="table-responsive"><table class="table table-sm table-bordered mb-0"><tbody>`;
        for (const [key, val] of Object.entries(newObj)) {
          const displayKey = key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
          const displayVal =
            val !== null && val !== ''
              ? Helpers.escapeHtml(String(val))
              : '<span class="text-muted">—</span>';
          html += `<tr><td class="fw-medium" style="width:35%">${displayKey}</td><td>${displayVal}</td></tr>`;
        }
        html += '</tbody></table></div>';
      }
      if (oldObj) {
        html += `<details class="mt-2"><summary class="text-muted small" style="cursor:pointer;"><i class="fas fa-history me-1"></i>Ver valor anterior</summary>
          <div class="mt-2"><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><tbody>`;
        for (const [key, val] of Object.entries(oldObj)) {
          const displayKey = key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
          const displayVal =
            val !== null && val !== ''
              ? Helpers.escapeHtml(String(val))
              : '<span class="text-muted">—</span>';
          html += `<tr><td class="fw-medium" style="width:35%">${displayKey}</td><td>${displayVal}</td></tr>`;
        }
        html += '</tbody></table></div></div></details>';
      }
    } else {
      html += '<p class="text-muted mt-2 mb-0">No hay datos de cambio registrados.</p>';
    }

    const formatJson = (str) => {
      if (!str || str === 'null') return null;
      try {
        return JSON.stringify(JSON.parse(str), null, 2);
      } catch {
        return str;
      }
    };
    const rawOld = formatJson(row.valor_anterior);
    const rawNew = formatJson(row.valor_nuevo);
    if (rawOld || rawNew) {
      html +=
        '<details class="mt-3"><summary class="text-muted small" style="cursor:pointer;">JSON original</summary>';
      if (rawOld)
        html += `<div class="mb-1"><strong class="small text-danger">Anterior</strong><pre class="bg-light p-2 rounded mt-1 mb-2" style="font-size:0.75rem;max-height:150px;overflow-y:auto;">${Helpers.escapeHtml(rawOld)}</pre></div>`;
      if (rawNew)
        html += `<div><strong class="small text-success">Nuevo</strong><pre class="bg-light p-2 rounded mt-1 mb-0" style="font-size:0.75rem;max-height:150px;overflow-y:auto;">${Helpers.escapeHtml(rawNew)}</pre></div>`;
      html += '</details>';
    }

    $('#detailModalBody').html(html);
    $('#detailModal').modal({ focus: false }).modal('show');
  });

  $('#detailModal').on('hidden.bs.modal', function () {
    $('#detailModalBody').html('');
  });

  initDataTable();
});
