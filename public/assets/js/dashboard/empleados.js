import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';
import * as C from '../utils/components.js';

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
      SkeletonHelper.showTableSkeleton('empleadosTable', 5, 5);
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
          data: 'cargo',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: null,
          orderable: false,
          render: (data) => C.btnGroup(
              C.btnView('btn-view'),
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

  // Ver detalle empleado
  $(document).on('click', '.btn-view', function () {
    const row = empleadosTable.row($(this).closest('tr')).data();
    const id = row.id;
    $.getJSON(`${baseUrl}?action=get_detail&id=${id}`, { 'X-Requested-With': 'XMLHttpRequest' })
      .done((res) => {
        if (!res.success) {
          Helpers.toast('error', res.message);
          return;
        }
        const e = res.employee;
        let html = `
          <div class="mb-3">
            <table class="table table-sm table-bordered">
              <tbody>
                <tr><th style="width:35%;">Nombre</th><td>${Helpers.escapeHtml(e.nombre_trabajador || '')}</td></tr>
                <tr><th>Apellido</th><td>${Helpers.escapeHtml(e.apellido_trabajador || '')}</td></tr>
                <tr><th>Cédula</th><td>${Helpers.escapeHtml(e.cedula_trabajador || '')}</td></tr>
                <tr><th>Teléfono</th><td>${Helpers.escapeHtml(e.telefono_trabajador || '')}</td></tr>
                <tr><th>Cargo</th><td>${Helpers.escapeHtml(e.cargo || '')}</td></tr>
                <tr><th>Activo</th><td>${e.activo ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>'}</td></tr>
              </tbody>
            </table>
          </div>`;
        $('#detailEmployeeBody').html(html);
        $('#detailEmployeeModal').modal({ focus: false }).modal('show');
      })
      .fail(() => {
        Helpers.toast('error', 'Error al obtener los detalles del empleado');
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
  $('#addEmployeeModal, #editEmployeeModal, #detailEmployeeModal').on('hidden.bs.modal', function () {
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
