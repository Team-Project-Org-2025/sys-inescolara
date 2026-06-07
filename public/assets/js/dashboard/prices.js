import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}prices`;
  let pricesTable = null;

  const fetchCostoInsumos = ($form, idLote) => {
    if (!idLote) return;
    $.ajax({
      url: `${baseUrl}?action=get_costo_insumos&id_lote=${idLote}`,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .done((r) => {
        if (r.success) {
          $form.find('[name="costo_total_insumo"]').val(parseFloat(r.costo_total_insumo || 0).toFixed(2));
          calcSugerido($form);
        }
      });
  };

  const calcSugerido = ($form) => {
    const ins = parseFloat($form.find('[name="costo_total_insumo"]').val()) || 0;
    const gan = parseFloat($form.find('[name="porcentaje_ganancia"]').val()) || 0;
    const $select = $form.find('[name="id_lote"]');
    const $option = $select.find('option:selected');
    const base = parseInt($option.data('cantidad')) || 1;

    const sugerido = (ins * (1 + gan / 100)) / base;
    if (sugerido > 0) {
      $form.find('[name="precio_final_sugerido"]').val(sugerido.toFixed(2));
    }
  };

  const priceValidationRules = {
    id_lote: 'select',
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('pricesTable', 5, 9);
    }
    pricesTable = $('#pricesTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_prices`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'prices',
      },
      columnDefs: [
        {
          targets: [0],
          data: 'id_lote',
          render: (data) => `#${data}`,
        },
        {
          targets: [1],
          data: 'planta_nombre',
          render: (data) => data || '<span class="text-muted">&mdash;</span>',
        },
        {
          targets: [2],
          data: 'costo_total_insumo',
          className: 'text-end',
          render: (data) => Helpers.formatCurrencyBs(data),
        },
        {
          targets: [3],
          data: 'porcentaje_ganancia',
          render: (data) => `${parseFloat(data).toFixed(1)}%`,
        },
        {
          targets: [4],
          data: 'precio_final_sugerido',
          className: 'text-center',
          render: (data) => `<strong>${Helpers.formatCurrencyBs(data)}</strong>`,
        },
        {
          targets: [5],
          data: 'vigente',
          className: 'text-center',
          render: (data) => data == 1
            ? '<span class="badge bg-success">Vigente</span>'
            : '<span class="badge bg-secondary">Inactivo</span>',
        },
        {
          targets: [6],
          data: 'fecha_calculo',
          className: 'text-center',
          render: (data) => data || '<span class="text-muted">&mdash;</span>',
        },
        {
          targets: [7],
          data: null,
          orderable: false,
          render: (data) => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-id_lote="${Helpers.escapeHtml(data.id_lote)}"
                        data-costo_total_insumo="${Helpers.escapeHtml(data.costo_total_insumo)}"
                        data-porcentaje_ganancia="${Helpers.escapeHtml(data.porcentaje_ganancia)}"
                        data-precio_final_sugerido="${Helpers.escapeHtml(data.precio_final_sugerido)}"
                        data-fecha_calculo="${Helpers.escapeHtml(data.fecha_calculo || '')}"
                        data-vigente="${Helpers.escapeHtml(data.vigente)}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-info="#${Helpers.escapeHtml(data.id_lote)} - ${Helpers.escapeHtml(data.planta_nombre || '')}">
                    <i class="fas fa-trash"></i>
                </button>
              </div>
            `;
          },
        },
      ],
      pageLength: 10,
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
              SkeletonHelper.showTableSkeleton('pricesTable', 5, 9);
            }
            pricesTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  $('#btnAddPrice').on('click', function () {
    const $editModal = $('#editPriceModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addPriceModal').modal({ focus: false }).modal('show');
  });

  const toggleBatchWarning = ($form, warningId, originalBatchId) => {
    const $select = $form.find('[name="id_lote"]');
    const $option = $select.find('option:selected');
    const selectedId = parseInt($option.val());
    const exists = ($option.data('exists') === 1 || $option.data('exists') === '1');
    const showWarning = exists && selectedId !== originalBatchId;
    $(warningId).toggleClass('d-none', !showWarning);
    $form.find('button[type="submit"]').prop('disabled', showWarning);
  };

  $(document).on('change', '#addPriceForm [name="id_lote"]', function () {
    const idLote = parseInt($(this).val()) || 0;
    fetchCostoInsumos($('#addPriceForm'), idLote);
    toggleBatchWarning($('#addPriceForm'), '#addBatchPriceWarning', 0);
  });

  $(document).on('change', '#editPriceForm [name="id_lote"]', function () {
    const idLote = parseInt($(this).val()) || 0;
    fetchCostoInsumos($('#editPriceForm'), idLote);
    const origId = parseInt($('#editPriceForm').data('original-batch')) || 0;
    toggleBatchWarning($('#editPriceForm'), '#editBatchPriceWarning', origId);
  });

  const addFormFields = '#addPriceForm input[name="costo_total_insumo"], ' +
    '#addPriceForm input[name="porcentaje_ganancia"]';

  $(document).on('input change', addFormFields, function () {
    calcSugerido($('#addPriceForm'));
  });

  $('#addPriceForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), priceValidationRules)) {
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
          Helpers.toast('success', response.message);
          $('#addPriceModal').modal('hide');
          pricesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al guardar el cálculo');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    const $addModal = $('#addPriceModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editPriceForm').data('original-batch', parseInt($btn.data('id_lote')) || 0);
    $('#editPriceId').val($btn.data('id'));
    $('#editIdLote').val($btn.data('id_lote')).trigger('change');
    $('#editCostoInsumo').val($btn.data('costo_total_insumo'));
    $('#editPorcentajeGanancia').val($btn.data('porcentaje_ganancia'));
    $('#editPrecioSugerido').val($btn.data('precio_final_sugerido'));
    $('#editFechaCalculo').val($btn.data('fecha_calculo'));
    $('#editVigente').prop('checked', parseInt($btn.data('vigente')) === 1);

    $('#editPriceModal').modal({ focus: false }).modal('show');
  });

  const editFormFields = '#editPriceForm input[name="costo_total_insumo"], ' +
    '#editPriceForm input[name="porcentaje_ganancia"]';

  $(document).on('input change', editFormFields, function () {
    calcSugerido($('#editPriceForm'));
  });

  $('#editPriceForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), priceValidationRules, true)) {
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
          Helpers.toast('success', response.message);
          $('#editPriceModal').modal('hide');
          pricesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar el cálculo');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const info = $(this).data('info');

    Helpers.confirmDialog(
      '¿Eliminar cálculo?',
      `¿Deseas eliminar el cálculo de precio <strong>${Helpers.escapeHtml(info)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              pricesTable.ajax.reload(null, false);
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

  $('#addPriceModal, #editPriceModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
    $form.find('button[type="submit"]').prop('disabled', false);
    $(this).find('.form-text.text-danger').addClass('d-none');
  });

  // ============================================================
  //  CALCULAR POR PLANTA
  // ============================================================
  let calcData = null;

  $('#btnCalcularPlanta').on('click', function () {
    const idPlanta = parseInt($('#calcPlanta').val()) || 0;
    const categoria = $('#calcCategoria').val() || '';

    if (!idPlanta) {
      Helpers.toast('error', 'Seleccione una planta.');
      return;
    }

    const url = `${baseUrl}?action=calcular_por_planta&id_planta=${idPlanta}${categoria ? '&categoria=' + encodeURIComponent(categoria) : ''}`;
    $.ajax({
      url,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .done((r) => {
        if (!r.success || !r.data) {
          Helpers.toast('error', r.message || 'Error al calcular');
          return;
        }
        calcData = r.data;
        const d = r.data;

        if (d.lotes.length === 0) {
          Helpers.toast('error', 'No hay lotes para esta planta con los filtros seleccionados.');
          $('#calcResultContainer').addClass('d-none');
          return;
        }

        const gan = parseFloat($('#calcGanancia').val()) || 0;
        const precioSugerido = d.costo_por_planta * (1 + gan / 100);

        $('#calcTotalInsumos').text(Helpers.formatCurrencyBs(d.total_costos));
        $('#calcTotalPlantas').text(d.total_plantas);
        $('#calcCostoPlanta').text(Helpers.formatCurrencyBs(d.costo_por_planta));
        $('#calcPrecioSugerido').text(Helpers.formatCurrencyBs(precioSugerido));

        let rows = '';
        d.lotes.forEach((l) => {
          const catLabel = l.categoria ? l.categoria.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '<span class="text-muted">—</span>';
          const costoLote = typeof l.costo_total_lote !== 'undefined' ? l.costo_total_lote : l.costo_insumos;
          rows += `<tr>
            <td>#${l.id_lote}</td>
            <td>${catLabel}</td>
            <td>${l.cantidad_actual}</td>
            <td class="text-end">${Helpers.formatCurrencyBs(costoLote)}</td>
          </tr>`;
        });
        $('#calcLotesBody').html(rows);
        $('#calcResultContainer').removeClass('d-none');
      })
      .fail(() => Helpers.toast('error', 'Error al consultar datos.'));
  });

  $(document).on('input', '#calcGanancia', function () {
    if (!calcData) return;
    const gan = parseFloat($(this).val()) || 0;
    const precioSugerido = calcData.costo_por_planta * (1 + gan / 100);
    $('#calcPrecioSugerido').text(Helpers.formatCurrencyBs(precioSugerido));
  });

  $('#btnGuardarPlanta').on('click', function () {
    if (!calcData || !calcData.lotes.length) {
      Helpers.toast('error', 'Primero calcule los precios.');
      return;
    }

    const idPlanta = parseInt($('#calcPlanta').val()) || 0;
    const ganancia = parseFloat($('#calcGanancia').val()) || 0;
    const categoria = $('#calcCategoria').val() || '';
    const vigente = $('#calcVigente').is(':checked') ? 1 : 0;
    const precioSugerido = calcData.costo_por_planta * (1 + ganancia / 100);

    if (!idPlanta || precioSugerido <= 0) {
      Helpers.toast('error', 'Datos inválidos para guardar.');
      return;
    }

    const data = {
      id_planta: idPlanta,
      porcentaje_ganancia: ganancia,
      categoria: categoria || null,
      precio_final_sugerido: parseFloat(precioSugerido.toFixed(2)),
      fecha_calculo: new Date().toISOString().split('T')[0],
      vigente,
      lote_ids: calcData.lotes.map((l) => l.id_lote),
      lote_costos: calcData.lotes.reduce((acc, l) => {
        acc[l.id_lote] = typeof l.costo_total_lote !== 'undefined' ? l.costo_total_lote : l.costo_insumos;
        return acc;
      }, {}),
    };

    Ajax.post(`${baseUrl}?action=guardar_por_planta`, data)
      .then((r) => {
        if (r.success) {
          Helpers.toast('success', r.message);
          pricesTable.ajax.reload(null, false);
          $('#calcResultContainer').addClass('d-none');
          calcData = null;
        } else {
          Helpers.toast('error', r.message);
        }
      })
      .catch((err) => Helpers.toast('error', err));
  });

  initDataTable();

  setupRealTimeValidation($('#addPriceForm'), priceValidationRules);
  setupRealTimeValidation($('#editPriceForm'), priceValidationRules, true);
});
