import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}unit-measures`;
  let unitsTable = null;

  const unitValidationRules = {
    nombre: 'nombrePlanta',
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('unitsTable', 5, 3);
    }
    unitsTable = $('#unitsTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_units`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'units',
      },
      columns: [
        { data: 'nombre_unidad_medida' },
        {
          data: 'simbolo',
          render: (data) => data
            ? `<code class="px-2 py-1 bg-light rounded">${Helpers.escapeHtml(data)}</code>`
            : '<span class="text-muted">—</span>',
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre="${Helpers.escapeHtml(data.nombre_unidad_medida)}"
                        data-simbolo="${Helpers.escapeHtml(data.simbolo || '')}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre="${Helpers.escapeHtml(data.nombre_unidad_medida)}">
                    <i class="fas fa-trash"></i>
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
              SkeletonHelper.showTableSkeleton('unitsTable', 5, 3);
            }
            unitsTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  $('#btnAddUnit').on('click', function () {
    const $editModal = $('#editUnitModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addUnitModal').modal({ focus: false }).modal('show');
  });

  $('#addUnitForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), unitValidationRules)) {
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
          Helpers.toast('success', 'Unidad agregada correctamente');
          $('#addUnitModal').modal('hide');
          unitsTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar unidad');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    const $addModal = $('#addUnitModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editUnitId').val($btn.data('id'));
    $('#editUnitName').val($btn.data('nombre'));
    $('#editUnitSymbol').val($btn.data('simbolo'));

    $('#editUnitModal').modal({ focus: false }).modal('show');
  });

  $('#editUnitForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), unitValidationRules, true)) {
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
          Helpers.toast('success', 'Unidad actualizada correctamente');
          $('#editUnitModal').modal('hide');
          unitsTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar unidad');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre');

    Helpers.confirmDialog(
      '¿Eliminar unidad?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Unidad eliminada correctamente');
              unitsTable.ajax.reload(null, false);
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

  $('#addUnitModal, #editUnitModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  initDataTable();

  setupRealTimeValidation($('#addUnitForm'), unitValidationRules);
  setupRealTimeValidation($('#editUnitForm'), unitValidationRules, true);
});
