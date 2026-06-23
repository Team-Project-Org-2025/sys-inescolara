import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';
import * as C from '../utils/components.js';

$(document).ready(function () {
  const trazabilidadRules = {
    id_lote: 'select',
    cantidad: 'cantidad',
    estado_salud: 'select',
    fecha_registro: 'fechaFuturaCheck',
    observacion: null,
  };
  const baseUrl = `${window.BASE_URL || '/'}trazabilidad`;
  let trazabilidadTable = null;

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('trazabilidadTable', 5, 7);
    }
    trazabilidadTable = $('#trazabilidadTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_trazabilidad`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'trazabilidad',
      },
      columns: [
        { data: 'id_lote', render: (data) => `Lote #${data}` },
        { data: 'planta_nombre' },
        { data: 'cantidad' },
        {
          data: 'estado_salud',
          render: (data) => {
            const badges = {
              Sospechoso: 'badge bg-warning text-dark',
              Enfermo: 'badge bg-danger',
              Plaga: 'badge bg-danger',
              'Bajo observación': 'badge bg-secondary',
            };
            const cls = badges[data] || 'badge bg-secondary';
            return `<span class="${cls}">${Helpers.escapeHtml(data)}</span>`;
          },
        },
        { data: 'fecha_registro' },
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
          render: (data) => {
            const perms = window.userPermisos || [];
            const btns = [];

            if (perms.includes('TRAZABILIDAD_EDIT')) {
              btns.push(C.btnEdit('btn-edit'));
            }

            if (perms.includes('TRAZABILIDAD_DELETE')) {
              btns.push(C.btnDelete('btn-delete'));
            }

            return C.btnGroup(...btns);
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
              SkeletonHelper.showTableSkeleton('trazabilidadTable', 5, 7);
            }
            trazabilidadTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  const loadBatches = (selectedId = null) => {
    const data = { action: 'get_batches' };
    if (selectedId) data.include_id = selectedId;
    return $.ajax({
      url: baseUrl,
      method: 'GET',
      data,
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).done((res) => {
      const $select = $('#id_lote');
      $select.find('option:not(:first)').remove();
      if (res.success && res.batches) {
        res.batches.forEach((b) => {
          const label = `Lote #${b.id} - ${Helpers.escapeHtml(b.planta_nombre)} (${b.cantidad_actual} disp.)`;
          $select.append(`<option value="${b.id}" data-stock="${b.cantidad_actual}">${label}</option>`);
        });
        if (selectedId) $select.val(selectedId).trigger('change');
      }
    });
  };

  const updateLoteInfo = () => {
    const $select = $('#id_lote');
    const $option = $select.find('option:selected');
    const stock = $option.data('stock');
    const $info = $('#loteInfo');
    const $cantidad = $('#cantidad');
    if (stock !== undefined) {
      $info.text(`Ejemplares disponibles en este lote: ${stock}`).css('color', stock > 0 ? 'var(--text-secondary)' : '#dc3545');
      $cantidad.attr('max', stock);
    } else {
      $info.text('');
      $cantidad.removeAttr('max');
    }
  };

  $(document).on('change', '#id_lote', updateLoteInfo);

  $(document).on('input change', '#cantidad', function () {
    const $input = $(this);
    const max = parseInt($input.attr('max'), 10);
    const val = parseInt($input.val(), 10);
    if (max && val > max) {
      Helpers.toast('warning', `La cantidad no puede ser mayor a ${max} ejemplares disponibles en el lote.`);
      $input.val(max);
    }
  });

  $('#btnAddTrazabilidad').on('click', function () {
    $('#trazabilidadModalTitle').text('Registrar Cuarentena');
    $('#trazabilidadId').val('0');
    $('#trazabilidadForm')[0].reset();
    const _now = new Date();
    $('#fecha_registro').val(`${_now.getFullYear()}-${String(_now.getMonth() + 1).padStart(2, '0')}-${String(_now.getDate()).padStart(2, '0')}`);
    $('#trazabilidadSubmitBtn').text('Guardar');
    loadBatches().then(() => updateLoteInfo());
    $('#trazabilidadModal').modal({ focus: false }).modal('show');
    setupRealTimeValidation($('#trazabilidadForm'), trazabilidadRules);
  });

  $('#trazabilidadForm').on('submit', function (e) {
    e.preventDefault();
    if (!validateForm($(this), trazabilidadRules)) return;
    const max = parseInt($('#cantidad').attr('max'), 10);
    const val = parseInt($('#cantidad').val(), 10);
    if (max && val > max) {
      Helpers.toast('warning', `La cantidad no puede ser mayor a ${max} ejemplares disponibles.`);
      return;
    }
    const id = $('#trazabilidadId').val();
    const action = id && id !== '0' ? 'edit_ajax' : 'add_ajax';
    const formData = new FormData(this);

    $.ajax({
      url: `${baseUrl}?action=${action}`,
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
          $('#trazabilidadModal').modal('hide');
          trazabilidadTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al guardar registro');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const row = trazabilidadTable.row($(this).closest('tr')).data();
    $('#trazabilidadModalTitle').text('Editar Cuarentena');
    $('#trazabilidadId').val(row.id);
    $('#cantidad').val(row.cantidad);
    const $estado = $('#estado_salud');
    $estado.val(row.estado_salud);
    if ($estado.val() !== row.estado_salud) {
      $estado.append(`<option value="${Helpers.escapeHtml(row.estado_salud)}" selected>${Helpers.escapeHtml(row.estado_salud)}</option>`);
    }
    $('#fecha_registro').val(row.fecha_registro);
    $('#observacion').val(row.observacion);
    $('#trazabilidadSubmitBtn').text('Actualizar');
    loadBatches(row.id_lote).then(() => updateLoteInfo());
    $('#trazabilidadModal').modal({ focus: false }).modal('show');
    setupRealTimeValidation($('#trazabilidadForm'), trazabilidadRules);
  });

  $(document).on('click', '.btn-delete', function () {
    const row = trazabilidadTable.row($(this).closest('tr')).data();
    const id = row.id;
    Helpers.confirmDialog(
      '¿Desactivar cuarentena?',
      '¿Deseas desactivar este registro de cuarentena? El stock se devolverá al lote original.',
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              trazabilidadTable.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          })
          .catch((err) => {
            Helpers.toast('error', err);
          });
      },
      'Sí, desactivar'
    );
  });

  $('#trazabilidadModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
    $('#loteInfo').text('');
  });

  initDataTable();
});
