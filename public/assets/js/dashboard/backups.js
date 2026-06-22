import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';

function formatSize(bytes) {
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
  if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
  return bytes + ' B';
}

function dbLabelBadge(label) {
  const map = {
    Datos: 'bg-primary',
    Seguridad: 'bg-info',
  };
  return `<span class="badge ${map[label] || 'bg-secondary'}">${label}</span>`;
}

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}backups`;
  let backupsTable = null;

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('backupsTable', 10, 4);
    }
    backupsTable = $('#backupsTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_backups`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'backups',
      },
      columns: [
        {
          data: 'db_label',
          render: (data) => dbLabelBadge(data),
        },
        { data: 'date' },
        {
          data: 'size_formatted',
          className: 'text-end',
        },
        {
          data: null,
          orderable: false,
          className: 'text-center',
          render: (data) => {
            const downloadUrl = `${baseUrl}?action=download_backup&file=${encodeURIComponent(data.filename)}`;
            return `
              <div class="btn-group btn-group-sm">
                <a href="${downloadUrl}" class="btn btn-outline-primary" title="Descargar">
                  <i class="fas fa-download"></i> Descargar
                </a>
                <button class="btn btn-outline-warning btn-restore" data-filename="${data.filename}" data-db="${data.db_label}" title="Restaurar">
                  <i class="fas fa-undo"></i> Restaurar
                </button>
                <button class="btn btn-outline-danger btn-delete-backup" data-filename="${data.filename}" title="Eliminar">
                  <i class="fas fa-trash"></i> Eliminar
                </button>
              </div>
            `;
          },
        },
      ],
      order: [[1, 'desc']],
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
              SkeletonHelper.showTableSkeleton('backupsTable', 10, 4);
            }
            backupsTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  // Crear respaldo
  $('#createBackupBtn').on('click', function () {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando respaldo...');

    $.ajax({
      url: `${baseUrl}?action=create_backup`,
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      success: function (res) {
        if (res.success) {
          Helpers.toast('success', res.message);
          backupsTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', res.message);
        }
      },
      error: function (xhr) {
        const res = xhr.responseJSON || {};
        Helpers.toast('error', res.message || 'Error al crear el respaldo');
      },
      complete: function () {
        btn.prop('disabled', false).html('<i class="fas fa-database"></i> Crear Respaldo Completo');
      },
    });
  });

  // Abrir modal de restauración
  $(document).on('click', '.btn-restore', function () {
    const filename = $(this).data('filename');
    const db = $(this).data('db');
    $('#restoreFileName').text(filename);
    $('#restoreDbName').text(db);
    $('#confirmRestoreBtn').data('filename', filename);
    $('#restoreModal').modal({ focus: false }).modal('show');
  });

  // Confirmar restauración
  $('#confirmRestoreBtn').on('click', function () {
    const filename = $(this).data('filename');
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Restaurando...');

    $.ajax({
      url: `${baseUrl}?action=restore_backup`,
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      data: { filename },
      success: function (res) {
        if (res.success) {
          Helpers.toast('success', res.message);
          $('#restoreModal').modal('hide');
        } else {
          Helpers.toast('error', res.message);
        }
      },
      error: function (xhr) {
        const res = xhr.responseJSON || {};
        Helpers.toast('error', res.message || 'Error al restaurar');
      },
      complete: function () {
        btn.prop('disabled', false).html('<i class="fas fa-undo"></i> Restaurar');
      },
    });
  });

  // Eliminar respaldo
  $(document).on('click', '.btn-delete-backup', function () {
    const filename = $(this).data('filename');
    const row = $(this).closest('tr');

    Helpers.confirmDialog(
      '¿Eliminar respaldo?',
      `Se eliminará el archivo <strong>${filename}</strong>. Esta acción no se puede deshacer.`,
      () => {
        $.ajax({
          url: `${baseUrl}?action=delete_backup`,
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          data: { filename },
          success: function (res) {
            if (res.success) {
              Helpers.toast('success', res.message);
              backupsTable.ajax.reload(null, false);
            } else {
              Helpers.toast('error', res.message);
            }
          },
          error: function (xhr) {
            const res = xhr.responseJSON || {};
            Helpers.toast('error', res.message || 'Error al eliminar');
          },
        });
      },
      'Sí, eliminar'
    );
  });

  // Limpiar modal al cerrar
  $('#restoreModal').on('hidden.bs.modal', function () {
    $('#confirmRestoreBtn')
      .prop('disabled', false)
      .html('<i class="fas fa-undo"></i> Restaurar');
  });

  initDataTable();
});
