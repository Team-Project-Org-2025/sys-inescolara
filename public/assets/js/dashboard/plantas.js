import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}plantas`;
  let plantasTable = null;

  const plantValidationRules = {
    nombre_comun: 'nombrePlanta',
    nombre_tecnico: 'nombrePlanta',
    especie_id: 'select',
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
      SkeletonHelper.showTableSkeleton('plantasTable', 5, 7);
    }
    plantasTable = $('#plantasTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_plants`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'plantas',
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
        { data: 'stock_lotes', render: (data) => data ?? '0', className: 'text-end' },
        {
          data: 'precio_vigente',
          render: (data) => data ? Helpers.formatCurrencyBs(data) : '<span class="text-muted">—</span>',
          className: 'text-end',
        },
        {
          data: null,
          orderable: false,
          render: () => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete">
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
              SkeletonHelper.showTableSkeleton('plantasTable', 5, 7);
            }
            plantasTable.ajax.reload(null, false);
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
          plantasTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar planta');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const row = plantasTable.row($(this).closest('tr')).data();

    const $addModal = $('#addPlantModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editPlantId').val(row.id);
    $('#editPlantName').val(row.nombre_comun);
    $('#editPlantTecnico').val(row.nombre_tecnico);
    $('#editPlantSpecies').val(row.especie_id);

    const imagen = row.imagen;
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
          plantasTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar planta');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const row = plantasTable.row($(this).closest('tr')).data();
    const id = row.id;
    const nombre = row.nombre_comun;

    Helpers.confirmDialog(
      '¿Eliminar planta?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Planta eliminada correctamente');
              plantasTable.ajax.reload(null, false);
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
