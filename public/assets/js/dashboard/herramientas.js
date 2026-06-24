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
    tipo: 'text',
    estado: 'select',
    fecha_adquisicion: 'fechaFuturaCheck',
    fecha_ultimo_mantenimiento: 'fechaFuturaCheck',
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('herramientasTable', 5, 8);
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
          render: () => C.btnGroup(
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
              SkeletonHelper.showTableSkeleton('herramientasTable', 5, 7);
            }
            herramientasTable.ajax.reload(null, false);
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
    $('#editToolType').val(row.tipo);
    $('#editToolStatus').val(row.estado);
    $('#editToolAcqDate').val(row.fecha_adquisicion);
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
