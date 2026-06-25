import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm, clearValidation } from '../utils/validation.js';
import * as C from '../utils/components.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}ampliacion`;
  let ampliacionTable = null;
  let lotesCache = [];
  let plantasCache = [];
  let ubicacionesCache = [];
  let especiesCache = [];

  const ampliacionRules = {
    id_trabajador_gestor: 'select',
    fecha_movimiento: 'fechaFuturaCheck',
  };

  const loadSelectData = () => {
    Ajax.get(`${baseUrl}?action=get_lotes`).then((res) => {
      if (res.success) lotesCache = res.lotes;
    });
    Ajax.get(`${baseUrl}?action=get_plantas`).then((res) => {
      if (res.success) plantasCache = res.plantas;
    });
    Ajax.get(`${baseUrl}?action=get_ubicaciones`).then((res) => {
      if (res.success) ubicacionesCache = res.ubicaciones;
    });
    Ajax.get(`${baseUrl}?action=get_especies`).then((res) => {
      if (res.success) especiesCache = res.especies;
    });
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('ampliacionTable', 5, 6);
    }
    ampliacionTable = $('#ampliacionTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_exchanges`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'ampliaciones',
      },
      columns: [
        { data: 'fecha_movimiento' },
        { data: 'cliente_nombre' },
        {
            data: null,
            render: (r) => r.tipo_cedula_cliente ? `${r.tipo_cedula_cliente}-${r.cedula_cliente}` : '—'
        },
        {
          data: 'total_salida',
          render: (data) => {
            const n = parseInt(data);
            return n > 0 ? `<span class="badge bg-danger">${n} item(s)</span>` : '<span class="text-muted">—</span>';
          },
        },
        {
          data: 'total_entrada',
          render: (data) => {
            const n = parseInt(data);
            return n > 0 ? `<span class="badge bg-success">${n} item(s)</span>` : '<span class="text-muted">—</span>';
          },
        },
        { data: 'gestor_nombre' },
        {
          data: null,
          orderable: false,
          render: () => C.btnGroup(
              C.btnView('btn-view'),
              C.btnDelete('btn-delete')
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
            ampliacionTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  const populateLoteOptions = ($select, selectedId) => {
    $select.find('option:not(:first)').remove();
    lotesCache.forEach((lot) => {
      const label = `${lot.planta_nombre} (Lote #${lot.id_lote}) - ${lot.ubicacion_nombre || '—'} [Stock: ${lot.cantidad_actual}]`;
      $select.append(`<option value="${lot.id_lote}" data-stock="${lot.cantidad_actual}">${Helpers.escapeHtml(label)}</option>`);
    });
    if (selectedId) $select.val(selectedId);
  };

  const populatePlantaOptions = ($select, selectedId) => {
    $select.find('option:not(:first)').remove();
    plantasCache.forEach((p) => {
      const label = p.nombre_comun || p.nombre_tecnico || 'Sin nombre';
      $select.append(`<option value="${p.id}">${Helpers.escapeHtml(label)}</option>`);
    });
    $select.append(`<option value="new">➕ Nueva planta...</option>`);
    if (selectedId) $select.val(selectedId);
  };

  const populateEspecieOptions = ($select, selectedId) => {
    $select.find('option:not(:first)').remove();
    especiesCache.forEach((e) => {
      const label = e.nombre_especie || e.nombre_comun || e.nombre_cientifico || 'Sin nombre';
      $select.append(`<option value="${e.id || e.id_especie}">${Helpers.escapeHtml(label)}</option>`);
    });
    if (selectedId) $select.val(selectedId);
  };

  const populateUbicacionOptions = ($select, selectedId) => {
    $select.find('option:not(:first)').remove();
    ubicacionesCache.forEach((u) => {
      $select.append(`<option value="${u.id}">${Helpers.escapeHtml(u.nombre_ubicacion)}</option>`);
    });
    if (selectedId) $select.val(selectedId);
  };

  const addSalidaRow = (idLote, cantidad) => {
    const $template = $($('#salidaRowTemplate').html());
    const $select = $template.find('.salida-lote');
    populateLoteOptions($select, idLote);
    if (cantidad) $template.find('.salida-cantidad').val(cantidad);

    $select.on('change', function () {
      const $opt = $(this).find('option:selected');
      const stock = $opt.data('stock') || 0;
      $template.find('.salida-stock-display').text(stock);
      $template.find('.salida-cantidad').attr('max', stock);
    });

    if (idLote) $select.trigger('change');
    $('#salidaTableBody').append($template);
  };

  const toggleNuevaPlanta = ($row) => {
    const $select = $row.find('.entrada-planta');
    const $nuevaDiv = $row.find('.entrada-nueva-planta');
    const isNew = $select.val() === 'new';
    $nuevaDiv.toggleClass('d-none', !isNew);
    if (!isNew) {
      $nuevaDiv.find('.entrada-nueva-nombre').val('');
      $nuevaDiv.find('.entrada-nueva-tecnico').val('');
      $nuevaDiv.find('.entrada-nueva-especie').val('');
      $select.prop('required', true);
    } else {
      $select.prop('required', false);
    }
  };

  const addEntradaRow = (idPlanta, idUbicacion, cantidad) => {
    const $template = $($('#entradaRowTemplate').html());
    populatePlantaOptions($template.find('.entrada-planta'), idPlanta);
    populateUbicacionOptions($template.find('.entrada-ubicacion'), idUbicacion);
    populateEspecieOptions($template.find('.entrada-nueva-especie'));
    if (cantidad) $template.find('.entrada-cantidad').val(cantidad);
    $template.find('.entrada-planta').on('change', function () {
      toggleNuevaPlanta($(this).closest('tr'));
    });
    toggleNuevaPlanta($template);
    $('#entradaTableBody').append($template);
  };

  $('#btnAddAmpliacion').on('click', function () {
    $('#ampliacionForm')[0].reset();
    clearValidation($('#ampliacionForm'));
    limpiarClienteAmp(
      document.getElementById('buscarClienteAmp'),
      document.getElementById('idClienteAmp'),
      document.getElementById('clienteSeleccionadoAmp')
    );
    $('#fecha_movimiento').val(new Date().toISOString().split('T')[0]);
    $('#salidaTableBody').empty();
    $('#entradaTableBody').empty();
    $('#ampliacionModal').modal({ focus: false }).modal('show');
  });

  $('#btnAddSalidaRow').on('click', function () {
    addSalidaRow(null, null);
  });

  $('#btnAddEntradaRow').on('click', function () {
    addEntradaRow(null, null, null);
  });

  $(document).on('click', '.btn-remove-row', function () {
    $(this).closest('tr').remove();
  });

  $('#ampliacionForm').on('submit', function (e) {
    e.preventDefault();
    if (!validateForm($(this), ampliacionRules)) return;

    const salidaItems = [];
    const entradaItems = [];

    $('#salidaTableBody tr').each(function () {
      const idLote = $(this).find('.salida-lote').val();
      const cantidad = parseInt($(this).find('.salida-cantidad').val());
      if (idLote && cantidad > 0) {
        salidaItems.push({ id_lote: parseInt(idLote), cantidad });
      }
    });

    $('#entradaTableBody tr').each(function () {
      const idPlanta = $(this).find('.entrada-planta').val();
      const idUbicacion = $(this).find('.entrada-ubicacion').val();
      const cantidad = parseInt($(this).find('.entrada-cantidad').val());
      const nuevaNombre = $(this).find('.entrada-nueva-nombre').val().trim();
      const isNew = !!nuevaNombre;
      const hasId = idPlanta && idPlanta !== 'new';
      if ((isNew || hasId) && idUbicacion && cantidad > 0) {
        const item = {
          id_planta: hasId ? parseInt(idPlanta) : 0,
          id_ubicacion: parseInt(idUbicacion),
          cantidad,
        };
        if (isNew) {
          item.nueva_planta_nombre = nuevaNombre;
          item.nueva_planta_tecnico = $(this).find('.entrada-nueva-tecnico').val().trim();
          item.nueva_planta_id_especie = parseInt($(this).find('.entrada-nueva-especie').val() || 0);
        }
        entradaItems.push(item);
      }
    });

    if (salidaItems.length === 0 && entradaItems.length === 0) {
      Helpers.toast('error', 'Debe agregar al menos un item de salida o entrada.');
      return;
    }

    const formData = new FormData(this);

    const idClienteVal = document.getElementById('idClienteAmp')?.value;
    if (!idClienteVal) {
      formData.delete('id_cliente');
    }
    formData.append('salida_items', JSON.stringify(salidaItems));
    formData.append('entrada_items', JSON.stringify(entradaItems));

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
          Helpers.toast('success', response.message);
          $('#ampliacionModal').modal('hide');
          ampliacionTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al registrar ampliación');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const id = ampliacionTable.row($(this).closest('tr')).data().id;
    Helpers.confirmDialog(
      '¿Eliminar ampliación?',
      '¿Deseas eliminar esta ampliación de especies?',
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Ampliación desactivada correctamente');
              ampliacionTable.ajax.reload(null, false);
            } else {
              Helpers.toast('error', response.message);
            }
          })
          .catch((err) => {
            Helpers.toast('error', err);
          });
      },
      'Sí, eliminar'
    );
  });

  $(document).on('click', '.btn-view', function () {
    const id = ampliacionTable.row($(this).closest('tr')).data().id;
    $('#detalleModal').modal({ focus: false }).modal('show');
    $('#detalleModalBody').html(`
      <div class="text-center py-4">
        <div class="spinner-border text-primary"></div>
        <p class="mt-2">Cargando detalle...</p>
      </div>
    `);

    Ajax.get(`${baseUrl}?action=get_detail`, { id })
      .then((res) => {
        if (res.success && res.ampliacion) {
          renderDetalle(res.ampliacion);
        } else {
          $('#detalleModalBody').html('<div class="alert alert-danger mb-0">Error al cargar detalle.</div>');
        }
      })
      .catch(() => {
        $('#detalleModalBody').html('<div class="alert alert-danger mb-0">Error de conexión.</div>');
      });
  });

  function renderDetalle(item) {
    const detalles = item.detalles || [];
    const salidas = detalles.filter((d) => d.tipo === 'salida');
    const entradas = detalles.filter((d) => d.tipo === 'entrada');

    let html = `
      <div class="mb-3">
        <p><strong>Cliente:</strong> ${Helpers.escapeHtml(item.cliente_nombre)}${item.tipo_cedula_cliente ? ` — ${item.tipo_cedula_cliente}-${item.cedula_cliente}` : ''}</p>
        <p><strong>Fecha:</strong> ${Helpers.escapeHtml(item.fecha_movimiento)}</p>
        <p><strong>Gestor:</strong> ${Helpers.escapeHtml(item.gestor_nombre)}</p>
        <p><strong>Observación:</strong> ${Helpers.escapeHtml(item.observacion || '—')}</p>
      </div>
      <hr>
    `;

    html += `<h6 class="text-danger"><i class="fas fa-arrow-right"></i> Salidas (${salidas.length})</h6>`;
    if (salidas.length > 0) {
      html += '<ul class="list-group mb-2">';
      salidas.forEach((d) => {
        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
          <span>${Helpers.escapeHtml(d.planta_nombre || '—')} (${Helpers.escapeHtml(d.ubicacion_nombre || '—')})</span>
          <span class="badge bg-danger rounded-pill">${d.cantidad} und</span>
        </li>`;
      });
      html += '</ul>';
    } else {
      html += '<p class="text-muted">Sin salidas</p>';
    }

    html += `<h6 class="text-success mt-3"><i class="fas fa-arrow-left"></i> Entradas (${entradas.length})</h6>`;
    if (entradas.length > 0) {
      html += '<ul class="list-group mb-2">';
      entradas.forEach((d) => {
        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
          <span>${Helpers.escapeHtml(d.planta_nombre || '—')} (${Helpers.escapeHtml(d.ubicacion_nombre || '—')})</span>
          <span class="badge bg-success rounded-pill">${d.cantidad} und</span>
        </li>`;
      });
      html += '</ul>';
    } else {
      html += '<p class="text-muted">Sin entradas</p>';
    }

    $('#detalleModalBody').html(html);
  }

  $(document).on('hide.bs.modal', '#ampliacionModal, #detalleModal', function () {
    if (document.activeElement && document.activeElement !== document.body) {
      document.activeElement.blur();
    }
  });

  $('#ampliacionModal, #detalleModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    if ($form.length) {
      Helpers.resetForm($form);
      clearValidation($form);
    }
    if ($(this).is('#ampliacionModal')) {
      limpiarClienteAmp(
        document.getElementById('buscarClienteAmp'),
        document.getElementById('idClienteAmp'),
        document.getElementById('clienteSeleccionadoAmp')
      );
    }
    if (document.activeElement && document.activeElement !== document.body) {
      document.activeElement.blur();
    }
  });

  // ==================== CLIENTE ====================

  function initBuscarClienteAmp() {
    const input = document.getElementById('buscarClienteAmp');
    const resultados = document.getElementById('clienteResultadosAmp');
    const hidden = document.getElementById('idClienteAmp');
    const seleccionado = document.getElementById('clienteSeleccionadoAmp');
    const texto = document.getElementById('clienteSeleccionadoTextoAmp');
    const limpiar = document.getElementById('limpiarClienteAmp');
    if (!input) return;
    let timeout;

    input.addEventListener('input', () => {
      clearTimeout(timeout);
      const q = input.value.trim();
      if (q.length < 2) { resultados.style.display = 'none'; return; }
      timeout = setTimeout(() => buscarClientesAmp(q, input, resultados, hidden, seleccionado, texto), 300);
    });
    input.addEventListener('blur', () => setTimeout(() => resultados.style.display = 'none', 300));
    input.addEventListener('focus', () => { if (resultados.children.length > 0) resultados.style.display = 'block'; });
    if (limpiar) {
      limpiar.addEventListener('click', () => limpiarClienteAmp(input, hidden, seleccionado));
    }
  }

  async function buscarClientesAmp(q, input, resultados, hidden, seleccionado, texto) {
    try {
      const res = await fetch(`${baseUrl}?action=buscar_clientes&q=${encodeURIComponent(q)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      resultados.innerHTML = '';
      resultados.style.display = 'none';
      if (!data.success || !data.clientes?.length) return;

      data.clientes.forEach(cl => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'list-group-item list-group-item-action py-1 d-flex justify-content-between align-items-center';
        const cedula = cl.tipo_cedula_cliente ? `${cl.tipo_cedula_cliente}-${cl.cedula_cliente}` : '';
        item.innerHTML = `<div><strong>${cl.nombre_cliente}</strong> <small class="text-muted">${cedula}</small></div>`;
        item.addEventListener('click', () => {
          seleccionarClienteAmp(cl.id_cliente, cl.nombre_cliente, cedula, input, hidden, seleccionado, texto);
          resultados.style.display = 'none';
        });
        resultados.appendChild(item);
      });
      resultados.style.display = 'block';
    } catch (e) {
      console.error('Error buscando clientes:', e);
    }
  }

  function seleccionarClienteAmp(id, nombre, cedula, input, hidden, seleccionado, texto) {
    hidden.value = id;
    input.value = '';
    input.placeholder = nombre;
    input.classList.add('is-valid');
    texto.textContent = cedula ? `${nombre} — ${cedula}` : nombre;
    seleccionado.classList.remove('d-none');
  }

  function limpiarClienteAmp(input, hidden, seleccionado) {
    hidden.value = '';
    input.value = '';
    input.placeholder = 'Buscar por C.I., nombre o apellido...';
    input.classList.remove('is-valid');
    seleccionado.classList.add('d-none');
  }

  setupRealTimeValidation($('#ampliacionForm'), ampliacionRules);
  loadSelectData();
  initBuscarClienteAmp();
  initDataTable();
});
