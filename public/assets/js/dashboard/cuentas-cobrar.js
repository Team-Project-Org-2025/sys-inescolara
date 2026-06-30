import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';
import * as C from '../utils/components.js';

$(document).ready(function () {
  const urlBase = `${window.BASE_URL || '/'}cuentas_cobrar`;
  let tablaCuentas = null;

  const reglasPago = {
    monto: 'precio',
    metodo: 'select',
    fecha_pago: 'fechaPagoCheck'
  };

  const iniciarTabla = () => {
    tablaCuentas = $('#cuentasTable').DataTable({
      ajax: {
        url: `${urlBase}?action=obtener_lista`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'data',
      },
      columns: [
        {
          data: 'referencia',
          render: (data, type, row) => type === 'display'
            ? `<a href="#" class="ver-detalle"><strong>${data}</strong></a>`
            : data
        },
        { data: 'nombre_cliente' },
        {
            data: null,
            render: (r) => r.tipo_cedula_cliente ? `${r.tipo_cedula_cliente}-${r.cedula_cliente}` : '—'
        },
        {
          data: 'fecha_venta',
          render: (data) => data ? data.split(' ')[0] : '—'
        },
        {
          data: 'monto_total',
          render: (data) => `Bs ${Number(data).toFixed(2)}`
        },
        {
          data: 'estado_cuenta',
          render: (data) => {
            const map = { vigente: 'badge-vigente', vencido: 'badge-vencido', pagado: 'badge-pagado' };
            const labels = { vigente: 'Vigente', vencido: 'Vencido', pagado: 'Pagado' };
            return `<span class="badge badge-estado ${map[data] || 'bg-secondary'}">${labels[data] || data}</span>`;
          }
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            let html = C.btnView('ver-detalle', 'title="Ver detalle"');
            if (data.estado_cuenta !== 'pagado') {
              html += C.btnPay('btn-pagar', 'title="Registrar pago"');
            }
            return html;
          }
        }
      ],
      pageLength: 15,
      responsive: true,
      autoWidth: false,
      order: [[4, 'asc']],
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
              SkeletonHelper.showTableSkeleton('cuentasTable', 5, 10);
            }
            tablaCuentas.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  const cargarDetalle = (id) => {
    $('#detailModalBody').html(`
      <div class="text-center py-4">
        <div class="spinner-border" role="status"></div>
        <p class="mt-2 text-muted">Cargando detalle...</p>
      </div>
    `);
    $('#detailModal').modal('show');

    $.ajax({
      url: `${urlBase}?action=obtener_detalle&id=${id}`,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .done((response) => {
        if (response.success) {
          renderizarDetalle(response.data);
        } else {
          $('#detailModalBody').html(`<div class="alert alert-danger">${response.message}</div>`);
        }
      })
      .fail(() => {
        $('#detailModalBody').html('<div class="alert alert-danger">Error al cargar detalle</div>');
      });
  };

  const renderizarDetalle = (data) => {
    const hoy = new Date().toISOString().split('T')[0];
    const claseEstado = data.saldo_pendiente <= 0 ? 'badge-pagado' :
      (data.fecha_vencimiento && data.fecha_vencimiento < hoy ? 'badge-vencido' : 'badge-vigente');
    const etiquetaEstado = data.saldo_pendiente <= 0 ? 'Pagado' :
      (data.fecha_vencimiento && data.fecha_vencimiento < hoy ? 'Vencido' : 'Vigente');

    let html = `
      <div class="row mb-3">
        <div class="col-md-6">
          <table class="table table-sm table-borderless">
            <tr><td class="text-muted">Referencia:</td><td><strong>${data.referencia}</strong></td></tr>
            <tr><td class="text-muted">Cliente:</td><td><strong>${data.nombre_cliente}</strong> ${data.tipo_cedula_cliente ? `— ${data.tipo_cedula_cliente}-${data.cedula_cliente}` : ''}</td></tr>
            <tr><td class="text-muted">Contacto:</td><td>${data.contacto || '—'}</td></tr>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-sm table-borderless">
            <tr><td class="text-muted">Fecha Venta:</td><td>${data.fecha_venta}</td></tr>
            <tr><td class="text-muted">Vencimiento:</td><td>${data.fecha_vencimiento || '—'}</td></tr>
            <tr><td class="text-muted">Vendedor:</td><td>${data.trabajador}</td></tr>
            <tr><td class="text-muted">Monto Total:</td><td><strong>Bs ${Number(data.monto_total).toFixed(2)}</strong></td></tr>
            <tr><td class="text-muted">Total Pagado:</td><td><strong class="text-success">Bs ${Number(data.total_pagado).toFixed(2)}</strong></td></tr>
            <tr><td class="text-muted">Saldo Pendiente:</td><td><strong>Bs ${Number(data.saldo_pendiente).toFixed(2)}</strong></td></tr>
            <tr><td class="text-muted">Estado:</td><td><span class="badge badge-estado ${claseEstado}">${etiquetaEstado}</span></td></tr>
          </table>
        </div>
      </div>
    `;

    html += '<h6 class="fw-bold mb-2">Productos</h6>';
    html += '<table class="table table-sm table-bordered mb-4"><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr></thead><tbody>';
    if (data.detalles && data.detalles.length > 0) {
      data.detalles.forEach(d => {
        const sub = Number(d.cantidad) * Number(d.precio_unitario);
        html += `<tr><td>${d.producto}</td><td>${d.cantidad}</td><td>Bs ${Number(d.precio_unitario).toFixed(2)}</td><td>Bs ${sub.toFixed(2)}</td></tr>`;
      });
    }
    html += '</tbody></table>';

    html += '<h6 class="fw-bold mb-2">Historial de Pagos</h6>';
    html += '<table class="table table-sm table-bordered"><thead><tr><th>Fecha</th><th>Metodo</th><th>Monto</th><th>Referencia</th><th>Banco</th><th>Estado</th><th>Cobrador</th></tr></thead><tbody>';
    if (data.pagos && data.pagos.length > 0) {
      data.pagos.forEach(p => {
        const badges = { registrado: 'bg-warning text-dark', confirmado: 'bg-success', rechazado: 'bg-danger' };
        html += `<tr>
          <td>${p.fecha_pago}</td>
          <td>${p.metodo}</td>
          <td>Bs ${Number(p.monto).toFixed(2)}</td>
          <td>${p.referencia || '—'}</td>
          <td>${p.banco || '—'}</td>
          <td><span class="badge ${badges[p.estado_pago] || 'bg-secondary'}">${p.estado_pago}</span></td>
          <td>${p.cobrador || '—'}</td>
        </tr>`;
      });
    } else {
      html += '<tr><td colspan="7" class="text-center text-muted">No hay pagos registrados</td></tr>';
    }
    html += '</tbody></table>';

    if (data.observaciones) {
      html += `<div class="alert alert-secondary mb-0"><small><strong>Observaciones:</strong> ${data.observaciones}</small></div>`;
    }

    $('#detailModalBody').html(html);
  };

  const abrirModalPago = (id, cliente, saldo, referencia, fechaVenta) => {
    $('#payIdVenta').val(id);
    $('#payInfo').html(`<strong>${referencia}</strong> — ${cliente} — Saldo pendiente: <strong>Bs ${Number(saldo).toFixed(2)}</strong>`);
    const $fechaInput = $('#paymentForm').find('[name="fecha_pago"]');
    if (fechaVenta) {
      $fechaInput.data('fecha-venta', fechaVenta.split(' ')[0]);
    }
    $('#paymentForm')[0].reset();
    $('#payReferenceGroup').hide();
    $('#payMetodo').val('');
    $('#paymentModal').modal('show');
  };

  $('#payMetodo').on('change', function () {
    const val = $(this).val();
    if (val === 'transferencia' || val === 'pago_movil') {
      $('#payReferenceGroup').slideDown(200);
    } else {
      $('#payReferenceGroup').slideUp(200);
    }
  });

  $(document).on('click', '.ver-detalle', function (e) {
    e.preventDefault();
    const row = tablaCuentas.row($(this).closest('tr')).data();
    const id = row.id_venta;
    cargarDetalle(id);
  });

  $(document).on('click', '.btn-pagar', function () {
    const row = tablaCuentas.row($(this).closest('tr')).data();
    const id = row.id_venta;
    const cedula = row.tipo_cedula_cliente ? `${row.tipo_cedula_cliente}-${row.cedula_cliente}` : '';
    const cliente = row.nombre_cliente + (cedula ? ` — ${cedula}` : '');
    const saldo = row.saldo_pendiente;
    const referencia = row.referencia;
    const fechaVenta = row.fecha_venta;
    abrirModalPago(id, cliente, saldo, referencia, fechaVenta);
  });

  $('#paymentForm').on('submit', function (e) {
    e.preventDefault();
    if (!validateForm($(this), reglasPago)) return;
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
          $('#paymentModal').modal('hide');
          tablaCuentas.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al registrar pago');
      });
  });

  $('#paymentModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
    $form.find('[name="fecha_pago"]').removeData('fecha-venta');
    $('#payReferenceGroup').hide();
  });

  if ($('#paymentForm').length) {
    setupRealTimeValidation($('#paymentForm'), reglasPago);
  }

  iniciarTabla();
});
