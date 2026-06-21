import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm, clearValidation } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}proveedores`;
  let proveedoresTable = null;

  const supplierRules = {
    nombre_proveedor: 'nombre',      
    contacto_vendedor: 'nombre',     
    telefono_proveedor: 'telefono',
    rif_numero: null,
    rif_tipo: 'select'
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('proveedoresTable', 5, 5);
    }
    proveedoresTable = $('#proveedoresTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_suppliers`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'suppliers',
      },
      columns: [
        { data: 'nombre_proveedor' },
        {
          data: 'rif_proveedor',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'contacto_vendedor',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'telefono_proveedor',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: null,
          orderable: false,
          render: () => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete">
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
              SkeletonHelper.showTableSkeleton('proveedoresTable', 5, 5);
            }
            proveedoresTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  // Agregar proveedor
  $('#btnAddSupplier').on('click', function () {
    const $editModal = $('#editSupplierModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addSupplierModal').modal({ focus: false }).modal('show');
  });

  // Combinar tipo + número RIF antes de enviar
  const combineRif = (form, tipoId, numeroId, hiddenId) => {
    const tipo = form.querySelector(`#${tipoId}`).value;
    const numero = form.querySelector(`#${numeroId}`).value.trim();
    form.querySelector(`#${hiddenId}`).value = tipo && numero ? `${tipo}-${numero}` : '';
  };

  $('#addSupplierForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), supplierRules)) {
      Helpers.toast('error', 'Por favor, verifique los campos marcados en rojo.');
      return; 
    }
    combineRif(this, 'addRifTipo', 'addRifNumero', 'addRifProveedor');
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
          Helpers.toast('success', 'Proveedor agregado correctamente');
          $('#addSupplierModal').modal('hide');
          proveedoresTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar proveedor');
      });
  });

  // Editar proveedor
  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);
    const row = proveedoresTable.row($btn.closest('tr')).data();

    const $addModal = $('#addSupplierModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    const rifFull = row.rif_proveedor || '';
    const rifParts = rifFull.split('-');
    $('#editSupplierId').val(row.id);
    $('#editSupplierName').val(row.nombre_proveedor);
    $('#editRifTipo').val(rifParts.length > 1 ? rifParts[0] : '');
    $('#editRifNumero').val(rifParts.length > 1 ? rifParts.slice(1).join('-') : rifFull);
    $('#editSupplierContacto').val(row.contacto_vendedor);
    $('#editSupplierTelefono').val(row.telefono_proveedor);

    $('#editSupplierModal').modal({ focus: false }).modal('show');
  });

  $('#editSupplierForm').on('submit', function (e) {
    e.preventDefault();
    combineRif(this, 'editRifTipo', 'editRifNumero', 'editRifProveedor');
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
          Helpers.toast('success', 'Proveedor actualizado correctamente');
          $('#editSupplierModal').modal('hide');
          proveedoresTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar proveedor');
      });
  });

  // Eliminar proveedor
  $(document).on('click', '.btn-delete', function () {
    const row = proveedoresTable.row($(this).closest('tr')).data();
    const id = row.id;
    const nombre = row.nombre_proveedor;

    Helpers.confirmDialog(
      '¿Eliminar proveedor?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Proveedor eliminado correctamente');
              proveedoresTable.ajax.reload(null, false);
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

  // Limpiar modales
  $('#addSupplierModal, #editSupplierModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);

  });

  initDataTable();

  setupRealTimeValidation($('#addSupplierForm'), supplierRules);
  setupRealTimeValidation($('#editSupplierForm'), supplierRules, true);
});
