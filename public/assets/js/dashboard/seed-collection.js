import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}recoleccion`;
  let recoleccionTable = null;

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('recoleccionTable', 5, 8);
    }
    recoleccionTable = $('#recoleccionTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_recolecciones`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'recolecciones',
      },
      columns: [
        { data: 'trabajador_nombre' },
        { data: 'nombre_ubicacion' },
        { data: 'fecha_asignacion' },
        {
          data: 'fecha_recoleccion',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'estatus',
          render: (data) => {
            if (data === 'Pendiente') {
              return '<span class="badge bg-warning text-dark">Pendiente</span>';
            }
            return '<span class="badge bg-success">Realizada</span>';
          },
        },
        {
          data: 'total_detalles',
          render: (data) => {
            const count = parseInt(data);
            if (count > 0) {
              return `<span class="badge bg-info text-dark"><i class="fas fa-seedling"></i> ${count} tipo(s)</span>`;
            }
            return '<span class="text-muted">—</span>';
          },
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            let html = '<div class="d-flex gap-1">';

            if (data.estatus === 'Pendiente') {
              html += `
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-id_trabajador="${Helpers.escapeHtml(data.id_trabajador)}"
                        data-id_ubicacion="${Helpers.escapeHtml(data.id_ubicacion)}"
                        data-fecha_asignacion="${Helpers.escapeHtml(data.fecha_asignacion)}"
                        data-observacion="${Helpers.escapeHtml(data.observacion || '')}">
                  <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-success btn-completar"
                        data-id="${Helpers.escapeHtml(data.id)}">
                  <i class="fas fa-check"></i> Completar
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}">
                  <i class="fas fa-trash"></i> Eliminar
                </button>
              `;
            }

            if (data.estatus === 'Realizada' && (!data.total_detalles || parseInt(data.total_detalles) === 0)) {
              html += `
                <button class="btn btn-sm btn-outline-info btn-registrar-insumo"
                        data-id="${Helpers.escapeHtml(data.id)}">
                  <i class="fas fa-seedling"></i> Registrar Insumo
                </button>
              `;
            }

            if (data.total_detalles && parseInt(data.total_detalles) > 0) {
              html += `<span class="text-success" style="font-size:0.85rem;"><i class="fas fa-check-circle"></i> ${data.total_detalles} tipo(s)</span>`;
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
              SkeletonHelper.showTableSkeleton('recoleccionTable', 5, 8);
            }
            recoleccionTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  // Abrir Modal Registrar Recolección
  $('#btnAddRecoleccion').on('click', function () {
    $('#recoleccionModalTitle').text('Registrar Recolección');
    $('#recoleccionId').val('0');
    $('#recoleccionForm')[0].reset();
    $('#fecha_asignacion').val(new Date().toISOString().split('T')[0]);
    $('#recoleccionSubmitBtn').text('Guardar');
    $('#recoleccionModal').modal({ focus: false }).modal('show');
  });

  // Guardar o Editar Recolección
  $('#recoleccionForm').on('submit', function (e) {
    e.preventDefault();
    const id = $('#recoleccionId').val();
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
          $('#recoleccionModal').modal('hide');
          recoleccionTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al guardar recolección');
      });
  });

  // Abrir Modal Editar Recolección
  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);
    $('#recoleccionModalTitle').text('Editar Recolección');
    $('#recoleccionId').val($btn.data('id'));
    $('#id_trabajador').val($btn.data('id_trabajador'));
    $('#id_ubicacion').val($btn.data('id_ubicacion'));
    $('#fecha_asignacion').val($btn.data('fecha_asignacion'));
    $('#observacion').val($btn.data('observacion'));
    $('#recoleccionSubmitBtn').text('Actualizar');
    $('#recoleccionModal').modal({ focus: false }).modal('show');
  });

  // Eliminar Recolección
  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    Helpers.confirmDialog(
      '¿Eliminar recolección?',
      '¿Deseas eliminar esta tarea de recolección? Esta acción no se puede deshacer.',
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Recolección eliminada correctamente');
              recoleccionTable.ajax.reload(null, false);
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

  // Abrir Modal Completar Recolección
  $(document).on('click', '.btn-completar', function () {
    const id = $(this).data('id');
    $('#completarId').val(id);
    $('#fecha_recoleccion').val(new Date().toISOString().split('T')[0]);
    $('#completarModal').modal({ focus: false }).modal('show');
  });

  // Completar Recolección
  $('#completarForm').on('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    $.ajax({
      url: `${baseUrl}?action=completar_ajax`,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      dataType: 'json',
    })
      .done((response) => {
        if (response.success) {
          Helpers.toast('success', 'Recolección completada correctamente');
          $('#completarModal').modal('hide');
          recoleccionTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al completar recolección');
      });
  });

  // Quick-add Ubicación
  $('#btnAddUbicacionQuick').on('click', function () {
    $('#ubicacionQuickForm')[0].reset();
    $('#ubicacionQuickModal').modal({ focus: false }).modal('show');
  });

  $('#ubicacionQuickForm').on('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    $.ajax({
      url: `${window.BASE_URL || '/'}locations?action=add_ajax`,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      dataType: 'json',
    })
      .done((response) => {
        if (response.success) {
          Helpers.toast('success', 'Ubicación agregada correctamente');
          $('#ubicacionQuickModal').modal('hide');
          // Recargar ubicaciones en el select
          $.getJSON(`${window.BASE_URL || '/'}locations?action=get_locations`, { 'X-Requested-With': 'XMLHttpRequest' })
            .done((res) => {
              if (res.success) {
                const $select = $('#id_ubicacion');
                $select.find('option:not(:first)').remove();
                res.locations.forEach((loc) => {
                  $select.append(`<option value="${loc.id}">${Helpers.escapeHtml(loc.nombre_ubicacion)}</option>`);
                });
                $select.val(response.id || '');
              }
            });
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al crear ubicación');
      });
  });

  // --- Múltiples insumos por recolección ---
  const addInsumoRow = (planta, nombre, unidad, cantidad) => {
    const $template = $($('#insumoRowTemplate').html());
    if (planta) $template.find('.insumo-planta').val(planta);
    if (nombre) $template.find('.insumo-nombre').val(nombre);
    if (unidad) $template.find('.insumo-unidad').val(unidad);
    if (cantidad) $template.find('.insumo-cantidad').val(cantidad);
    $('#insumosTableBody').append($template);
  };

  // Abrir Modal Registrar Insumo
  $(document).on('click', '.btn-registrar-insumo', function () {
    const id = $(this).data('id');
    $('#insumoRecoleccionId').val(id);
    $('#insumosTableBody').empty();
    addInsumoRow('', '', '', '');
    $('#insumoModal').modal({ focus: false }).modal('show');
  });

  // Agregar fila
  $('#btnAddInsumoRow').on('click', function () {
    addInsumoRow('', '', '', '');
  });

  // Remover fila
  $(document).on('click', '.btn-remove-insumo-row', function () {
    const $tbody = $('#insumosTableBody');
    if ($tbody.find('tr').length > 1) {
      $(this).closest('tr').remove();
    } else {
      Helpers.toast('warning', 'Debe haber al menos un tipo de semilla.');
    }
  });

  // Auto-completar nombre al seleccionar planta
  $(document).on('change', '.insumo-planta', function () {
    const $row = $(this).closest('tr');
    const planta = $(this).val();
    const $nombreInput = $row.find('.insumo-nombre');
    if (planta && !$nombreInput.val()) {
      $nombreInput.val(`Semillas de ${planta}`);
    }
  });

  // Registrar todos los insumos
  $('#insumoForm').on('submit', function (e) {
    e.preventDefault();
    const id = $('#insumoRecoleccionId').val();
    const items = [];
    let valid = true;

    $('#insumosTableBody tr').each(function () {
      const $row = $(this);
      const plantaOrigen = $row.find('.insumo-planta').val() || '';
      const nombreSemilla = $row.find('.insumo-nombre').val().trim();
      const idUnidadMedida = $row.find('.insumo-unidad').val();
      const cantidad = parseFloat($row.find('.insumo-cantidad').val());

      if (!nombreSemilla || !idUnidadMedida || !cantidad || cantidad <= 0) {
        valid = false;
        $row.addClass('table-danger');
        return;
      }
      $row.removeClass('table-danger');
      items.push({
        planta_origen: plantaOrigen,
        nombre_semilla: nombreSemilla,
        id_unidad_medida: parseInt(idUnidadMedida),
        cantidad: cantidad,
      });
    });

    if (!valid) {
      Helpers.toast('error', 'Complete todos los campos de las filas marcadas en rojo.');
      return;
    }

    if (items.length === 0) {
      Helpers.toast('error', 'Debe agregar al menos un tipo de semilla.');
      return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('items', JSON.stringify(items));

    $.ajax({
      url: `${baseUrl}?action=registrar_insumo_ajax`,
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
          $('#insumoModal').modal('hide');
          recoleccionTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al registrar semillas');
      });
  });

  // Limpiar formularios al cerrar modales
  $('#recoleccionModal, #completarModal, #insumoModal, #ubicacionQuickModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  initDataTable();
});
