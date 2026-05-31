import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}batches`;
  let batchesTable = null;

  const batchValidationRules = {
    id_planta: 'select',
    fecha_siembra: 'fechaFuturaCheck',
    cantidad_inicial: 'cantidad',
    cantidad_actual: 'cantidad',
    estado: 'select',
    origen: 'select',
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
      SkeletonHelper.showTableSkeleton('batchesTable', 5, 9);
    }
    batchesTable = $('#batchesTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_batches`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'batches',
      },
      columns: [
        {
          data: 'imagen',
          render: (data) => {
            const url = data ? `${window.BASE_URL || '/'}${data}` : null;
            return url
              ? `<img src="${url}" class="batch-thumb" data-img="${url}" data-bs-toggle="modal" data-bs-target="#imageLightbox" title="Ver imagen">`
              : `<div class="batch-thumb-placeholder"><i class="fas fa-leaf"></i></div>`;
          },
          orderable: false,
        },
        { data: 'planta_nombre' },
        {
          data: 'especie_nombre',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'fecha_siembra',
          render: (data) => data ? Helpers.formatDate(data) : '—',
        },
        { data: 'cantidad_inicial' },
        { data: 'cantidad_actual' },
        {
          data: 'estado',
          render: (data) => Helpers.getBadge(data),
        },
        { data: 'origen' },
        {
          data: 'observacion',
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
                        data-id_planta="${Helpers.escapeHtml(data.id_planta)}"
                        data-fecha_siembra="${Helpers.escapeHtml(data.fecha_siembra || '')}"
                        data-cantidad_inicial="${Helpers.escapeHtml(data.cantidad_inicial)}"
                        data-cantidad_actual="${Helpers.escapeHtml(data.cantidad_actual)}"
                        data-estado="${Helpers.escapeHtml(data.estado || '')}"
                        data-origen="${Helpers.escapeHtml(data.origen || '')}"
                        data-observacion="${Helpers.escapeHtml(data.observacion || '')}"
                        data-imagen="${Helpers.escapeHtml(data.imagen || '')}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre="${Helpers.escapeHtml(data.planta_nombre || '')}">
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
              SkeletonHelper.showTableSkeleton('batchesTable', 5, 9);
            }
            batchesTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  showImagePreview('addBatchImage', 'addBatchPreview');
  showImagePreview('editBatchImage', 'editBatchPreview');

  $(document).on('click', '.batch-thumb', function () {
    const src = $(this).data('img');
    $('#lightboxImg').attr('src', src);
  });

  $('#imageLightbox').on('hidden.bs.modal', function () {
    $('#lightboxImg').attr('src', '');
  });

  $('#btnAddBatch').on('click', function () {
    const $editModal = $('#editBatchModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addBatchModal').modal({ focus: false }).modal('show');
  });

  $('#addBatchForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), batchValidationRules)) {
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
          Helpers.toast('success', 'Lote agregado correctamente');
          $('#addBatchModal').modal('hide');
          batchesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar lote');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    const $addModal = $('#addBatchModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editBatchId').val($btn.data('id'));
    $('#editBatchPlant').val($btn.data('id_planta'));
    $('#editBatchDate').val($btn.data('fecha_siembra'));
    $('#editBatchQtyInit').val($btn.data('cantidad_inicial'));
    $('#editBatchQtyCurr').val($btn.data('cantidad_actual'));
    $('#editBatchStatus').val($btn.data('estado'));
    $('#editBatchOrigen').val($btn.data('origen'));
    $('#editBatchObs').val($btn.data('observacion'));

    const imagen = $btn.data('imagen');
    const $currentImg = $('#editImageCurrent');
    if (imagen) {
      $currentImg.show().find('img').attr('src', `${window.BASE_URL || '/'}${imagen}`);
    } else {
      $currentImg.hide();
    }

    $('#editBatchModal').modal({ focus: false }).modal('show');
  });

  $('#editBatchForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), batchValidationRules, true)) {
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
          Helpers.toast('success', 'Lote actualizado correctamente');
          $('#editBatchModal').modal('hide');
          batchesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar lote');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre');

    Helpers.confirmDialog(
      '¿Eliminar lote?',
      `¿Deseas eliminar el lote de <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Lote eliminado correctamente');
              batchesTable.ajax.reload(null, false);
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

  $('#addBatchModal, #editBatchModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
    $(this).find('.img-preview').hide();
    $('#editImageCurrent').hide();
  });

  initDataTable();

  setupRealTimeValidation($('#addBatchForm'), batchValidationRules);
  setupRealTimeValidation($('#editBatchForm'), batchValidationRules, true);
});
