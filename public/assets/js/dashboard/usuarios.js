import * as Validations from '../utils/validation.js';
import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}user`;
  let usersTable = null;

  const updateSidebarUser = (avatarUrl, userName) => {
    const $avatar = $('.sidebar-user-avatar').first();
    const $name = $('.sidebar-user-name').first();
    if ($name.length) {
      $name.text(userName);
    }
    if ($avatar.length) {
      if (avatarUrl) {
        $avatar.html(`<img src="${avatarUrl}" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`);
      } else {
        $avatar.html(`<span class="sidebar-user-initial">${(userName || 'U')[0].toUpperCase()}</span>`);
      }
    }
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('usersTable', 5, 4);
    }
    usersTable = $('#usersTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_users`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'users',
      },
      columns: [
        { data: 'nombre_usuario' },
        {
          data: 'correo_electronico',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        { data: 'nombre_rol' },
        {
          data: null,
          orderable: false,
          render: (data) => {
            const isSuper = data.id == 1;
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit" 
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre_usuario="${Helpers.escapeHtml(data.nombre_usuario)}"
                        data-correo_electronico="${Helpers.escapeHtml(data.correo_electronico || '')}"
                        data-rol_id="${Helpers.escapeHtml(data.rol_id)}"
                        data-avatar="${Helpers.escapeHtml(data.avatar || '')}">
                    <i class="fas fa-edit"></i> Editar
                </button>
                ${isSuper ? '' : `
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre_usuario="${Helpers.escapeHtml(data.nombre_usuario)}">
                    <i class="fas fa-trash"></i> Eliminar
                </button>`}
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
            SkeletonHelper.showTableSkeleton('usersTable', 5, 4);
            usersTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  //Agregar usuario
  $('#btnAddUser').on('click', function () {
    const $editModal = $('#editUserModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addUserModal').modal({ focus: false }).modal('show');
  });

  $('#addUserForm').on('submit', function (e) {
    e.preventDefault();

    const rules = {
      nombre_usuario: 'nombre',
      password: 'password',
    };

    if (!Validations.validateForm($(this), rules)) {
      Helpers.toast('warning', 'Corrija los campos resaltados');
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
          Helpers.toast('success', 'Usuario agregado correctamente');
          $('#addUserModal').modal('hide');
          usersTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar usuario');
      });
  });

  //Editar usuario
  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    // Cerrar cualquier otro modal abierto primero
    const $addModal = $('#addUserModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    const userId = parseInt($btn.data('id'), 10);
    const isSuper = userId === 1;
    const currentUserId = parseInt($('#currentUserId').val(), 10);
    const currentUserRole = parseInt($('#currentUserRole').val(), 10);
    const isOwnAccount = userId === currentUserId;
    const isAdmin = currentUserRole === 1;

    $('#editUserIdHidden').val(userId);
    $('#editUserName').val($btn.data('nombre_usuario'));
    $('#editUserEmail').val($btn.data('correo_electronico'));
    $('#editUserRole').val($btn.data('rol_id')).prop('disabled', false).css('pointerEvents', isSuper ? 'none' : '').toggleClass('readonly-look', isSuper);
    $('#editUserPassword').val('');
    $('#editCurrentPassword').val('');
    $('#editUserRoleNote').toggle(isSuper);

    // Mostrar campo de contraseña actual si edita su propia cuenta O es administrador
    const $currentPwGroup = $('#currentPasswordGroup');
    const $currentPwHelp = $('#currentPasswordHelp');
    if (isOwnAccount || isAdmin) {
      $currentPwGroup.show();
      $('#editUserPassword').attr('placeholder', 'Nueva contraseña (dejar en blanco para no cambiar)');
      if (isOwnAccount) {
        $currentPwHelp.text('Ingresa tu contraseña actual para cambiarla.');
      } else {
        $currentPwHelp.text('Como administrador, debes ingresar tu propia contraseña para autorizar el cambio.');
      }
    } else {
      $currentPwGroup.hide();
      $('#editUserPassword').attr('placeholder', 'Contraseña (dejar en blanco para no cambiar)');
    }

    // Mostrar preview del avatar actual
    const avatar = $btn.data('avatar');
    const $preview = $('#editAvatarPreview');
    if (avatar) {
      $preview.show().find('img').attr('src', `${window.BASE_URL || '/'}${avatar}`);
    } else {
      $preview.hide();
    }

    Validations.clearValidation($('#editUserForm'));
    $('#editUserModal').modal({ focus: false }).modal('show');
  });

  $('#editUserForm').on('submit', function (e) {
    e.preventDefault();

    const rules = {
      nombre_usuario: 'nombre',
      password: 'password',
    };

    if (!Validations.validateForm($(this), rules, true)) {
      Helpers.toast('warning', 'Corrija los campos resaltados');
      return;
    }

    const formData = new FormData(this);
    // Eliminar password si está vacío
    if (!formData.get('password')) {
      formData.delete('password');
    }
    // Eliminar current_password si está vacío para no enviarlo
    if (!formData.get('current_password')) {
      formData.delete('current_password');
    }

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
          Helpers.toast('success', 'Usuario actualizado correctamente');
          $('#editUserModal').modal('hide');
          usersTable.ajax.reload(null, false);

          // Si el usuario editado es el mismo de la sesión, actualizar el sidebar
          if (response.user && response.user.id == $('#currentUserId').val()) {
            const avatarUrl = response.user.avatar
              ? `${window.BASE_URL || '/'}${response.user.avatar}`
              : null;
            const userName = response.user.nombre_usuario;
            updateSidebarUser(avatarUrl, userName);
          }
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar usuario');
      });
  });

  //Eliminar usuario
  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre_usuario');

    Helpers.confirmDialog(
      '¿Eliminar usuario?',
      `¿Deseas eliminar a <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Usuario eliminado correctamente');
              usersTable.ajax.reload(null, false);
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

  const addRules = {
    nombre_usuario: 'nombre',
    password: 'password',
  };

  const editRules = {
    nombre_usuario: 'nombre',
    password: 'password',
  };

  Validations.setupRealTimeValidation($('#addUserForm'), addRules, false);
  Validations.setupRealTimeValidation($('#editUserForm'), editRules, true);

  //Limpiar modales
  $('#addUserModal, #editUserModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  initDataTable();
});
