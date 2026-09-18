import * as Helpers from '../utils/helpers.js';
import * as C from '../utils/components.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}permisos`;
  let permisosTable = null;

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('permisosTable', 5, 4);
    }
    permisosTable = $('#permisosTable').DataTable({
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
          render: (data) => {
            if (!data) return '<span class="text-muted">—</span>';
            const escaped = Helpers.escapeHtml(data);
            return escaped.length > 80
              ? `<span title="${escaped}">${Helpers.truncateText(escaped, 80)}</span>`
              : escaped;
          },
        },
        {
          data: null,
          render: (data) => {
            if (data.id === 1) {
              return '<span class="badge bg-success">Acceso total</span>';
            }
            return `<span class="badge bg-secondary">${data.permisos_asignados} permisos</span>`;
          },
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            if (data.id === 1) {
              return `<span class="text-muted small">—</span>`;
            }
            return C.btnGroup(
              C.btnCustom({
                label: 'Permisos',
                icon: 'fa-key',
                className: 'btn-permisos',
                btnClass: 'btn-outline-primary',
              })
            );
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
              SkeletonHelper.showTableSkeleton('permisosTable', 5, 4);
            }
            permisosTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  $(document).on('click', '.btn-permisos', function () {
    const row = permisosTable.row($(this).closest('tr')).data();

    $('#permisosRoleId').val(row.id);
    $('#permisosRoleName').text(row.nombre_rol);
    $('#rolePermisosForm input[name="permisos[]"]').prop('checked', false);

    $.ajax({
      url: `${baseUrl}?action=get_role_permissions&id=${row.id}`,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .done((response) => {
        if (response.success && Array.isArray(response.permisos)) {
          response.permisos.forEach((p) => {
            const val = p.id_modulo + ':' + p.id_permiso;
            $(`#rolePermisosForm input[name="permisos[]"][value="${val}"]`).prop('checked', true);
          });
          $('#rolePermisosModal').modal({ focus: false }).modal('show');
        } else {
          Helpers.toast('error', response.message || 'No se pudieron cargar los permisos del rol');
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al cargar los permisos del rol');
      });
  });

  $('#btnCheckAll').on('click', function () {
    $('#rolePermisosForm input[name="permisos[]"]').prop('checked', true);
  });

  $('#btnUncheckAll').on('click', function () {
    $('#rolePermisosForm input[name="permisos[]"]').prop('checked', false);
  });

  $('#rolePermisosForm').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    $.ajax({
      url: `${baseUrl}?action=save_ajax`,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      dataType: 'json',
    })
      .done((response) => {
        if (response.success) {
          Helpers.toast('success', 'Permisos actualizados correctamente');
          $('#rolePermisosModal').modal('hide');
          permisosTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al guardar los permisos');
      });
  });

  $('#rolePermisosModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
    $(this).find('input[name="permisos[]"]').prop('checked', false);
  });

  initDataTable();
});
