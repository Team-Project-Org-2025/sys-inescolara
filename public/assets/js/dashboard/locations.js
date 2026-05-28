import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}locations`;
  let locationsTable = null;

  const locationValidationRules = {
    nombre_ubicacion: 'nombrePlanta'
  };

  
  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('locationsTable', 5, 3);
    }
    
    locationsTable = $('#locationsTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_locations`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'locations',
      },
      columns: [
        { 
          data: 'id',
          width: '10%'
        },
        { 
          data: 'nombre_ubicacion' 
        },
        {
          data: null,
          orderable: false,
          searchable: false,
          width: '25%',
          render: (data) => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre_ubicacion="${Helpers.escapeHtml(data.nombre_ubicacion)}">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre="${Helpers.escapeHtml(data.nombre_ubicacion || '')}">
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
              SkeletonHelper.showTableSkeleton('locationsTable', 5, 3);
            }
            locationsTable.ajax.reload(null, false);
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
          locationsTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar ubicación');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    const $addModal = $('#addLocationModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editLocationId').val($btn.data('id'));
    $('#editLocationName').val($btn.data('nombre_ubicacion'));

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
          locationsTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar ubicación');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre');

    Helpers.confirmDialog(
      '¿Eliminar ubicación?',
      `¿Deseas eliminar la ubicación <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Ubicación eliminada correctamente');
              locationsTable.ajax.reload(null, false);
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