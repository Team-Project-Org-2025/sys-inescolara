import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}supplies`;
  let suppliesTable = null;

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('suppliesTable', 5, 6);
    }
    suppliesTable = $('#suppliesTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_supplies`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'supplies',
      },
      columns: [
        { data: 'nombre_insumo' },
        {
          data: 'categoria',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'nombre_unidad_medida',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        { 
          data: 'stock_actual',
          render: (data) => parseFloat(data).toFixed(2)
        },
        { 
          data: 'costo_unitario_actual',
          render: (data) => `$${parseFloat(data).toFixed(2)}`
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id_insumo)}"
                        data-nombre_insumo="${Helpers.escapeHtml(data.nombre_insumo)}"
                        data-id_unidad_medida="${Helpers.escapeHtml(data.id_unidad_medida)}"
                        data-categoria="${Helpers.escapeHtml(data.categoria || '')}"
                        data-stock_actual="${Helpers.escapeHtml(data.stock_actual)}"
                        data-costo_unitario_actual="${Helpers.escapeHtml(data.costo_unitario_actual)}">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id_insumo)}"
                        data-nombre_insumo="${Helpers.escapeHtml(data.nombre_insumo)}">
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
              SkeletonHelper.showTableSkeleton('suppliesTable', 5, 4);
            }
            suppliesTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  // Abrir Modal Agregar Insumo
  $('#btnAddSupply').on('click', function () {
    const $editModal = $('#editSupplyModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addSupplyModal').modal({ focus: false }).modal('show');
  });

  // Guardar Nuevo Insumo
  $('#addSupplyForm').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

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
          Helpers.toast('success', 'Insumo agregado correctamente');
          $('#addSupplyModal').modal('hide');
          suppliesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar insumo');
      });
  });

  // Abrir Modal Editar Insumo
  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    const $addModal = $('#addSupplyModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    // Mapeo exacto a los inputs del Modal de Edición
    $('#editSupplyId').val($btn.data('id'));
    $('#editSupplyName').val($btn.data('nombre_insumo'));
    $('#editSupplyUnit').val($btn.data('id_unidad_medida'));
    $('#editSupplyCat').val($btn.data('categoria'));
    $('#editSupplyStock').val($btn.data('stock_actual'));
    $('#editSupplyCost').val($btn.data('costo_unitario_actual'));

    $('#editSupplyModal').modal({ focus: false }).modal('show');
  });

  // Procesar Edición de Insumo
  $('#editSupplyForm').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    $.ajax({
      url: `${baseUrl}?action=edit_ajax`,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      dataType: 'json',
    })
      .done((response) => {
        if (response.success) {
          Helpers.toast('success', 'Insumo actualizado correctamente');
          $('#editSupplyModal').modal('hide');
          suppliesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar insumo');
      });
  });

  // Eliminar Insumo
  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre_insumo');

    Helpers.confirmDialog(
      '¿Eliminar insumo?',
      `¿Deseas eliminar el insumo <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Insumo eliminado correctamente');
              suppliesTable.ajax.reload(null, false);
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

  // Limpiar/Resetear formularios al cerrar modales
  $('#addSupplyModal, #editSupplyModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  initDataTable();
});