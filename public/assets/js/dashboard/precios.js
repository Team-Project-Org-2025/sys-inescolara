import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { validateField, validateSelect } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}precios`;
  let preciosTable = null;
  let calcData = null;

  // ============================================================
  //  CALCULAR POR PLANTA
  // ============================================================

  function loadCalc() {
    const idPlanta = parseInt($('#calcPlanta').val()) || 0;
    const categoria = $('#calcCategoria').val() || '';

    if (!idPlanta) {
      Helpers.toast('error', 'Seleccione una planta.');
      return;
    }

    const $btn = $('#btnCalcularPlanta');
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Calculando...');
    $('#calcResultContainer').addClass('d-none');

    const url = `${baseUrl}?action=calcular_por_planta&id_planta=${idPlanta}${categoria ? '&categoria=' + encodeURIComponent(categoria) : ''}`;
    $.ajax({
      url,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .always(() => {
        $btn.prop('disabled', false).html('<i class="fas fa-calculator"></i> Calcular');
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
          const mo = typeof l.costo_mano_obra !== 'undefined' ? l.costo_mano_obra : 0;
          const ins = typeof l.costo_insumos !== 'undefined' ? l.costo_insumos : 0;
          const agua = typeof l.costo_agua !== 'undefined' ? l.costo_agua : 0;
          const total = parseFloat(mo) + parseFloat(ins) + parseFloat(agua);
          rows += `<tr>
            <td>#${l.id_lote}</td>
            <td>${catLabel}</td>
            <td>${l.cantidad_actual}</td>
            <td class="text-end">${Helpers.formatCurrencyBs(mo)}</td>
            <td class="text-end">${Helpers.formatCurrencyBs(ins)}</td>
            <td class="text-end">${Helpers.formatCurrencyBs(agua)}</td>
            <td class="text-end"><strong>${Helpers.formatCurrencyBs(total)}</strong></td>
          </tr>`;
        });
        $('#calcLotesBody').html(rows);
        $('#calcResultContainer').removeClass('d-none');
      })
      .fail(() => Helpers.toast('error', 'Error al consultar datos.'));
  }

  $('#btnCalcularPlanta').on('click', loadCalc);

  // Auto-calcular al cambiar planta o categoría
  $('#calcPlanta').on('change', function () {
    if ($(this).val()) loadCalc();
  });
  $('#calcCategoria').on('change', function () {
    if ($('#calcPlanta').val()) loadCalc();
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

    if (!validateSelect($('#calcPlanta'))) return;

    const idPlanta = parseInt($('#calcPlanta').val()) || 0;
    const ganancia = parseFloat($('#calcGanancia').val()) || 0;
    const categoria = $('#calcCategoria').val() || '';
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
          if (preciosTable) preciosTable.ajax.reload(null, false);
          $('#calcResultContainer').addClass('d-none');
          calcData = null;
        } else {
          Helpers.toast('error', r.message);
        }
      })
      .catch((err) => Helpers.toast('error', err));
  });

  // ============================================================
  //  ELIMINAR
  // ============================================================

  $(document).on('click', '.btn-delete', function () {
    const row = preciosTable.row($(this).closest('tr')).data();
    const id = row.id;
    const info = `#${row.id_lote} - ${row.planta_nombre || ''}`;

    Helpers.confirmDialog(
      '¿Eliminar cálculo?',
      `¿Deseas eliminar el cálculo de precio <strong>${Helpers.escapeHtml(info)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              if (preciosTable) preciosTable.ajax.reload(null, false);
          } else {
            Helpers.toast('error', response.message);
          }
        })
        .catch((err) => Helpers.toast('error', err));
      },
      'Sí, eliminar'
    );
  });

  // ============================================================
  //  EDITAR (ventana de confirmación)
  // ============================================================

  $(document).on('click', '.btn-edit', function () {
    const row = preciosTable.row($(this).closest('tr')).data();

    Helpers.confirmDialog(
      '¿Editar cálculo?',
      `Se abrirá una ventana para editar el precio del lote <strong>#${Helpers.escapeHtml(row.id_lote)}</strong>.<br>
       <small>Se recomienda recalcular desde la planta para mantener consistencia.</small>`,
      () => {
        const id = row.id;
        const precioActual = row.precio_final_sugerido;
        const gananciaActual = row.porcentaje_ganancia;

        Swal.fire({
          title: 'Editar Precio',
          html: `
            <div class="text-start">
              <label class="form-label">% Ganancia</label>
              <input id="swal-ganancia" class="form-control" type="number" step="0.01" min="0" value="${parseFloat(gananciaActual).toFixed(2)}">
              <label class="form-label mt-2">Precio Sugerido (Bs)</label>
              <input id="swal-precio" class="form-control" type="number" step="0.01" min="0.01" value="${parseFloat(precioActual).toFixed(2)}">
            </div>
          `,
          showCancelButton: true,
          confirmButtonText: 'Guardar',
          cancelButtonText: 'Cancelar',
          preConfirm: () => {
            const ganancia = parseFloat(document.getElementById('swal-ganancia').value) || 0;
            const precio = parseFloat(document.getElementById('swal-precio').value) || 0;
            if (precio <= 0) {
              Swal.showValidationMessage('El precio debe ser mayor a 0');
              return false;
            }
            return { id, precio_final_sugerido: precio, porcentaje_ganancia: ganancia, fecha_calculo: new Date().toISOString().split('T')[0] };
          },
        }).then((result) => {
          if (!result.isConfirmed || !result.value) return;
          const data = result.value;

          Ajax.post(`${baseUrl}?action=edit_ajax`, {
            id: data.id,
            id_lote: row.id_lote,
            costo_total_insumo: parseFloat(row.costo_total_insumo) || 0,
            porcentaje_ganancia: data.porcentaje_ganancia,
            precio_final_sugerido: data.precio_final_sugerido,
            fecha_calculo: data.fecha_calculo,
          })
            .then((response) => {
              if (response.success) {
                Helpers.toast('success', response.message);
                if (preciosTable) preciosTable.ajax.reload(null, false);
              } else {
                Helpers.toast('error', response.message);
              }
            })
            .catch((err) => Helpers.toast('error', err));
        });
      },
      'Sí, continuar'
    );
  });

  function initDataTable() {
    try {
      if ($.fn.DataTable.isDataTable('#preciosTable')) {
        $('#preciosTable').DataTable().destroy();
      }
      preciosTable = $('#preciosTable').DataTable({
        ajax: {
          url: `${baseUrl}?action=get_prices`,
          method: 'GET',
          dataType: 'json',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          dataSrc: 'prices',
        },
        columns: [
          { data: 'id_lote', render: (data) => `#${data}` },
          { data: 'planta_nombre', render: (data) => data || '<span class="text-muted">&mdash;</span>' },
          { data: 'costo_total_insumo', className: 'text-end', render: (data) => Helpers.formatCurrencyBs(data) },
          { data: 'porcentaje_ganancia', render: (data) => `${parseFloat(data).toFixed(1)}%` },
          { data: 'precio_final_sugerido', className: 'text-center', render: (data) => `<strong>${Helpers.formatCurrencyBs(data)}</strong>` },
          { data: 'fecha_calculo', className: 'text-center', render: (data) => data || '<span class="text-muted">&mdash;</span>' },
          { data: null, orderable: false, render: () => {
              return `
                <div class="d-flex gap-1">
                  <button class="btn btn-sm btn-outline-primary btn-edit">
                      <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger btn-delete">
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
        dom: 'lfrtip',
      });
    } catch (e) {
      console.error('Error al inicializar DataTable:', e);
    }
  }

  initDataTable();
});
