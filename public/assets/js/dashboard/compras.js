import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const urlBase = `${window.BASE_URL || '/'}compras`;
  let tablaCompras = null;
  let editandoId = null;

  const reglasCompra = {
    id_proveedor: 'select',
    fecha_compra: 'fechaFuturaCheck',
  };

  const CATEGORIAS = [
    { value: 'germinado', label: 'Germinado' },
    { value: 'en_crecimiento', label: 'En Crecimiento' },
    { value: 'para_cosechar', label: 'Para Cosechar' },
    { value: 'maduro', label: 'Maduro' },
  ];

  function agregarFilaItem(tipo, idItem, nombre, cantidad, costoUnitario, categoriaLote, idUbicacion) {
    const subtotal = (parseFloat(cantidad) || 0) * (parseFloat(costoUnitario) || 0);
    const fila = `
      <tr>
        <td>
          <select class="form-select form-select-sm item-tipo" required>
            <option value="insumo" ${tipo === 'insumo' ? 'selected' : ''}>Insumo</option>
            <option value="herramienta" ${tipo === 'herramienta' ? 'selected' : ''}>Herramienta</option>
            <option value="planta" ${tipo === 'planta' ? 'selected' : ''}>Planta</option>
          </select>
        </td>
        <td>
          <div class="d-flex flex-column gap-1">
            <div class="d-flex gap-1">
              <select class="form-select form-select-sm item-select flex-grow-1" required>
                <option value="">Seleccione...</option>
              </select>
              <button type="button" class="btn btn-success btn-add-item-quick d-none" title="Crear nueva planta" style="line-height:1; flex-shrink: 0;">
                <i class="fas fa-plus"></i>
              </button>
            </div>
            <div class="d-flex gap-1 planta-extras ${tipo !== 'planta' ? 'd-none' : ''}">
              <select class="form-select form-select-sm item-categoria" style="min-width:120px">
                <option value="germinado">Germinado</option>
                <option value="en_crecimiento" ${categoriaLote === 'en_crecimiento' ? 'selected' : ''}>En Crecimiento</option>
                <option value="para_cosechar" ${categoriaLote === 'para_cosechar' ? 'selected' : ''}>Para Cosechar</option>
                <option value="maduro" ${categoriaLote === 'maduro' ? 'selected' : ''}>Maduro</option>
              </select>
              <select class="form-select form-select-sm item-ubicacion" style="min-width:120px">
                <option value="">Ubicación...</option>
                ${(window.UBICACIONES || []).map(u => `<option value="${u.id}" ${idUbicacion == u.id ? 'selected' : ''}>${Helpers.escapeHtml(u.nombre)}</option>`).join('')}
              </select>
            </div>
          </div>
        </td>
        <td>
          <input type="number" class="form-control form-control-sm text-end item-cantidad" step="0.01" min="0.01" value="${cantidad || 1}" required>
        </td>
        <td>
          <input type="number" class="form-control form-control-sm text-end item-costo" step="0.01" min="0" value="${costoUnitario || 0}" required>
        </td>
        <td class="text-end item-subtotal">${Helpers.formatCurrencyBs(subtotal)}</td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item"><i class="fas fa-times"></i></button>
        </td>
      </tr>`;
    $('#itemsBody').append(fila);

    const $fila = $('#itemsBody tr:last');
    $fila.find('.btn-add-item-quick').addClass('d-none');
    if (tipo === 'planta') $fila.find('.btn-add-item-quick').removeClass('d-none');
    alternarCamposPlanta($fila, tipo === 'planta');
    cargarOpcionesItem($fila, tipo);
    if (idItem) $fila.find('.item-select').val(idItem);
    actualizarSubtotalItem($fila);
  }

  function alternarCamposPlanta($fila, esPlanta) {
    if (esPlanta) {
      $fila.find('.planta-extras').removeClass('d-none');
    } else {
      $fila.find('.planta-extras').addClass('d-none');
    }
  }

  function cargarOpcionesItem($fila, tipo, callback) {
    const $select = $fila.find('.item-select');
    $select.empty().append('<option value="">Seleccione...</option>');

    const urls = {
      insumo: `${window.BASE_URL || '/'}insumos?action=get_supplies`,
      herramienta: `${window.BASE_URL || '/'}herramientas?action=get_tools`,
      planta: `${window.BASE_URL || '/'}plantas?action=get_plants`,
    };

    $.ajax({
      url: urls[tipo],
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).done((r) => {
      if (!r.success) return;
      const mapaLista = { insumo: 'insumos', herramienta: 'herramientas', planta: 'plantas' };
      const lista = r[mapaLista[tipo]] || [];
      lista.forEach((item) => {
        const mapaEtiqueta = { insumo: 'nombre_insumo', herramienta: 'nombre_herramienta', planta: 'nombre_comun' };
        const mapaId = { insumo: item.id_insumo || item.id, herramienta: item.id_herramienta || item.id, planta: item.id };
        const etiqueta = item[mapaEtiqueta[tipo]] || '';
        $select.append(`<option value="${Helpers.escapeHtml(mapaId[tipo])}">${Helpers.escapeHtml(etiqueta)}</option>`);
      });
      if (typeof callback === 'function') callback();
    });
  }

  function actualizarSubtotalItem($fila) {
    const cant = parseFloat($fila.find('.item-cantidad').val()) || 0;
    const costo = parseFloat($fila.find('.item-costo').val()) || 0;
    $fila.find('.item-subtotal').text(Helpers.formatCurrencyBs(cant * costo));
    actualizarTotales();
  }

  function actualizarTotales() {
    let total = 0;
    $('#itemsBody tr').each(function () {
      const cant = parseFloat($(this).find('.item-cantidad').val()) || 0;
      const costo = parseFloat($(this).find('.item-costo').val()) || 0;
      total += cant * costo;
    });
    $('#itemsTotal').text(Helpers.formatCurrencyBs(total));
    $('#itemsTotalFinal').text(Helpers.formatCurrencyBs(total));
    $('#frmTotal').val(total.toFixed(2));
  }

  function obtenerDatosItems() {
    const items = [];
    let valido = true;
    $('#itemsBody tr').each(function () {
      const tipo = $(this).find('.item-tipo').val();
      const idItem = parseInt($(this).find('.item-select').val()) || 0;
      const cantidad = parseFloat($(this).find('.item-cantidad').val()) || 0;
      const costo = parseFloat($(this).find('.item-costo').val()) || 0;
      if (!tipo || !idItem || cantidad <= 0) {
        valido = false;
        return;
      }
      const item = { tipo_item: tipo, id_item: idItem, cantidad, costo_unitario: costo };
      if (tipo === 'planta') {
        item.categoria_lote = $(this).find('.item-categoria').val() || 'germinado';
        const ubi = $(this).find('.item-ubicacion').val();
        if (ubi) item.id_ubicacion = parseInt(ubi);
      }
      items.push(item);
    });
    return valido ? items : null;
  }

  // ============================================================
  //  Eventos de items
  // ============================================================

  $(document).on('change', '.item-tipo', function () {
    const $fila = $(this).closest('tr');
    const tipo = $(this).val();
    cargarOpcionesItem($fila, tipo);
    $fila.find('.btn-add-item-quick').addClass('d-none');
    if (tipo === 'planta') $fila.find('.btn-add-item-quick').removeClass('d-none');
    alternarCamposPlanta($fila, tipo === 'planta');
  });

  $(document).on('input', '.item-cantidad, .item-costo', function () {
    actualizarSubtotalItem($(this).closest('tr'));
  });

  $(document).on('click', '.btn-remove-item', function () {
    $(this).closest('tr').remove();
    actualizarTotales();
  });

  $(document).on('click', '.btn-add-item-quick', function () {
    const $fila = $(this).closest('tr');
    const $select = $fila.find('.item-select');
    const $btn = $(this);

    $select.hide();
    $btn.hide();
    const $inputEnLinea = $(`<input type="text" class="form-control form-control-sm flex-grow-1 inline-plant-input" placeholder="Nombre común..." autofocus>`);
    const $btnGuardar = $(`<button type="button" class="btn btn-sm btn-success flex-shrink-0 inline-plant-save" title="Guardar"><i class="fas fa-check"></i></button>`);
    const $btnCancelar = $(`<button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0 inline-plant-cancel" title="Cancelar"><i class="fas fa-times"></i></button>`);
    $btn.parent().append($inputEnLinea, $btnGuardar, $btnCancelar);
    $inputEnLinea.focus();

    function revertirEnLinea() {
      $inputEnLinea.remove();
      $btnGuardar.remove();
      $btnCancelar.remove();
      $select.show();
      $btn.show();
    }

    function crearPlanta() {
      const nombre = $inputEnLinea.val().trim();
      if (!nombre) {
        $inputEnLinea.focus();
        return;
      }
      $btnGuardar.prop('disabled', true);
      $inputEnLinea.prop('disabled', true);
      $.ajax({
        url: `${urlBase}?action=agregar_planta_rapido`,
        method: 'POST',
        data: { nombre_comun: nombre },
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataType: 'json',
      }).done((r) => {
        if (r.success && r.planta) {
          Helpers.toast('success', `"${Helpers.escapeHtml(nombre)}" creada`);
          revertirEnLinea();
          cargarOpcionesItem($fila, 'planta', () => {
            $select.val(String(r.planta.id)).trigger('change');
          });
        } else {
          Helpers.toast('error', r.message || 'Error al crear la planta');
          $btnGuardar.prop('disabled', false);
          $inputEnLinea.prop('disabled', false).focus();
        }
      }).fail(() => {
        Helpers.toast('error', 'Error de conexión al crear la planta');
        $btnGuardar.prop('disabled', false);
        $inputEnLinea.prop('disabled', false).focus();
      });
    }

    $btnGuardar.on('click', crearPlanta);
    $btnCancelar.on('click', revertirEnLinea);
    $inputEnLinea.on('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); crearPlanta(); }
      if (e.key === 'Escape') { e.preventDefault(); revertirEnLinea(); }
    });
  });

  $('#btnAddItem').on('click', function () {
    agregarFilaItem('insumo', null, '', 1, 0);
  });

  // ============================================================
  //  DataTable
  // ============================================================

  function inicializarDataTable() {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('comprasTable', 5, 10);
    }
    tablaCompras = $('#comprasTable').DataTable({
      ajax: {
        url: `${urlBase}?action=obtener_compras`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'compras',
      },
      columns: [
        { data: 'id_compra', render: (data) => `#${data}` },
        { data: 'proveedor_nombre', render: (data) => data || '<span class="text-muted">&mdash;</span>' },
        { data: 'fecha_compra' },
        {
          data: null,
          render: (d) => {
            const tp = d.tipo_comprobante || '';
            const nc = d.numero_comprobante || '';
            return nc ? `${tp} ${nc}` : tp || '<span class="text-muted">&mdash;</span>';
          },
        },
        { data: 'subtotal', className: 'text-end', render: (data) => Helpers.formatCurrencyBs(data) },
        { data: 'iva', className: 'text-end', render: (data) => Helpers.formatCurrencyBs(data) },
        { data: 'total', className: 'text-end', render: (data) => `<strong>${Helpers.formatCurrencyBs(data)}</strong>` },
        { data: 'items_count', className: 'text-center', render: (data) => data || 0 },
        {
          data: 'estado',
          className: 'text-center',
          render: (data) => {
            const badges = { pendiente: 'warning', recibida: 'success', cancelada: 'secondary' };
            return `<span class="badge bg-${badges[data] || 'dark'}">${data}</span>`;
          },
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            const esPendiente = data.estado === 'pendiente';
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-info btn-detail">
                    <i class="fas fa-eye"></i>
                </button>
                ${esPendiente ? `
                <button class="btn btn-sm btn-outline-primary btn-edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-success btn-recibir">
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-cancelar">
                    <i class="fas fa-ban"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete">
                    <i class="fas fa-trash"></i>
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
      order: [[2, 'desc']],
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
              SkeletonHelper.showTableSkeleton('comprasTable', 5, 10);
            }
            tablaCompras.ajax.reload(null, false);
          },
        },
      ],
    });
  }

  // ============================================================
  //  Abrir modal de agregar
  // ============================================================

  $('#btnAddCompra').on('click', function () {
    editandoId = null;
    $('#compraId').val('');
    $('#compraModalTitle').text('Nueva Compra');
    $('#compraForm')[0].reset();
    $('#frmFecha').val(new Date().toISOString().split('T')[0]);
    $('#itemsBody').empty();
    agregarFilaItem('insumo', null, '', 1, 0);
    actualizarTotales();
    $('#compraModal').modal('show');
  });

  // ============================================================
  //  Enviar formulario (agregar / editar)
  // ============================================================

  $('#compraForm').on('submit', function (e) {
    e.preventDefault();
    if (!validateForm($(this), reglasCompra)) return;

    const items = obtenerDatosItems();
    if (!items) {
      Helpers.toast('error', 'Debe agregar al menos un item válido.');
      return;
    }

    let subtotal = 0;
    items.forEach((it) => { subtotal += it.cantidad * it.costo_unitario; });
    $('#frmSubtotal').val(subtotal.toFixed(2));

    const formData = new FormData(this);
    formData.set('items', JSON.stringify(items));

    const accion = editandoId ? 'editar_ajax' : 'agregar_ajax';
    if (editandoId) formData.set('id', editandoId);

    $.ajax({
      url: `${urlBase}?action=${accion}`,
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
          $('#compraModal').modal('hide');
          tablaCompras.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al guardar la compra');
      });
  });

  // ============================================================
  //  Editar
  // ============================================================

  $(document).on('click', '.btn-edit', function () {
    const row = tablaCompras.row($(this).closest('tr')).data();
    const id = row.id_compra;
    editandoId = id;
    $('#compraModalTitle').text('Editar Compra');
    $('#compraForm')[0].reset();
    $('#itemsBody').empty();

    $.ajax({
      url: `${urlBase}?action=obtener_detalles&id_compra=${id}`,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).done((r) => {
      if (!r.success || !r.compra) {
        Helpers.toast('error', 'No se pudo cargar la compra.');
        return;
      }
      const c = r.compra;
      $('#compraId').val(c.id_compra);
      $('#frmProveedor').val(c.id_proveedor);
      $('#frmFecha').val(c.fecha_compra);
      $('#frmTipoComprobante').val(c.tipo_comprobante || 'Factura');
      $('#frmNumComprobante').val(c.numero_comprobante || '');
      $('#frmObservacion').val(c.observacion || '');

      if (r.details && r.details.length) {
        r.details.forEach((d) => {
          agregarFilaItem(d.tipo_item, d.id_item, d.item_nombre, d.cantidad, d.costo_unitario, d.categoria_lote, d.id_ubicacion);
        });
      } else {
        agregarFilaItem('insumo', null, '', 1, 0);
      }
      actualizarTotales();
      $('#compraModal').modal('show');
    }).fail(() => Helpers.toast('error', 'Error al cargar datos de la compra.'));
  });

  // ============================================================
  //  Detalle
  // ============================================================

  $(document).on('click', '.btn-detail', function () {
    const row = tablaCompras.row($(this).closest('tr')).data();
    const id = row.id_compra;
    $.ajax({
      url: `${urlBase}?action=obtener_detalles&id_compra=${id}`,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).done((r) => {
      if (!r.success) {
        Helpers.toast('error', 'Error al cargar detalle.');
        return;
      }
      const c = r.compra || {};
      const details = r.details || [];
      let html = `
        <div class="row mb-3">
          <div class="col-md-6">
            <strong>Proveedor:</strong> ${Helpers.escapeHtml(c.proveedor_nombre || '')}<br>
            <strong>RIF:</strong> ${Helpers.escapeHtml(c.rif_proveedor || '')}<br>
            <strong>Fecha:</strong> ${c.fecha_compra || ''}
          </div>
          <div class="col-md-6 text-md-end">
            <strong>Comprobante:</strong> ${Helpers.escapeHtml(c.tipo_comprobante || '')} ${Helpers.escapeHtml(c.numero_comprobante || '')}<br>
            <strong>Estado:</strong> <span class="badge bg-${c.estado === 'recibida' ? 'success' : c.estado === 'cancelada' ? 'secondary' : 'warning'}">${c.estado || ''}</span><br>
            <strong>Total:</strong> ${Helpers.formatCurrencyBs(c.total)}
          </div>
        </div>
        ${c.observacion ? `<div class="mb-3"><strong>Observación:</strong> ${Helpers.escapeHtml(c.observacion)}</div>` : ''}
        <hr>
        <div class="table-responsive">
          <table class="table table-sm table-bordered">
            <thead class="table-light">
              <tr>
                <th style="width:80px">Tipo</th>
                <th>Item</th>
                <th class="text-end" style="width:90px">Cantidad</th>
                <th class="text-end" style="width:100px">Costo Unit.</th>
                <th class="text-end" style="width:100px">Subtotal</th>
              </tr>
            </thead>
            <tbody>`;
      let subtotalItems = 0;
      details.forEach((d) => {
        subtotalItems += parseFloat(d.subtotal) || 0;
        let extra = '';
        if (d.tipo_item === 'planta') {
          const cat = d.categoria_lote || 'germinado';
          const ubi = d.id_ubicacion ? ` (Ubic. #${d.id_ubicacion})` : '';
          extra = `<br><small class="text-muted">${cat}${ubi}</small>`;
        }
        html += `<tr>
          <td><span class="badge bg-${d.tipo_item === 'insumo' ? 'primary' : 'secondary'}">${d.tipo_item}</span></td>
          <td>${Helpers.escapeHtml(d.item_nombre || '')}${extra}</td>
          <td class="text-end">${parseFloat(d.cantidad).toFixed(2)}</td>
          <td class="text-end">${Helpers.formatCurrencyBs(d.costo_unitario)}</td>
          <td class="text-end">${Helpers.formatCurrencyBs(d.subtotal)}</td>
        </tr>`;
      });
      html += `</tbody>
            <tfoot>
              <tr class="fw-bold"><td colspan="4" class="text-end">Total:</td><td class="text-end">${Helpers.formatCurrencyBs(c.total)}</td></tr>
            </tfoot>
          </table>
        </div>`;
      $('#detailBody').html(html);
      $('#detailModal').modal('show');
    }).fail(() => Helpers.toast('error', 'Error al cargar detalle.'));
  });

  // ============================================================
  //  Recibir
  // ============================================================

  $(document).on('click', '.btn-recibir', function () {
    const row = tablaCompras.row($(this).closest('tr')).data();
    const id = row.id_compra;
    const info = `#${row.id_compra} - ${row.proveedor_nombre || ''}`;
    Helpers.confirmDialog(
      '¿Recibir compra?',
      `Al recibir la compra <strong>${Helpers.escapeHtml(info)}</strong> se actualizará el stock y se crearán lotes para las plantas con los datos de categoría y ubicación configurados en cada item. ¿Desea continuar?`,
      () => {
        Ajax.post(`${urlBase}?action=recibir_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              tablaCompras.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          }).catch((err) => Helpers.toast('error', err));
      },
      'Sí, recibir'
    );
  });

  // ============================================================
  //  Cancelar
  // ============================================================

  $(document).on('click', '.btn-cancelar', function () {
    const row = tablaCompras.row($(this).closest('tr')).data();
    const id = row.id_compra;
    const info = `#${row.id_compra} - ${row.proveedor_nombre || ''}`;
    Helpers.confirmDialog(
      '¿Cancelar compra?',
      `¿Deseas cancelar la compra <strong>${Helpers.escapeHtml(info)}</strong>?`,
      () => {
        Ajax.post(`${urlBase}?action=cancelar_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              tablaCompras.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          }).catch((err) => Helpers.toast('error', err));
      },
      'Sí, cancelar'
    );
  });

  // ============================================================
  //  Eliminar
  // ============================================================

  $(document).on('click', '.btn-delete', function () {
    const row = tablaCompras.row($(this).closest('tr')).data();
    const id = row.id_compra;
    const info = `#${row.id_compra} - ${row.proveedor_nombre || ''}`;
    Helpers.confirmDialog(
      '¿Eliminar compra?',
      `¿Deseas eliminar la compra <strong>${Helpers.escapeHtml(info)}</strong>?`,
      () => {
        Ajax.post(`${urlBase}?action=eliminar_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', response.message);
              tablaCompras.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          }).catch((err) => Helpers.toast('error', err));
      },
      'Sí, eliminar'
    );
  });

  // ============================================================
  //  Limpiar modal al cerrar
  // ============================================================

  $('#compraModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  // ============================================================
  //  Inicializar
  // ============================================================

  setupRealTimeValidation($('#compraForm'), reglasCompra);
  inicializarDataTable();
});
