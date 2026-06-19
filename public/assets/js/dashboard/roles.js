import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}roles`;
  let rolesTable = null;

  const roleRules = {
    nombre_rol: 'nombreProducto',
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('rolesTable', 5, 4);
    }
    rolesTable = $('#rolesTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_roles`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'roles',
      },
      columns: [
        { data: 'nombre_rol' },
        {
          data: 'descripcion_rol',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: 'total_permisos',
          render: (data) => `<span class="badge bg-info">${data || 0}</span>`,
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            if (data.id === 1) {
              return `<span class="text-muted small">—</span>`;
            }
            const canDelete = data.id > 2;
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        ${!canDelete ? 'disabled' : ''}>
                    <i class="fas fa-trash"></i>
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
              SkeletonHelper.showTableSkeleton('rolesTable', 5, 4);
            }
            rolesTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  $('#btnAddRole').on('click', function () {
    const $editModal = $('#editRoleModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addRoleModal').modal({ focus: false }).modal('show');
  });

  $('#addRoleForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), roleRules)) {
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
          Helpers.toast('success', 'Rol creado correctamente');
          $('#addRoleModal').modal('hide');
          rolesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al crear el rol');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const row = rolesTable.row($(this).closest('tr')).data();

    const $addModal = $('#addRoleModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editRoleId').val(row.id);
    $('#editRoleName').val(row.nombre_rol);
    $('#editRoleDesc').val(row.descripcion_rol);

    const permIds = row.permisos;
    $('#editRoleModal input[name="permisos[]"]').prop('checked', false);
    if (permIds && Array.isArray(permIds)) {
      permIds.forEach((pid) => {
        $(`#editRoleModal input[name="permisos[]"][value="${pid}"]`).prop('checked', true);
      });
    }

    $('#editRoleModal').modal({ focus: false }).modal('show');
  });

  $('#editRoleForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), roleRules, true)) {
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
          Helpers.toast('success', 'Rol actualizado correctamente');
          $('#editRoleModal').modal('hide');
          rolesTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar el rol');
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const row = rolesTable.row($(this).closest('tr')).data();
    const id = row.id;
    const nombre = row.nombre_rol;

    Helpers.confirmDialog(
      '¿Eliminar rol?',
      `¿Deseas eliminar el rol <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Rol eliminado correctamente');
              rolesTable.ajax.reload(null, false);
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

  $('#addRoleModal, #editRoleModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  initDataTable();

  setupRealTimeValidation($('#addRoleForm'), roleRules);
  setupRealTimeValidation($('#editRoleForm'), roleRules, true);
});
