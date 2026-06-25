import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm, clearValidation } from '../utils/validation.js';
import * as C from '../utils/components.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}lotes`;
  let lotesTable = null;

  const batchValidationRules = {
    id_planta: 'select',
    id_ubicacion: 'select',
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
      SkeletonHelper.showTableSkeleton('lotesTable', 5, 13);
    }
    lotesTable = $('#lotesTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_batches`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'lotes',
      },
      columns: [
        { data: 'id' },
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
          data: 'ubicacion_nombre',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'fecha_siembra',
          render: (data) => data ? Helpers.formatDate(data) : '—',
        },
        { data: 'cantidad_inicial' },
        { data: 'cantidad_actual' },
        {
          data: 'precio_unitario',
          render: (data) => data ? Helpers.formatCurrencyBs(data) : '<span class="text-muted">—</span>',
        },
        {
          data: 'estado',
          render: (data) => Helpers.getBadge(data),
        },
        {
          data: 'categoria',
          render: (data) => {
            if (!data) return '<span class="text-muted">—</span>';
            const colors = { germinado: 'success', en_crecimiento: 'info', para_cosechar: 'warning', maduro: 'danger' };
            return `<span class="badge bg-${colors[data] || 'secondary'}">${data.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}</span>`;
          },
        },
        { data: 'origen' },
        {
          data: 'observacion',
          render: (data) => {
            if (!data) return '<span class="text-muted">—</span>';
            const escaped = Helpers.escapeHtml(data);
            return escaped.length > 80
              ? `<span title="${escaped}">${Helpers.truncateText(escaped, 80)}</span>`
              : escaped;
          },
        },
        {
          data: null,
          orderable: false,
          render: () => {
            return C.btnGroup(
              C.btnEdit('btn-edit'),
              C.btnDelete('btn-delete'),
            );
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
              SkeletonHelper.showTableSkeleton('lotesTable', 5, 12);
            }
            lotesTable.ajax.reload(null, false);
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
          lotesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar lote');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const row = lotesTable.row($(this).closest('tr')).data();

    const $addModal = $('#addBatchModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editBatchId').val(row.id);
    $('#editBatchPlant').val(row.id_planta);
    $('#editBatchLocation').val(row.id_ubicacion);
    $('#editBatchDate').val(row.fecha_siembra);
    $('#editBatchQtyInit').val(row.cantidad_inicial);
    $('#editBatchQtyCurr').val(row.cantidad_actual);
    $('#editBatchStatus').val(row.estado);
    $('#editBatchCategoria').val(row.categoria || '');
    $('#editBatchOrigen').val(row.origen);
    $('#editBatchObs').val(row.observacion);

    const imagen = row.imagen;
    const $currentImg = $('#editImageCurrent');
    if (imagen) {
      $currentImg.show().find('img').attr('src', `${window.BASE_URL || '/'}${imagen}`);
    } else {
      $currentImg.hide();
    }

    clearValidation($('#editBatchForm'));

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
          lotesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar lote');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const row = lotesTable.row($(this).closest('tr')).data();
    const id = row.id;
    const nombre = row.planta_nombre;

    Helpers.confirmDialog(
      '¿Eliminar lote?',
      `¿Deseas eliminar el lote de <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Lote eliminado correctamente');
              lotesTable.ajax.reload(null, false);
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
