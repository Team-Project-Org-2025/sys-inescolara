import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import * as C from '../utils/components.js';

let preciosTable = null;

const baseUrl = `${window.BASE_URL || '/'}precios`;

$(document).ready(function () {

  // ============================================================
  //  DATA TABLE
  // ============================================================

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
          { data: 'planta_nombre', render: (data) => data || '<span class="text-muted">&mdash;</span>' },
          { data: 'cantidad_actual', className: 'text-center', render: (data) => `<strong>${data}</strong>` },
          {
            data: 'costo_unitario',
            className: 'text-end',
            render: (data, type, row) => {
              const val = parseFloat(data) || 0;
              return `<span class="editable-field" data-field="costo_unitario" data-id="${row.id_lote}" title="Click para editar">${Helpers.formatCurrencyBs(val)}</span>`;
            },
          },
          {
            data: 'porcentaje_ganancia',
            className: 'text-center',
            render: (data, type, row) => {
              const val = parseFloat(data) || 0;
              return `<span class="editable-field" data-field="porcentaje_ganancia" data-id="${row.id_lote}" title="Click para editar">${val.toFixed(1)}%</span>`;
            },
          },
          {
            data: 'total_insumos',
            className: 'text-end',
            render: (data) => Helpers.formatCurrencyBs(data),
          },
          {
            data: 'precio_final',
            className: 'text-end',
            render: (data) => `<strong>${Helpers.formatCurrencyBs(data)}</strong>`,
          },
          { data: null, orderable: false, className: 'text-center', render: (row) => C.btnGroup(
              C.btnView('btn-ver-insumos', 'title="Ver Insumos"'),
              C.btnEdit('btn-edit-precio', 'title="Editar Precio"'),
            ),
          },
        ],
        pageLength: 10,
        autoWidth: false,
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
        },
        order: [[0, 'asc']],
        dom: 'lfrtip',
      });
    } catch (e) {
      console.error('Error al inicializar DataTable:', e);
    }
  }

  initDataTable();

  // ============================================================
  //  MODAL EDITAR PRECIO
  // ============================================================

  function recalcularModalPreview() {
    const costo = parseFloat($('#editCostoUnitario').val()) || 0;
    const ganancia = parseFloat($('#editPorcentajeGanancia').val()) || 0;
    const totalInsumos = parseFloat($('#editTotalInsumos').data('raw')) || 0;
    const gananciaMonto = costo * ganancia / 100;
    const precioFinal = costo + totalInsumos + gananciaMonto;
    $('#editGananciaMonto').text(Helpers.formatCurrencyBs(gananciaMonto));
    $('#editPrecioFinal').text(Helpers.formatCurrencyBs(precioFinal));
  }

  function abrirModalEditar(row) {
    $('#editLoteId').val(row.id_lote);
    $('#editLoteLabel').text(`Lote #${row.id_lote} — ${Helpers.escapeHtml(row.planta_nombre || '')}`);
    $('#editCostoUnitario').val(parseFloat(row.costo_unitario).toFixed(2));
    $('#editPorcentajeGanancia').val(parseFloat(row.porcentaje_ganancia).toFixed(1));
    $('#editTotalInsumos').text(Helpers.formatCurrencyBs(row.total_insumos)).data('raw', row.total_insumos);
    recalcularModalPreview();
    $('#editarPrecioModal').modal('show');
  }

  $(document).on('input', '#editCostoUnitario, #editPorcentajeGanancia', recalcularModalPreview);

  $('#btnGuardarPrecio').on('click', function () {
    const idLote = parseInt($('#editLoteId').val()) || 0;
    const costoUnitario = parseFloat($('#editCostoUnitario').val()) || 0;
    const porcentajeGanancia = parseFloat($('#editPorcentajeGanancia').val()) || 0;

    if (!idLote) { Helpers.toast('error', 'ID de lote inválido.'); return; }
    if (costoUnitario <= 0) { Helpers.toast('error', 'El costo unitario debe ser mayor a cero.'); return; }
    if (porcentajeGanancia < 0) { Helpers.toast('error', 'El porcentaje de ganancia no puede ser negativo.'); return; }

    const promises = [];

    promises.push(
      Ajax.post(`${baseUrl}?action=actualizar_precio_ajax`, {
        id_lote: idLote,
        costo_unitario: costoUnitario,
      })
    );

    promises.push(
      Ajax.post(`${baseUrl}?action=actualizar_ganancia_ajax`, {
        id_lote: idLote,
        porcentaje_ganancia: porcentajeGanancia,
      })
    );

    Promise.all(promises)
      .then((results) => {
        const allOk = results.every((r) => r.success);
        if (allOk) {
          Helpers.toast('success', 'Precio actualizado correctamente.');
          $('#editarPrecioModal').modal('hide');
          if (preciosTable) preciosTable.ajax.reload(null, false);
        } else {
          const firstError = results.find((r) => !r.success);
          Helpers.toast('error', firstError ? firstError.message : 'Error al actualizar.');
        }
      })
      .catch((err) => Helpers.toast('error', err));
  });

  // ============================================================
  //  EDICION INLINE
  // ============================================================

  $(document).on('click', '.editable-field', function () {
    const $span = $(this);
    if ($span.find('input').length) return;

    const field = $span.data('field');
    const idLote = $span.data('id');
    const row = preciosTable.row($span.closest('tr')).data();
    const currentVal = parseFloat(row[field]) || 0;

    const isGanancia = field === 'porcentaje_ganancia';
    const displayVal = isGanancia ? currentVal.toFixed(1) : currentVal.toFixed(2);

    $span.html(`<input type="number" class="form-control form-control-sm inline-input" value="${displayVal}" step="${isGanancia ? '0.1' : '0.01'}" min="0"${isGanancia ? ' max="100"' : ''}>`);
    const $input = $span.find('input').focus().select();

    function cancelar() {
      $span.text(isGanancia ? `${currentVal.toFixed(1)}%` : Helpers.formatCurrencyBs(currentVal));
    }

    function guardar() {
      const newVal = parseFloat($input.val());
      if (isNaN(newVal) || newVal < 0) {
        Helpers.toast('error', 'Valor inválido.');
        cancelar();
        return;
      }
      if (isGanancia && newVal > 100) {
        Helpers.toast('error', 'El porcentaje no puede superar 100.');
        cancelar();
        return;
      }
      if (!isGanancia && newVal <= 0) {
        Helpers.toast('error', 'El costo unitario debe ser mayor a cero.');
        cancelar();
        return;
      }

      const action = isGanancia ? 'actualizar_ganancia_ajax' : 'actualizar_precio_ajax';
      const payload = { id_lote: idLote };
      payload[isGanancia ? 'porcentaje_ganancia' : 'costo_unitario'] = newVal;

      Ajax.post(`${baseUrl}?action=${action}`, payload)
        .then((r) => {
          if (r.success) {
            Helpers.toast('success', r.message);
            if (preciosTable) preciosTable.ajax.reload(null, false);
          } else {
            Helpers.toast('error', r.message);
            cancelar();
          }
        })
        .catch((err) => {
          Helpers.toast('error', err);
          cancelar();
        });
    }

    $input.on('blur', guardar);
    $input.on('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); $input.blur(); }
      if (e.key === 'Escape') { cancelar(); }
    });
  });

  // ============================================================
  //  DETALLE DE INSUMOS
  // ============================================================

  $(document).on('click', '.btn-ver-insumos', function () {
    const row = preciosTable.row($(this).closest('tr')).data();
    const idLote = row.id_lote;

    $('#detalleLoteLabel').text(`Lote #${idLote} — ${Helpers.escapeHtml(row.planta_nombre || '')}`);
    $('#detalleInsumosBody').html('<tr><td colspan="6" class="text-center py-3"><div class="spinner-border text-primary"></div></td></tr>');
    $('#detalleTotalInsumos').text('$0.00');
    $('#detalleInsumosFoot').show();
    $('#detalleInsumosModal').modal('show');

    Ajax.get(`${baseUrl}?action=get_detalle&id_lote=${idLote}`)
      .then((r) => {
        const detalles = (r.success && r.detalles) ? r.detalles : [];
        if (detalles.length === 0) {
          $('#detalleInsumosBody').html('<tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No hay insumos registrados para este lote</td></tr>');
          $('#detalleInsumosFoot').hide();
          return;
        }
        let totalGeneral = 0;
        let html = '';
        detalles.forEach((d) => {
          const subtotal = parseFloat(d.subtotal) || 0;
          const costo = parseFloat(d.costo_unitario) || 0;
          totalGeneral += subtotal;
          const fecha = d.fecha_registro ? Helpers.formatDate(d.fecha_registro) : '—';
          html += `<tr>
            <td>${Helpers.escapeHtml(d.nombre_insumo || '')}</td>
            <td class="text-center">${d.cantidad} ${Helpers.escapeHtml(d.simbolo || '')}</td>
            <td class="text-end">${Helpers.formatCurrencyBs(costo)}</td>
            <td class="text-end fw-semibold">${Helpers.formatCurrencyBs(subtotal)}</td>
            <td class="text-center"><span class="badge ${d.origen === 'Tarea' ? 'bg-info' : 'bg-secondary'}">${Helpers.escapeHtml(d.origen)}</span></td>
            <td>${fecha}</td>
          </tr>`;
        });
        $('#detalleInsumosBody').html(html);
        $('#detalleTotalInsumos').text(Helpers.formatCurrencyBs(totalGeneral));
      })
      .catch(() => {
        $('#detalleInsumosBody').html('<tr><td colspan="6" class="text-center text-danger py-3">Error al cargar los datos</td></tr>');
        $('#detalleInsumosFoot').hide();
      });
  });

  // ============================================================
  //  BOTON EDITAR PRECIO (ACCION EN TABLA)
  // ============================================================

  $(document).on('click', '.btn-edit-precio', function () {
    const row = preciosTable.row($(this).closest('tr')).data();
    abrirModalEditar(row);
  });

});
