import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';

$(document).ready(function () {
  const urlBase = `${window.BASE_URL || '/'}CuentasPagar`;
  let tablaCuentas = null;

  // ============================================================
  //  DataTable
  // ============================================================

  function inicializarDataTable() {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('cuentasTable', 5, 9);
    }
    tablaCuentas = $('#cuentasTable').DataTable({
      ajax: {
        url: `${urlBase}?action=obtener_cuentas`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'cuentas',
      },
      columns: [
        { data: 'id_cuenta_pagar', render: (data) => `#${data}` },
        { data: 'proveedor_nombre', render: (data) => data || '<span class="text-muted">&mdash;</span>' },
        {
          data: null,
          render: (d) => `<a href="#" class="link-compra">Compra #${Helpers.escapeHtml(d.id_compra)}</a>`,
        },
        { data: 'monto_total', className: 'text-end', render: (data) => Helpers.formatCurrencyBs(data) },
        {
          data: 'saldo_pendiente',
          className: 'text-end',
          render: (data, type, row) => {
            const cls = parseFloat(data) <= 0 ? 'text-success' : 'text-danger';
            return `<strong class="${cls}">${Helpers.formatCurrencyBs(data)}</strong>`;
          },
        },
        {
          data: 'fecha_vencimiento',
          render: (data) => data || '<span class="text-muted">&mdash;</span>',
        },
        { data: 'pagos_count', className: 'text-center', render: (data) => data || 0 },
        {
          data: 'estado',
          className: 'text-center',
          render: (data) => {
            const badges = { pendiente: 'warning', parcial: 'info', pagada: 'success', vencida: 'danger' };
            return `<span class="badge bg-${badges[data] || 'dark'}">${data}</span>`;
          },
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            const tieneSaldo = parseFloat(data.saldo_pendiente) > 0;
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-info btn-detalle">
                    <i class="fas fa-eye"></i> Ver
                </button>
                ${tieneSaldo ? `
                <button class="btn btn-sm btn-outline-success btn-pagar">
                    <i class="fas fa-money-bill"></i> Pagar
                </button>
                ` : ''}
              </div>
            `;
          },
        },
      ],
      pageLength: 10,
      responsive: true,
      autoWidth: false,
      order: [[0, 'desc']],
      language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
      },
      dom: '<"d-flex justify-content-between align-items-center mb-2"lfB>tip',
      buttons: [
        {
          text: '<i class="fas fa-sync-alt"></i> Actualizar',
          className: 'btn btn-outline-secondary btn-sm',
          action: () => tablaCuentas.ajax.reload(null, false),
        },
      ],
    });
  }

  // ============================================================
  //  Detalle
  // ============================================================

  $(document).on('click', '.btn-detalle', function () {
    const row = tablaCuentas.row($(this).closest('tr')).data();
    const id = row.id_cuenta_pagar;
    $.ajax({
      url: `${urlBase}?action=obtener_detalle&id_cuenta=${id}`,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).done((r) => {
      if (!r.success || !r.cuenta) {
        Helpers.toast('error', 'Error al cargar detalle.');
        return;
      }
      const c = r.cuenta;
      const pagos = r.pagos || [];
      const badges = { pendiente: 'warning', parcial: 'info', pagada: 'success', vencida: 'danger' };

      let html = `
        <div class="row mb-3">
          <div class="col-md-6">
            <strong>Proveedor:</strong> ${Helpers.escapeHtml(c.proveedor_nombre || '')}<br>
            <strong>RIF:</strong> ${Helpers.escapeHtml(c.rif_proveedor || '')}<br>
            <strong>Compra:</strong> <a href="#" class="link-compra" data-id="${Helpers.escapeHtml(c.id_compra)}">#${Helpers.escapeHtml(c.id_compra)}</a><br>
            <strong>Fecha Compra:</strong> ${c.fecha_compra || ''}
          </div>
          <div class="col-md-6 text-md-end">
            <strong>Estado:</strong> <span class="badge bg-${badges[c.estado] || 'dark'}">${c.estado || ''}</span><br>
            <strong>Monto Total:</strong> ${Helpers.formatCurrencyBs(c.monto_total)}<br>
            <strong>Saldo Pendiente:</strong> <span class="${parseFloat(c.saldo_pendiente) <= 0 ? 'text-success' : 'text-danger'}">${Helpers.formatCurrencyBs(c.saldo_pendiente)}</span><br>
            <strong>Vencimiento:</strong> ${c.fecha_vencimiento || 'Sin fecha'}
          </div>
        </div>
        ${c.observacion ? `<div class="mb-3"><strong>Observación:</strong> ${Helpers.escapeHtml(c.observacion)}</div>` : ''}
        <hr>
        <h6 class="fw-bold mb-2">Pagos Realizados</h6>`;

      if (pagos.length === 0) {
        html += '<p class="text-muted">No se han registrado pagos.</p>';
      } else {
        html += `
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Fecha</th>
                  <th class="text-end">Monto</th>
                  <th>Tipo</th>
                  <th>Referencia</th>
                  <th>Estado</th>
                  <th style="width:50px"></th>
                </tr>
              </thead>
              <tbody>`;
        pagos.forEach((p) => {
          const badgePago = { pendiente: 'warning', confirmado: 'success', anulado: 'secondary' };
          html += `<tr>
            <td>${p.id_pago_compra}</td>
            <td>${p.fecha_pago}</td>
            <td class="text-end">${Helpers.formatCurrencyBs(p.monto)}</td>
            <td>${p.tipo_pago || '-'}</td>
            <td>${p.referencia || '-'}</td>
            <td><span class="badge bg-${badgePago[p.estado] || 'dark'}">${p.estado}</span></td>
            <td class="text-center">
              ${p.estado === 'confirmado' ? `
              <button class="btn btn-sm btn-outline-danger btn-anular-pago"
                      data-id="${Helpers.escapeHtml(p.id_pago_compra)}"
                      data-monto="${Helpers.escapeHtml(p.monto)}"
                      title="Anular pago">
                  <i class="fas fa-ban"></i>
              </button>` : ''}
            </td>
          </tr>`;
        });
        html += `</tbody></table></div>`;
      }

      html += `
        <div class="text-end mt-3">
          ${parseFloat(c.saldo_pendiente) > 0 ? `
          <button class="btn btn-success" id="btnPagarDesdeDetalle"
                  data-id="${Helpers.escapeHtml(c.id_cuenta_pagar)}"
                  data-saldo="${Helpers.escapeHtml(c.saldo_pendiente)}"
                  data-proveedor="${Helpers.escapeHtml(c.proveedor_nombre || '')}">
              <i class="fas fa-money-bill"></i> Registrar Pago
          </button>` : ''}
        </div>`;

      $('#detalleBody').html(html);
      $('#detalleModal').modal('show');
    }).fail(() => Helpers.toast('error', 'Error al cargar detalle.'));
  });

  // ============================================================
  //  Abrir modal de pago
  // ============================================================

  $(document).on('click', '.btn-pagar', function () {
    const row = tablaCuentas.row($(this).closest('tr')).data();
    abrirModalPago(row.id_cuenta_pagar, row.saldo_pendiente);
  });

  $(document).on('click', '#btnPagarDesdeDetalle', function () {
    $('#detalleModal').modal('hide');
    setTimeout(() => {
      abrirModalPago($(this).data('id'), $(this).data('saldo'));
    }, 300);
  });

  function abrirModalPago(idCuenta, saldo) {
    $('#pagoIdCuenta').val(idCuenta);
    $('#pagoMonto').val('');
    $('#pagoMonto').attr('max', saldo);
    $('#pagoSaldoInfo').text(Helpers.formatCurrencyBs(saldo));
    $('#pagoFecha').val(new Date().toISOString().split('T')[0]);
    $('#pagoForm')[0].reset();
    $('#pagoIdCuenta').val(idCuenta);
    pagoRefRequired();
    $('#pagoModal').modal('show');
    $('#pagoMonto').focus();
  }

  function pagoRefRequired() {
    const tipo = $('#pagoTipo').val();
    const refInput = $('#pagoReferencia');
    if (tipo === 'Efectivo') {
      refInput.prop('required', false);
      refInput.closest('.col-md-6').find('.ref-required').remove();
    } else {
      refInput.prop('required', true);
      if (!refInput.closest('.col-md-6').find('.ref-required').length) {
        refInput.closest('.col-md-6').find('label').append(' <span class="text-danger ref-required">*</span>');
      }
    }
  }

  $(document).on('change', '#pagoTipo', pagoRefRequired);

  // ============================================================
  //  Registrar pago
  // ============================================================

  $('#pagoForm').on('submit', function (e) {
    e.preventDefault();

    const monto = parseFloat($('#pagoMonto').val()) || 0;
    const saldo = parseFloat($('#pagoMonto').attr('max')) || 0;

    if (monto <= 0) {
      Helpers.toast('error', 'El monto debe ser mayor a cero.');
      return;
    }
    if (monto > saldo) {
      Helpers.toast('error', `El monto (${Helpers.formatCurrencyBs(monto)}) supera el saldo pendiente (${Helpers.formatCurrencyBs(saldo)}).`);
      return;
    }

    const formData = new FormData(this);

    $.ajax({
      url: `${urlBase}?action=registrar_pago`,
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
          $('#pagoModal').modal('hide');
          tablaCuentas.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al registrar el pago');
      });
  });

  // ============================================================
  //  Anular pago
  // ============================================================

  $(document).on('click', '.btn-anular-pago', function () {
    const id = $(this).data('id');
    const monto = $(this).data('monto');
    Helpers.confirmDialog(
      '¿Anular pago?',
      `¿Deseas anular el pago por <strong>${Helpers.formatCurrencyBs(monto)}</strong>? El saldo de la cuenta se recalculará.`,
      () => {
        Ajax.post(`${urlBase}?action=anular_pago`, { id_pago: id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              $('#detalleModal').modal('hide');
              tablaCuentas.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          }).catch((err) => Helpers.toast('error', err));
      },
      'Sí, anular'
    );
  });

  // ============================================================
  //  Limpiar modal al cerrar
  // ============================================================

  $('#pagoModal').on('hidden.bs.modal', function () {
    Helpers.resetForm($(this).find('form'));
  });

  // ============================================================
  //  Inicializar
  // ============================================================

  inicializarDataTable();
});
