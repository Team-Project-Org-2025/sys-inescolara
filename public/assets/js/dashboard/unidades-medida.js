import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';
import * as C from '../utils/components.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}unidades-medida`;
  let unidadesMedidaTable = null;

  const unitValidationRules = {
    nombre: 'nombrePlanta',
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('unidadesMedidaTable', 5, 2);
    }
    unidadesMedidaTable = $('#unidadesMedidaTable').DataTable({
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
              SkeletonHelper.showTableSkeleton('unidadesMedidaTable', 5, 2);
            }
            unidadesMedidaTable.ajax.reload(null, false);
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
          unidadesMedidaTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar unidad');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const row = unidadesMedidaTable.row($(this).closest('tr')).data();

    const $addModal = $('#addUnitModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editUnitId').val(row.id);
    $('#editUnitName').val(row.nombre_unidad_medida);

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
          unidadesMedidaTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar unidad');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const row = unidadesMedidaTable.row($(this).closest('tr')).data();
    const id = row.id;
    const nombre = row.nombre_unidad_medida;

    Helpers.confirmDialog(
      '¿Eliminar unidad?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Unidad eliminada correctamente');
              unidadesMedidaTable.ajax.reload(null, false);
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
