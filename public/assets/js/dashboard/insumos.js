import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';
import * as C from '../utils/components.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}insumos`;
  let insumosTable = null;

  const supplyRules = {
    nombre_insumo: 'nombre',
    id_unidad_medida: 'select',
    stock_actual: 'cantidad',
    costo_unitario_actual: 'cantidad'
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('insumosTable', 5, 6);
    }
    insumosTable = $('#insumosTable').DataTable({
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
          render: () => C.btnGroup(
              C.btnEdit('btn-edit'),
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
              SkeletonHelper.showTableSkeleton('insumosTable', 5, 6);
            }
            insumosTable.ajax.reload(null, false);
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
    if (!validateForm($(this), supplyRules)) return;

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
          insumosTable.ajax.reload(null, false);
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
    const row = insumosTable.row($(this).closest('tr')).data();

    const $addModal = $('#addSupplyModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    // Mapeo exacto a los inputs del Modal de Edición
    $('#editSupplyId').val(row.id_insumo);
    $('#editSupplyName').val(row.nombre_insumo);
    $('#editSupplyUnit').val(row.id_unidad_medida);
    $('#editSupplyCat').val(row.categoria);
    $('#editSupplyStock').val(row.stock_actual);
    $('#editSupplyCost').val(row.costo_unitario_actual);

    $('#editSupplyModal').modal({ focus: false }).modal('show');
  });

  // Procesar Edición de Insumo
  $('#editSupplyForm').on('submit', function (e) {
    e.preventDefault();
    if (!validateForm($(this), supplyRules, true)) return;

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
          insumosTable.ajax.reload(null, false);
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
    const row = insumosTable.row($(this).closest('tr')).data();
    const id = row.id_insumo;
    const nombre = row.nombre_insumo;

    Helpers.confirmDialog(
      '¿Eliminar insumo?',
      `¿Deseas eliminar el insumo <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Insumo eliminado correctamente');
              insumosTable.ajax.reload(null, false);
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

  setupRealTimeValidation($('#addSupplyForm'), supplyRules);
  setupRealTimeValidation($('#editSupplyForm'), supplyRules, true);

  initDataTable();
});