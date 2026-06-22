import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}empleados`;
  let empleadosTable = null;

  const employeeRules = {
    nombre_trabajador: 'nombre',
    apellido_trabajador: 'nombre',
    cedula_trabajador: 'cedula',
    telefono_trabajador: 'telefono'
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('empleadosTable', 5, 7);
    }
    empleadosTable = $('#empleadosTable').DataTable({
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
          data: 'cargo',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'activo',
          render: (data) => data ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>',
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
              SkeletonHelper.showTableSkeleton('empleadosTable', 5, 5);
            }
            empleadosTable.ajax.reload(null, false);
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

    if (!validateForm($(this), employeeRules)) {
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
          Helpers.toast('success', 'Trabajador agregado correctamente');
          $('#addEmployeeModal').modal('hide');
          empleadosTable.ajax.reload(null, false);
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
    const row = empleadosTable.row($(this).closest('tr')).data();

    const $addModal = $('#addEmployeeModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editEmployeeId').val(row.id);
    $('#editEmployeeName').val(row.nombre_trabajador);
    $('#editEmployeeApellido').val(row.apellido_trabajador);
    $('#editEmployeeCedula').val(row.cedula_trabajador);
    $('#editEmployeeTelefono').val(row.telefono_trabajador);
    $('#editEmployeeCargo').val(row.cargo);
    $('#editEmployeeActivo').prop('checked', !!row.activo);

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
          empleadosTable.ajax.reload(null, false);
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
    const row = empleadosTable.row($(this).closest('tr')).data();
    const id = row.id;
    const nombre = row.nombre_trabajador;

    Helpers.confirmDialog(
      '¿Eliminar trabajador?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Trabajador eliminado correctamente');
          empleadosTable.ajax.reload(null, false);
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

  // Limpiar modales
  $('#addEmployeeModal, #editEmployeeModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
    // NUEVO: Limpia los feedbacks visuales que hayan quedado rojos al cerrar
    $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
    $form.find('.invalid-feedback').remove();
  });

  initDataTable();
  setupRealTimeValidation($('#addEmployeeForm'), employeeRules);
  setupRealTimeValidation($('#editEmployeeForm'), employeeRules, true);
});
