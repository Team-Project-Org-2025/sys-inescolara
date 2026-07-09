import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';
import * as C from '../utils/components.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}herramientas`;
  let herramientasTable = null;

  const toolValidationRules = {
    nombre_herramienta: 'nombrePlanta',
    cantidad: 'cantidad',
    estado: 'select',
    fecha_ultimo_mantenimiento: 'fechaFuturaCheck',
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('herramientasTable', 5, 4);
    }
    herramientasTable = $('#herramientasTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_tools`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'tools',
      },
      columns: [
        { data: 'nombre_herramienta' },
        { data: 'cantidad', className: 'text-center' },
        {
          data: 'estado',
          render: (data) => {
            const badges = {
              disponible: '<span class="badge bg-success">Disponible</span>',
              'en uso': '<span class="badge bg-primary">En Uso</span>',
              dañado: '<span class="badge bg-danger">Da&ntilde;ado</span>',
            };
            return badges[data] || `<span class="badge bg-secondary">${Helpers.escapeHtml(data)}</span>`;
          },
        },
        {
          data: null,
          orderable: false,
          render: () => C.btnGroup(
              C.btnView('btn-view'),
              C.btnEdit('btn-edit'),
              C.btnDelete('btn-delete'),
            ),
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
              SkeletonHelper.showTableSkeleton('herramientasTable', 5, 4);
            }
            herramientasTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  $(document).on('click', '.btn-view', function () {
    const row = herramientasTable.row($(this).closest('tr')).data();

    $('#viewToolNombre').text(row.nombre_herramienta || '—');
    $('#viewToolCantidad').text(row.cantidad ?? '0');

    const badges = {
      disponible: 'Disponible',
      'en uso': 'En Uso',
      dañado: 'Dañado',
    };
    $('#viewToolEstado').text(badges[row.estado] || row.estado || '—');
    $('#viewToolFechaMant').text(row.fecha_ultimo_mantenimiento || '—');
    $('#viewToolObs').text(row.observacion || '—');

    $('#viewToolModal').modal({ focus: false }).modal('show');
  });

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
          herramientasTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar herramienta');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const row = herramientasTable.row($(this).closest('tr')).data();

    const $addModal = $('#addToolModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editToolId').val(row.id);
    $('#editToolName').val(row.nombre_herramienta);
    $('#editToolCantidad').val(row.cantidad);
    $('#editToolStatus').val(row.estado);
    $('#editToolMaintDate').val(row.fecha_ultimo_mantenimiento);
    $('#editToolObs').val(row.observacion);

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
          herramientasTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar herramienta');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const row = herramientasTable.row($(this).closest('tr')).data();
    const id = row.id;
    const nombre = row.nombre_herramienta;

    Helpers.confirmDialog(
      '¿Eliminar herramienta?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong> (cantidad: ${row.cantidad})?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              herramientasTable.ajax.reload(null, false);
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
