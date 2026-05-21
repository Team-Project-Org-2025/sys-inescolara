import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}plants`;
  let plantsTable = null;

  const plantValidationRules = {
    nombre_comun: 'nombrePlanta',
    nombre_tecnico: 'nombrePlanta',
    especie_id: 'select',
    imagen: 'default'
  };

  const showImagePreview = (inputId, previewId) => {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;
    input.addEventListener('change', function () {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      } else {
        preview.style.display = 'none';
      }
    });
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('plantsTable', 5, 5);
    }
    plantsTable = $('#plantsTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_plants`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'plants',
      },
      columns: [
        {
          data: 'imagen',
          render: (data) => {
            const url = data ? `${window.BASE_URL || '/'}${data}` : null;
            return url
              ? `<img src="${url}" class="plant-thumb" data-img="${url}" data-bs-toggle="modal" data-bs-target="#imageLightbox" title="Ver imagen">`
              : `<div class="plant-thumb-placeholder"><i class="fas fa-leaf"></i></div>`;
          },
          orderable: false,
        },
        { data: 'nombre_comun' },
        {
          data: 'nombre_tecnico',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'especie_nombre',
          render: (data) => data || '<span class="text-muted">Sin especie</span>',
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre_comun="${Helpers.escapeHtml(data.nombre_comun)}"
                        data-nombre_tecnico="${Helpers.escapeHtml(data.nombre_tecnico || '')}"
                        data-especie_id="${Helpers.escapeHtml(data.especie_id || '')}"
                        data-imagen="${Helpers.escapeHtml(data.imagen || '')}">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre="${Helpers.escapeHtml(data.nombre_comun || '')}">
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
              SkeletonHelper.showTableSkeleton('plantsTable', 5, 5);
            }
            plantsTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  showImagePreview('addPlantImage', 'addPlantPreview');
  showImagePreview('editPlantImage', 'editPlantPreview');

  $(document).on('click', '.plant-thumb', function () {
    const src = $(this).data('img');
    $('#lightboxImg').attr('src', src);
  });

  $('#imageLightbox').on('hidden.bs.modal', function () {
    $('#lightboxImg').attr('src', '');
  });

  $('#btnAddPlant').on('click', function () {
    const $editModal = $('#editPlantModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addPlantModal').modal({ focus: false }).modal('show');
  });

  $('#addPlantForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), plantValidationRules)) {
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
          Helpers.toast('success', 'Planta agregada correctamente');
          $('#addPlantModal').modal('hide');
          plantsTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar planta');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    const $addModal = $('#addPlantModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editPlantId').val($btn.data('id'));
    $('#editPlantName').val($btn.data('nombre_comun'));
    $('#editPlantTecnico').val($btn.data('nombre_tecnico'));
    $('#editPlantSpecies').val($btn.data('especie_id'));

    const imagen = $btn.data('imagen');
    const $currentImg = $('#editImageCurrent');
    if (imagen) {
      $currentImg.show().find('img').attr('src', `${window.BASE_URL || '/'}${imagen}`);
    } else {
      $currentImg.hide();
    }

    $('#editPlantModal').modal({ focus: false }).modal('show');
  });

  $('#editPlantForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), plantValidationRules, true)) {
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
          Helpers.toast('success', 'Planta actualizada correctamente');
          $('#editPlantModal').modal('hide');
          plantsTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar planta');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre');

    Helpers.confirmDialog(
      '¿Eliminar planta?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Planta eliminada correctamente');
              plantsTable.ajax.reload(null, false);
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

  $('#addPlantModal, #editPlantModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
    $(this).find('.img-preview').hide();
    $('#editImageCurrent').hide();
  });

  initDataTable();

  setupRealTimeValidation($('#addPlantForm'), plantValidationRules);
  setupRealTimeValidation($('#editPlantForm'), plantValidationRules, true);
});
