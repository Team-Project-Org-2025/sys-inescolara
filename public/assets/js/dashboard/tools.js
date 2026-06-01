import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}tools`;
  let toolsTable = null;

  const toolValidationRules = {
    nombre_herramienta: 'nombrePlanta',
    tipo: 'text',
    estado: 'select',
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('toolsTable', 5, 7);
    }
    toolsTable = $('#toolsTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_tools`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'tools',
      },
      columns: [
        { data: 'nombre_herramienta' },
        {
          data: 'tipo',
          render: (data) => data || '<span class="text-muted">&mdash;</span>',
        },
        {
          data: 'estado',
          render: (data) => {
            const badges = {
              disponible: '<span class="badge bg-success">Disponible</span>',
              en_uso: '<span class="badge bg-primary">En Uso</span>',
              mantenimiento: '<span class="badge bg-warning text-dark">Mantenimiento</span>',
              danada: '<span class="badge bg-danger">Da&ntilde;ada</span>',
              baja: '<span class="badge bg-secondary">De Baja</span>',
            };
            return badges[data] || `<span class="badge bg-secondary">${Helpers.escapeHtml(data)}</span>`;
          },
        },
        {
          data: 'fecha_adquisicion',
          render: (data) => data || '<span class="text-muted">&mdash;</span>',
        },
        {
          data: 'fecha_ultimo_mantenimiento',
          render: (data) => data || '<span class="text-muted">&mdash;</span>',
        },
        {
          data: 'observacion',
          render: (data) => data ? Helpers.truncateText(data, 50) : '<span class="text-muted">&mdash;</span>',
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre_herramienta="${Helpers.escapeHtml(data.nombre_herramienta)}"
                        data-tipo="${Helpers.escapeHtml(data.tipo || '')}"
                        data-estado="${Helpers.escapeHtml(data.estado)}"
                        data-fecha_adquisicion="${Helpers.escapeHtml(data.fecha_adquisicion || '')}"
                        data-fecha_ultimo_mantenimiento="${Helpers.escapeHtml(data.fecha_ultimo_mantenimiento || '')}"
                        data-observacion="${Helpers.escapeHtml(data.observacion || '')}">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre="${Helpers.escapeHtml(data.nombre_herramienta || '')}">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
              </div>
            `;
          },
        },
      ],
      pageLength: 10,
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
              SkeletonHelper.showTableSkeleton('toolsTable', 5, 7);
            }
            toolsTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  $('#btnAddTool').on('click', function () {
    const $editModal = $('#editToolModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addToolModal').modal({ focus: false }).modal('show');
  });

  $('#addToolForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), toolValidationRules)) {
      Helpers.toast('error', 'Por favor, verifique los campos marcados en rojo.');
      return;
    }

    const formData = new FormData(this);

    $.ajax({
      url: `${baseUrl}?action=add_ajax`,
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
          $('#addToolModal').modal('hide');
          toolsTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar herramienta');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    const $addModal = $('#addToolModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editToolId').val($btn.data('id'));
    $('#editToolName').val($btn.data('nombre_herramienta'));
    $('#editToolType').val($btn.data('tipo'));
    $('#editToolStatus').val($btn.data('estado'));
    $('#editToolAcqDate').val($btn.data('fecha_adquisicion'));
    $('#editToolMaintDate').val($btn.data('fecha_ultimo_mantenimiento'));
    $('#editToolObs').val($btn.data('observacion'));

    $('#editToolModal').modal({ focus: false }).modal('show');
  });

  $('#editToolForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), toolValidationRules, true)) {
      Helpers.toast('error', 'Por favor, verifique los campos marcados en rojo.');
      return;
    }

    const formData = new FormData(this);

    $.ajax({
      url: `${baseUrl}?action=edit_ajax`,
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
          $('#editToolModal').modal('hide');
          toolsTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar herramienta');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre');

    Helpers.confirmDialog(
      '¿Eliminar herramienta?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              toolsTable.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          })
          .catch((err) => {
            Helpers.toast('error', err);
          });
      },
      'Sí, eliminar'
    );
  });

  $('#addToolModal, #editToolModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  initDataTable();

  setupRealTimeValidation($('#addToolForm'), toolValidationRules);
  setupRealTimeValidation($('#editToolForm'), toolValidationRules, true);
});
