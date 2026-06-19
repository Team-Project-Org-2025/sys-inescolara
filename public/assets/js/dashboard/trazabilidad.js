import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

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
              Sano: 'badge bg-success',
              Sospechoso: 'badge bg-warning text-dark',
              Enfermo: 'badge bg-danger',
              Plaga: 'badge bg-danger',
              Cuarentena: 'badge bg-info text-dark',
              'Bajo observación': 'badge bg-secondary',
            };
            const cls = badges[data] || 'badge bg-secondary';
            return `<span class="${cls}">${Helpers.escapeHtml(data)}</span>`;
          },
        },
        { data: 'fecha_registro' },
        {
          data: 'observacion',
          render: (data) => data ? Helpers.escapeHtml(data) : '<span class="text-muted">—</span>',
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            const perms = window.userPermisos || [];
            let html = '<div class="d-flex gap-1">';

            if (perms.includes('TRAZABILIDAD_EDIT')) {
              html += `
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-id_lote="${Helpers.escapeHtml(data.id_lote)}"
                        data-cantidad="${Helpers.escapeHtml(data.cantidad)}"
                        data-estado_salud="${Helpers.escapeHtml(data.estado_salud)}"
                        data-fecha_registro="${Helpers.escapeHtml(data.fecha_registro)}"
                        data-observacion="${Helpers.escapeHtml(data.observacion || '')}">
                  <i class="fas fa-edit"></i>
                </button>
              `;
            }

            if (perms.includes('TRAZABILIDAD_DELETE')) {
              html += `
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}">
                  <i class="fas fa-trash"></i>
                </button>
              `;
            }

            html += '</div>';
            return html;
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
    return $.getJSON(`${baseUrl}?action=get_batches`, {
      'X-Requested-With': 'XMLHttpRequest',
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
    if (stock !== undefined) {
      $info.text(`Ejemplares disponibles en este lote: ${stock}`).css('color', stock > 0 ? 'var(--text-secondary)' : '#dc3545');
    } else {
      $info.text('');
    }
  };

  $(document).on('change', '#id_lote', updateLoteInfo);

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
    const $btn = $(this);
    $('#trazabilidadModalTitle').text('Editar Cuarentena');
    $('#trazabilidadId').val($btn.data('id'));
    $('#cantidad').val($btn.data('cantidad'));
    $('#estado_salud').val($btn.data('estado_salud'));
    $('#fecha_registro').val($btn.data('fecha_registro'));
    $('#observacion').val($btn.data('observacion'));
    $('#trazabilidadSubmitBtn').text('Actualizar');
    loadBatches($btn.data('id_lote')).then(() => updateLoteInfo());
    $('#trazabilidadModal').modal({ focus: false }).modal('show');
    setupRealTimeValidation($('#trazabilidadForm'), trazabilidadRules);
  });

  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
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
