import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';
import * as C from '../utils/components.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}ubicaciones`;
  let ubicacionesTable = null;

  const locationValidationRules = {
    nombre_ubicacion: 'nombrePlanta'
  };

  
  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('ubicacionesTable', 5, 4);
    }
    ubicacionesTable = $('#ubicacionesTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_locations`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'ubicaciones',
      },
      columns: [
        { 
          data: 'nombre_ubicacion' 
        },
        {
          data: 'descripcion',
          render: (data) => {
            if (!data) return '<span class="text-muted">—</span>';
            const escaped = Helpers.escapeHtml(data);
            return escaped.length > 80
              ? `<span title="${escaped}">${Helpers.truncateText(escaped, 80)}</span>`
              : escaped;
          },
        },
        {
          data: 'tipo',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: null,
          orderable: false,
          searchable: false,
          width: '25%',
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
              SkeletonHelper.showTableSkeleton('ubicacionesTable', 5, 4);
            }
            ubicacionesTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  $('#btnAddLocation').on('click', function () {
    const $editModal = $('#editLocationModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addLocationModal').modal({ focus: false }).modal('show');
  });

  $('#addLocationForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), locationValidationRules)) {
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
          Helpers.toast('success', 'Ubicación agregada correctamente');
          $('#addLocationModal').modal('hide');
          ubicacionesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar ubicación');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const row = ubicacionesTable.row($(this).closest('tr')).data();

    const $addModal = $('#addLocationModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editLocationId').val(row.id);
    $('#editLocationName').val(row.nombre_ubicacion);
    $('#editLocationDesc').val(row.descripcion);
    $('#editLocationTipo').val(row.tipo);

    $('#editLocationModal').modal({ focus: false }).modal('show');
  });

  // --- Acción: Procesar Edición ---
  $('#editLocationForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), locationValidationRules, true)) {
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
          Helpers.toast('success', 'Ubicación actualizada correctamente');
          $('#editLocationModal').modal('hide');
          ubicacionesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar ubicación');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const row = ubicacionesTable.row($(this).closest('tr')).data();
    const id = row.id;
    const nombre = row.nombre_ubicacion;

    Helpers.confirmDialog(
      '¿Eliminar ubicación?',
      `¿Deseas eliminar la ubicación <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Ubicación eliminada correctamente');
              ubicacionesTable.ajax.reload(null, false);
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

  $('#addLocationModal, #editLocationModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  initDataTable();

  setupRealTimeValidation($('#addLocationForm'), locationValidationRules);
  setupRealTimeValidation($('#editLocationForm'), locationValidationRules, true);
});