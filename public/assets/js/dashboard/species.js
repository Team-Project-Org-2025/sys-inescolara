import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm, clearValidation } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}species`;
  let speciesTable = null;

  const speciesRules = {
    nombre_especie: 'nombreProducto',
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('speciesTable', 5, 3);
    }
    speciesTable = $('#speciesTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_species`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'species',
      },
      order: [[0, 'asc']],
      columns: [
        { data: 'nombre_especie' },
        {
          data: 'descripcion',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre_especie="${Helpers.escapeHtml(data.nombre_especie)}"
                        data-descripcion="${Helpers.escapeHtml(data.descripcion || '')}">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre_especie="${Helpers.escapeHtml(data.nombre_especie)}">
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
              SkeletonHelper.showTableSkeleton('speciesTable', 5, 3);
            }
            speciesTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  // Agregar especie
  $('#btnAddSpecies').on('click', function () {
    const $editModal = $('#editSpeciesModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addSpeciesModal').modal({ focus: false }).modal('show');
  });

  $('#addSpeciesForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), speciesRules)) {
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
          Helpers.toast('success', 'Especie agregada correctamente');
          $('#addSpeciesModal').modal('hide');
          speciesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar especie');
      });
  });

  // Editar especie
  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    const $addModal = $('#addSpeciesModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editSpeciesId').val($btn.data('id'));
    $('#editSpeciesName').val($btn.data('nombre_especie'));
    $('#editSpeciesDescripcion').val($btn.data('descripcion'));

    $('#editSpeciesModal').modal({ focus: false }).modal('show');
  });

  $('#editSpeciesForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), speciesRules, true)) {
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
          Helpers.toast('success', 'Especie actualizada correctamente');
          $('#editSpeciesModal').modal('hide');
          speciesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar especie');
      });
  });

  // Eliminar especie
  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre_especie');

    Helpers.confirmDialog(
      '¿Eliminar especie?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Especie eliminada correctamente');
              speciesTable.ajax.reload(null, false);
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

  // Limpiar modales
  $('#addSpeciesModal, #editSpeciesModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  initDataTable();

  setupRealTimeValidation($('#addSpeciesForm'), speciesRules);
  setupRealTimeValidation($('#editSpeciesForm'), speciesRules, true);
});
