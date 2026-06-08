import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}mermas`;
  let mermasTable = null;
  let quarantineCache = [];

  const mermaRules = {
    id_trazabilidad: 'select',
    cantidad: 'cantidad',
    motivo: 'select',
    fecha_merma: 'fechaFormato',
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('mermasTable', 5, 8);
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
          data: 'motivo',
          render: (data) => {
            const badges = {
              plaga: 'badge bg-danger',
              enfermedad: 'badge bg-warning',
              daño_mecanico: 'badge bg-secondary',
              factor_climatico: 'badge bg-info',
              otro: 'badge bg-dark',
            };
            const cls = badges[data] || 'badge bg-secondary';
            const labels = {
              plaga: 'Plaga',
              enfermedad: 'Enfermedad',
              daño_mecanico: 'Daño Mecánico',
              factor_climatico: 'Factor Climático',
              otro: 'Otro',
            };
            return `<span class="${cls}">${labels[data] || data}</span>`;
          },
        },
        {
          data: 'fecha_merma',
          render: (data) => data ? Helpers.formatDate(data) : '—',
        },
        {
          data: 'impacto_economico',
          render: (data) => `<span class="text-danger fw-semibold">${Helpers.formatCurrency(data)}</span>`,
        },
        {
          data: 'usuario_registra',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id_merma)}"
                        data-planta="${Helpers.escapeHtml(data.planta_nombre || '')}">
                    <i class="fas fa-trash"></i> Eliminar
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
              SkeletonHelper.showTableSkeleton('mermasTable', 5, 8);
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

  // Eliminar Merma
  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const planta = $(this).data('planta') || 'este registro';

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
