import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';
import * as C from '../utils/components.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}mermas`;
  let mermasTable = null;
  let quarantineCache = [];

  const mermaRules = {
    id_trazabilidad: 'select',
    cantidad: 'cantidad',
    motivo: 'select',
    fecha_merma: 'fechaFuturaCheck',
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('mermasTable', 5, 4);
    }
    mermasTable = $('#mermasTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_mermas`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'mermas',
      },
      columns: [
        {
          data: null,
          render: (data) =>
            `Cuarentena #${Helpers.escapeHtml(data.id_trazabilidad)}`,
        },
        {
          data: 'planta_nombre',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'cantidad',
          render: (data) => `<strong>${parseInt(data)}</strong>`,
        },
        {
          data: null,
          orderable: false,
          render: (data) => C.btnGroup(
              C.btnView('btn-view'),
              C.btnDelete('btn-delete'),
            ),
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
              SkeletonHelper.showTableSkeleton('mermasTable', 5, 4);
            }
            mermasTable.ajax.reload(null, false);
          },
        },
      ],
      drawCallback: function () {
        updateKpiCards();
      },
    });
  };

  const loadQuarantine = () => {
    $.ajax({
      url: `${baseUrl}?action=get_quarantine`,
      method: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      dataType: 'json',
    })
      .done((response) => {
        if (response.success && response.quarantine) {
          quarantineCache = response.quarantine;
          const $select = $('#mermaQuarantine');
          $select.find('option:not(:first)').remove();
          response.quarantine.forEach((q) => {
            const label = `Cuarentena #${q.id} - ${q.planta_nombre} (${q.cantidad} disp.) - ${q.estado_salud || 'Sin estado'}`;
            $select.append(`<option value="${q.id}" data-stock="${q.cantidad}" data-precio="${q.precio_unitario || 0}">${label}</option>`);
          });
        }
      })
      .fail(() => {
        Helpers.toast('error', 'Error al cargar cuarentenas disponibles');
      });
  };

  const updateImpactPreview = () => {
    const $select = $('#mermaQuarantine');
    const $selected = $select.find('option:selected');
    const stock = parseInt($selected.data('stock') || 0);
    const precio = parseFloat($selected.data('precio') || 0);
    const cantidad = parseInt($('#mermaCantidad').val() || 0);

    if ($selected.val() && cantidad > 0) {
      const impacto = cantidad * precio;
      $('#impactoValue').text(Helpers.formatCurrency(impacto));
      $('#impactoPreview').show();
    } else {
      $('#impactoPreview').hide();
    }

    if ($selected.val()) {
      $('#quarantineStockInfo').text(`Ejemplares en cuarentena: ${stock}.`);
    } else {
      $('#quarantineStockInfo').text('');
    }
  };

  const updateKpiCards = () => {
    if (!mermasTable) return;
    const data = mermasTable.rows({ filter: 'applied' }).data().toArray();

    let totalQuantity = 0;
    let totalImpact = 0;
    let lastDate = null;

    data.forEach((row) => {
      totalQuantity += parseInt(row.cantidad || 0);
      totalImpact += parseFloat(row.impacto_economico || 0);
      if (!lastDate || row.fecha_merma > lastDate) {
        lastDate = row.fecha_merma;
      }
    });

    $('#totalCount').text(data.length);
    $('#totalQuantity').text(totalQuantity);
    $('#totalImpact').text(Helpers.formatCurrency(totalImpact));
    $('#lastDate').text(lastDate ? Helpers.formatDate(lastDate) : '—');
  };

  // Abrir Modal Registrar Merma
  $('#btnAddMerma').on('click', function () {
    loadQuarantine();
    $('#addMermaModal').modal({ focus: false }).modal('show');
  });

  // Actualizar preview de impacto
  $('#mermaQuarantine').on('change', updateImpactPreview);
  $('#mermaCantidad').on('input', updateImpactPreview);

  // Guardar Merma
  $('#addMermaForm').on('submit', function (e) {
    e.preventDefault();
    if (!validateForm($(this), mermaRules)) return;

    const formData = new FormData(this);
    const $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Registrando...');

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
          Helpers.toast('success', 'Merma registrada correctamente');
          $('#addMermaModal').modal('hide');
          mermasTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al registrar merma');
      })
      .always(() => {
        $btn.prop('disabled', false).text('Registrar Merma');
      });
  });

  // Ver Merma
  $(document).on('click', '.btn-view', function () {
    const row = mermasTable.row($(this).closest('tr')).data();
    const d = row;
    const motivoLabels = {
      plaga: 'Plaga', enfermedad: 'Enfermedad',
      daño_mecanico: 'Daño Mecánico', factor_climatico: 'Factor Climático', otro: 'Otro',
    };
    const motivoBadge = {
      plaga: 'danger', enfermedad: 'warning',
      daño_mecanico: 'secondary', factor_climatico: 'info', otro: 'dark',
    };
    $('#viewMermaContent').html(`
      <div class="table-responsive">
        <table class="table table-bordered">
          <tr><th class="w-25">Cuarentena</th><td>#${Helpers.escapeHtml(d.id_trazabilidad)}</td></tr>
          <tr><th>Planta</th><td>${Helpers.escapeHtml(d.planta_nombre || '—')}</td></tr>
          <tr><th>Cantidad</th><td><strong>${parseInt(d.cantidad)}</strong></td></tr>
          <tr><th>Motivo</th><td><span class="badge bg-${motivoBadge[d.motivo] || 'secondary'}">${motivoLabels[d.motivo] || d.motivo}</span></td></tr>
          <tr><th>Fecha Merma</th><td>${Helpers.formatDate(d.fecha_merma)}</td></tr>
          <tr><th>Impacto Económico</th><td class="text-danger fw-semibold">${Helpers.formatCurrency(d.impacto_economico)}</td></tr>
          <tr><th>Descripción</th><td>${Helpers.escapeHtml(d.descripcion) || '<span class="text-muted">—</span>'}</td></tr>
          <tr><th>Estado Salud</th><td>${Helpers.escapeHtml(d.estado_salud) || '<span class="text-muted">—</span>'}</td></tr>
          <tr><th>Fecha Cuarentena</th><td>${d.fecha_cuarentena ? Helpers.formatDate(d.fecha_cuarentena) : '—'}</td></tr>
          <tr><th>Registrado por</th><td>${Helpers.escapeHtml(d.usuario_registra) || '<span class="text-muted">—</span>'}</td></tr>
        </table>
      </div>
    `);
    $('#viewMermaModal').modal({ focus: false }).modal('show');
  });

  // Eliminar Merma
  $(document).on('click', '.btn-delete', function () {
    const row = mermasTable.row($(this).closest('tr')).data();
    const id = row.id_merma;
    const planta = row.planta_nombre || 'este registro';

    Helpers.confirmDialog(
      '¿Desactivar registro?',
      `¿Deseas desactivar el registro de merma de <strong>${Helpers.escapeHtml(planta)}</strong>?<br>
       <small class="text-muted">Nota: La cuarentena no se restaurará automáticamente.</small>`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Registro de merma desactivado');
              mermasTable.ajax.reload(null, false);
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

  // Limpiar formulario al cerrar modal
  $('#addMermaModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
    $('#impactoPreview').hide();
    $('#quarantineStockInfo').text('');
    quarantineCache = [];
  });

  setupRealTimeValidation($('#addMermaForm'), mermaRules);

  initDataTable();
});
