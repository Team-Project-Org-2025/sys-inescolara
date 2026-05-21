import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}clients`;
  let clientsTable = null;

  const clientRules = {
    nombre_cliente: 'nombre',
    contacto_cliente: 'telefono'
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('clientsTable', 5, 3);
    }
    clientsTable = $('#clientsTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_clients`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'clients',
      },
      columns: [
        { data: 'nombre_cliente' },
        {
          data: 'contacto_cliente',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre_cliente="${Helpers.escapeHtml(data.nombre_cliente)}"
                        data-contacto_cliente="${Helpers.escapeHtml(data.contacto_cliente || '')}">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre_cliente="${Helpers.escapeHtml(data.nombre_cliente)}">
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
              SkeletonHelper.showTableSkeleton('clientsTable', 5, 3);
            }
            clientsTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  // Agregar cliente
  $('#btnAddClient').on('click', function () {
    const $editModal = $('#editClientModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addClientModal').modal({ focus: false }).modal('show');
  });

  $('#addClientForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), clientRules)) {
      Helpers.toast('error', 'Por favor, verifique los campos marcados en rojo.');
      return;
    }

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
          Helpers.toast('success', 'Cliente agregado correctamente');
          $('#addClientModal').modal('hide');
          clientsTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar cliente');
      });
  });

  // Editar cliente
  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    const $addModal = $('#addClientModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editClientId').val($btn.data('id'));
    $('#editClientName').val($btn.data('nombre_cliente'));
    $('#editClientContacto').val($btn.data('contacto_cliente'));

    $('#editClientModal').modal({ focus: false }).modal('show');
  });

  $('#editClientForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), clientRules, true)) {
      Helpers.toast('error', 'Por favor, verifique los campos marcados en rojo.');
      return;
    }

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
          Helpers.toast('success', 'Cliente actualizado correctamente');
          $('#editClientModal').modal('hide');
          clientsTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar cliente');
      });
  });

  // Eliminar cliente
  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre_cliente');

    Helpers.confirmDialog(
      '¿Eliminar cliente?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Cliente eliminado correctamente');
              clientsTable.ajax.reload(null, false);
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
  $('#addClientModal, #editClientModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  // Limpiar modales
  $('#addClientModal, #editClientModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
    // NUEVO: LIMPIAR FEEDBACK VISUAL ANTIGUO AL CERRAR
    $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
    $form.find('.invalid-feedback').remove();
  });

  initDataTable();
  
  setupRealTimeValidation($('#addClientForm'), clientRules);
  setupRealTimeValidation($('#editClientForm'), clientRules, true);
});
