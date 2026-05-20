import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}employees`;
  let employeesTable = null;

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('employeesTable', 5, 5);
    }
    employeesTable = $('#employeesTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_employees`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'employees',
      },
      columns: [
        { data: 'nombre_trabajador' },
        {
          data: 'apellido_trabajador',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'cedula_trabajador',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'telefono_trabajador',
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
                        data-nombre_trabajador="${Helpers.escapeHtml(data.nombre_trabajador)}"
                        data-apellido_trabajador="${Helpers.escapeHtml(data.apellido_trabajador || '')}"
                        data-cedula_trabajador="${Helpers.escapeHtml(data.cedula_trabajador || '')}"
                        data-telefono_trabajador="${Helpers.escapeHtml(data.telefono_trabajador || '')}">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre_trabajador="${Helpers.escapeHtml(data.nombre_trabajador)}">
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
              SkeletonHelper.showTableSkeleton('employeesTable', 5, 5);
            }
            employeesTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  // Agregar empleado
  $('#btnAddEmployee').on('click', function () {
    const $editModal = $('#editEmployeeModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addEmployeeModal').modal({ focus: false }).modal('show');
  });

  $('#addEmployeeForm').on('submit', function (e) {
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
          Helpers.toast('success', 'Trabajador agregado correctamente');
          $('#addEmployeeModal').modal('hide');
          employeesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar trabajador');
      });
  });

  // Editar empleado
  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    const $addModal = $('#addEmployeeModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editEmployeeId').val($btn.data('id'));
    $('#editEmployeeName').val($btn.data('nombre_trabajador'));
    $('#editEmployeeApellido').val($btn.data('apellido_trabajador'));
    $('#editEmployeeCedula').val($btn.data('cedula_trabajador'));
    $('#editEmployeeTelefono').val($btn.data('telefono_trabajador'));

    $('#editEmployeeModal').modal({ focus: false }).modal('show');
  });

  $('#editEmployeeForm').on('submit', function (e) {
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
          Helpers.toast('success', 'Trabajador actualizado correctamente');
          $('#editEmployeeModal').modal('hide');
          employeesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar trabajador');
      });
  });

  // Eliminar empleado
  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre_trabajador');

    Helpers.confirmDialog(
      '¿Eliminar trabajador?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Trabajador eliminado correctamente');
              employeesTable.ajax.reload(null, false);
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
  $('#addEmployeeModal, #editEmployeeModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  initDataTable();
});
