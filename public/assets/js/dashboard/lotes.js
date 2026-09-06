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
    id_estado: 'select',
    id_origen: 'select',
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
      SkeletonHelper.showTableSkeleton('lotesTable', 5, 8);
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
        {
          data: 'imagen',
          render: (data) => {
            const url = data ? `${window.BASE_URL || '/'}${data}` : null;
            return url
              ? `<img src="${url}" class="lote-thumb" data-img="${url}" data-bs-toggle="modal" data-bs-target="#imageLightbox" title="Ver imagen">`
              : `<div class="lote-thumb-placeholder"><i class="fas fa-leaf"></i></div>`;
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
        { data: 'cantidad_actual' },
        { data: 'costo_unitario', render: (data) => data != null ? `$${parseFloat(data).toFixed(2)}` : '<span class="text-muted">—</span>' },
        { data: 'porcentaje_ganancia', render: (data) => data != null ? `${parseFloat(data).toFixed(1)}%` : '<span class="text-muted">—</span>' },
        {
          data: 'estado_nombre',
          render: (data) => Helpers.getBadge(data),
        },
        {
          data: null,
          orderable: false,
          render: () => {
            return C.btnGroup(
              C.btnView('btn-view'),
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
              SkeletonHelper.showTableSkeleton('lotesTable', 5, 8);
            }
            lotesTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  showImagePreview('addBatchImage', 'addBatchPreview');
  showImagePreview('editBatchImage', 'editBatchPreview');

  $(document).on('click', '.lote-thumb', function () {
    const src = $(this).data('img');
    $('#lightboxImg').attr('src', src);
  });

  $('#imageLightbox').on('hidden.bs.modal', function () {
    $('#lightboxImg').attr('src', '');
  });

  $(document).on('click', '.btn-view', function () {
    const row = lotesTable.row($(this).closest('tr')).data();

    const imgUrl = row.imagen ? `${window.BASE_URL || '/'}${row.imagen}` : '';
    if (imgUrl) {
      $('#viewBatchImage').attr('src', imgUrl).show();
    } else {
      $('#viewBatchImage').hide();
    }

    $('#viewBatchPlanta').text(row.planta_nombre || '—');
    $('#viewBatchEspecie').text(row.especie_nombre || '—');
    $('#viewBatchUbicacion').text(row.ubicacion_nombre || '—');
    $('#viewBatchFecha').text(row.fecha_siembra ? Helpers.formatDate(row.fecha_siembra) : '—');
    $('#viewBatchCantInicial').text(row.cantidad_inicial ?? '0');
    $('#viewBatchCantActual').text(row.cantidad_actual ?? '0');
    $('#viewBatchEstado').text(row.estado_nombre || '—');
    $('#viewBatchCategoria').text(row.categoria_nombre || '—');
    $('#viewBatchOrigen').text(row.origen_nombre || '—');
    $('#viewBatchCostoUnitario').text(row.costo_unitario != null ? `$${parseFloat(row.costo_unitario).toFixed(2)}` : '—');
    $('#viewBatchPorcentajeGanancia').text(row.porcentaje_ganancia != null ? `${parseFloat(row.porcentaje_ganancia).toFixed(1)}%` : '—');
    $('#viewBatchObs').text(row.observacion || '—');

    $('#viewBatchModal').modal({ focus: false }).modal('show');
  });

  $('#btnAddBatch').on('click', function () {
    const $editModal = $('#editBatchModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }

    const $qtyInit = $('#addBatchQtyInit');
    const $qtyCurr = $('#addBatchQtyCurr');
    $qtyInit.prop('readonly', false).val('');
    $qtyCurr.prop('readonly', true).val('');
    $qtyInit.off('.addBatch').on('input.addBatch', function () {
      $qtyCurr.val(this.value);
    });

    $('#addBatchEstado').prop('disabled', true);

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
    $('#editBatchQtyInit').val(row.cantidad_inicial).prop('readonly', true);
    $('#editBatchQtyCurr').val(row.cantidad_actual).prop('readonly', false);
    $('#editBatchEstado').val(row.id_estado).prop('disabled', false);
    $('#editBatchCategoria').val(row.id_categoria || '');
    $('#editBatchOrigen').val(row.id_origen);
    $('#editBatchCostoUnitario').val(row.costo_unitario ?? '');
    $('#editBatchPorcentajeGanancia').val(row.porcentaje_ganancia ?? '');
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
    $('#addBatchQtyInit').prop('readonly', false).off('.addBatch');
    $('#addBatchQtyCurr').prop('readonly', false);
    $('#editBatchQtyInit').prop('readonly', false);
    $('#editBatchQtyCurr').prop('readonly', false);
    $('#addBatchEstado, #editBatchEstado').prop('disabled', false);
  });

  initDataTable();

  setupRealTimeValidation($('#addBatchForm'), batchValidationRules);
  setupRealTimeValidation($('#editBatchForm'), batchValidationRules, true);
});
