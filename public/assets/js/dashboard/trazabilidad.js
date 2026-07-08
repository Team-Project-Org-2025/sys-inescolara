import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';
import * as C from '../utils/components.js';

$(document).ready(function () {
    const trazabilidadRules = {
    id_lote: 'select',
    cantidad: 'cantidad',
    id_estado: 'select',
    fecha_registro: 'fechaFuturaCheck',
    observacion: null,
  };
  const baseUrl = `${window.BASE_URL || '/'}trazabilidad`;
  let trazabilidadTable = null;

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('trazabilidadTable', 5, 5);
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
              vivo: 'badge bg-success',
              cuarentena: 'badge bg-warning text-dark',
              muerto: 'badge bg-danger',
            };
            const cls = badges[data] || 'badge bg-secondary';
            return `<span class="${cls}">${Helpers.escapeHtml(data)}</span>`;
          },
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            const perms = window.userPermisos || [];
            const admin = window.isAdmin === true;
            const hasEdit = admin || perms.includes('trazabilidad:editar');
            const hasDelete = admin || perms.includes('trazabilidad:eliminar');
            const btns = [];

            btns.push(C.btnView('btn-view'));

            if (hasEdit) {
              btns.push(C.btnEdit('btn-edit'));
            }

            if (hasDelete) {
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
SkeletonHelper.showTableSkeleton('trazabilidadTable', 5, 5);
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

  $(document).on('click', '.btn-view', function () {
    const row = trazabilidadTable.row($(this).closest('tr')).data();
    $('#viewTrazaLote').text(`Lote #${row.id_lote}`);
    $('#viewTrazaPlanta').text(row.planta_nombre || '—');
    $('#viewTrazaCantidad').text(row.cantidad || '—');
    $('#viewTrazaEstado').text(row.estado_salud || '—');
    $('#viewTrazaFecha').text(row.fecha_registro || '—');
    $('#viewTrazaObs').text(row.observacion || '—');
    $('#viewTrazabilidadModal').modal({ focus: false }).modal('show');
  });

  $(document).on('click', '.btn-edit', function () {
    const row = trazabilidadTable.row($(this).closest('tr')).data();
    $('#trazabilidadModalTitle').text('Editar Cuarentena');
    $('#trazabilidadId').val(row.id);
    $('#cantidad').val(row.cantidad);
    $('#id_estado').val(row.id_estado);
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

  // ---- Editar Estado (mini modal) ----
  $(document).on('click', '.btn-edit-estado', function () {
    const row = trazabilidadTable.row($(this).closest('tr')).data();
    $('#editEstadoId').val(row.id);
    $('#editEstadoSelect').val(row.id_estado);
    const isVivo = row.estado_salud === 'vivo';
    const $alert = $('#editEstadoAlert');
    const $msg = $('#editEstadoAlertMsg');
    if (isVivo) {
      $alert.show();
      $msg.text('Si cambia a otro estado, el registro permanecerá en trazabilidad sin devolver stock.');
    } else {
      $alert.hide();
    }
    $('#editEstadoModal').modal({ focus: false }).modal('show');
  });

  $('#editEstadoForm').on('submit', function (e) {
    e.preventDefault();
    const id = $('#editEstadoId').val();
    const idEstado = $('#editEstadoSelect').val();
    if (!idEstado) {
      Helpers.toast('error', 'Debe seleccionar un estado.');
      return;
    }
    $.ajax({
      url: `${baseUrl}?action=update_estado_ajax`,
      method: 'POST',
      data: { id, id_estado: idEstado },
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      dataType: 'json',
    })
      .done((response) => {
        if (response.success) {
          Helpers.toast('success', response.message);
          $('#editEstadoModal').modal('hide');
          trazabilidadTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al cambiar estado');
      });
  });

  // ---- Restaurar (vivo + delete + restore stock) ----
  $(document).on('click', '.btn-restaurar', function () {
    const row = trazabilidadTable.row($(this).closest('tr')).data();
    const id = row.id;
    Helpers.confirmDialog(
      '¿Restaurar ejemplar?',
      'Se marcará como <strong>vivo</strong>, se eliminará de trazabilidad y el stock volverá al lote original.',
      () => {
        $.ajax({
          url: `${baseUrl}?action=restore_ajax`,
          method: 'POST',
          data: { id },
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          dataType: 'json',
        })
          .done((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              trazabilidadTable.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          })
          .fail((err) => {
            Helpers.toast('error', err.responseJSON?.message || 'Error al restaurar');
          });
      },
      'Sí, restaurar'
    );
  });

  initDataTable();
});
