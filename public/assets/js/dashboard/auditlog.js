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
              SkeletonHelper.showTableSkeleton('auditlogTable', 10, 5);
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
      SkeletonHelper.showTableSkeleton('auditlogTable', 10, 5);
            }
            auditlogTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  const fieldLabels = {
    id: 'ID', nombre: 'Nombre', nombre_cientifico: 'Nombre Científico', nombre_comun: 'Nombre Común',
    descripcion: 'Descripción', cantidad: 'Cantidad', precio: 'Precio', costo: 'Costo',
    total: 'Total', fecha: 'Fecha', fecha_creacion: 'Fecha de Creación', fecha_actualizacion: 'Fecha de Actualización',
    activo: 'Activo', estado: 'Estado', tipo: 'Tipo', stock: 'Stock', unidad: 'Unidad',
    medida: 'Medida', telefono: 'Teléfono', email: 'Correo Electrónico', direccion: 'Dirección',
    rif: 'RIF', cedula: 'Cédula', usuario_id: 'Usuario', username: 'Nombre de Usuario',
    password: 'Contraseña', rol_id: 'Rol', nota: 'Nota', observacion: 'Observación',
    motivo: 'Motivo', referencia: 'Referencia', lote_id: 'Lote', planta_id: 'Planta',
    proveedor_id: 'Proveedor', cliente_id: 'Cliente', trabajador_id: 'Trabajador',
    imagen: 'Imagen', codigo: 'Código', porcentaje: 'Porcentaje',
  };

  // Ver detalle
  $(document).on('click', '.btn-detail', function () {
    const row = auditlogTable.row($(this).closest('tr')).data();
    if (!row) return;

    const actionLabels = { CREATE: 'creó', UPDATE: 'actualizó', DELETE: 'eliminó', LOGIN: 'inició sesión', LOGOUT: 'cerró sesión' };
    let html = `<div class="alert alert-info py-2 mb-3 small">${Helpers.escapeHtml(row.nombre_usuario)} ${actionLabels[row.accion] || row.accion}`;
    if (row.tabla_afectada) html += ` un registro en <strong>${tableLabel(row.tabla_afectada)}</strong>`;
    if (row.id_registro_afectado) html += ` (ID #${Helpers.escapeHtml(row.id_registro_afectado)})`;
    html += ` el ${formatDate(row.fecha_accion)}</div>`;

    const renderJsonTable = (jsonStr, label) => {
      if (!jsonStr || jsonStr === 'null') return '';
      let parsed;
      try { parsed = JSON.parse(jsonStr); } catch { return ''; }
      if (!parsed || typeof parsed !== 'object') return '';

      let fields = '';
      for (const [key, val] of Object.entries(parsed)) {
        const displayKey = fieldLabels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        const displayVal = val !== null && val !== '' ? Helpers.escapeHtml(String(val)) : '<span class="text-muted">—</span>';
        fields += `<tr><td class="text-nowrap" style="width:35%;font-weight:500;">${displayKey}</td><td>${displayVal}</td></tr>`;
      }

      return `
        <h6 class="mt-3 mb-2">${label === 'Anterior' ? '<i class="fas fa-arrow-left text-danger me-1"></i>Valor anterior' : '<i class="fas fa-arrow-right text-success me-1"></i>Valor nuevo'}</h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <tbody>${fields}</tbody>
          </table>
        </div>`;
    };

    const oldHtml = renderJsonTable(row.valor_anterior, 'Anterior');
    const newHtml = renderJsonTable(row.valor_nuevo, 'Nuevo');

    if (oldHtml || newHtml) {
      if (oldHtml && newHtml) {
        html += '<div class="row"><div class="col-md-6">' + oldHtml + '</div><div class="col-md-6">' + newHtml + '</div></div>';
      } else {
        html += oldHtml || newHtml;
      }
    } else {
      html += '<p class="text-muted">No hay datos de cambio registrados.</p>';
    }

    const formatJson = (str) => {
      if (!str || str === 'null') return null;
      try { return JSON.stringify(JSON.parse(str), null, 2); } catch { return str; }
    };
    const oldJson = formatJson(row.valor_anterior);
    const newJson = formatJson(row.valor_nuevo);
    if (oldJson || newJson) {
      html += '<details class="mt-3"><summary class="text-muted small" style="cursor:pointer;">Ver JSON original</summary>';
      if (oldJson) html += '<div class="mb-1"><strong class="small text-danger">Valor anterior</strong><pre class="bg-light p-2 rounded mt-1 mb-2" style="font-size:0.75rem;max-height:150px;overflow-y:auto;">' + Helpers.escapeHtml(oldJson) + '</pre></div>';
      if (newJson) html += '<div><strong class="small text-success">Valor nuevo</strong><pre class="bg-light p-2 rounded mt-1 mb-0" style="font-size:0.75rem;max-height:150px;overflow-y:auto;">' + Helpers.escapeHtml(newJson) + '</pre></div>';
      html += '</details>';
    }

    $('#detailModalBody').html(html);
    $('#detailModal').modal({ focus: false }).modal('show');
  });

  // Limpiar modal al cerrar
  $('#detailModal').on('hidden.bs.modal', function () {
    $('#detailModalBody').html('');
  });

  initDataTable();
});
