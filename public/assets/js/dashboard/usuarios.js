import * as Validations from '../utils/validation.js';
import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}usuarios`;
  let usuariosTable = null;

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
      SkeletonHelper.showTableSkeleton('usuariosTable', 5, 4);
    }
    usuariosTable = $('#usuariosTable').DataTable({
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
          data: 'trabajador_nombre',
          render: (data) => data || '<span class="text-muted">—</span>',
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            const isSuper = data.id == 1;
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit">
                    <i class="fas fa-edit"></i> Editar
                </button>
                ${isSuper ? '' : `
                <button class="btn btn-sm btn-outline-danger btn-delete">
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
            SkeletonHelper.showTableSkeleton('usuariosTable', 5, 4);
            usuariosTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  function togglePermisosChecklist(roleId, container) {
    const $container = container || $('#permisosChecklist');
    if (roleId == 1) {
      $container.hide();
    } else {
      $container.show();
    }
  }

  //Agregar usuario
  $('#btnAddUser').on('click', function () {
    const $editModal = $('#editUserModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addUserModal').modal({ focus: false }).modal('show');
    // Reset checklist: mostrar si rol no es admin
    togglePermisosChecklist(parseInt($('#addUserRole').val()), $('#addPermisosChecklist'));
  });

  $(document).on('change', '#addUserRole', function () {
    togglePermisosChecklist(parseInt($(this).val()), $('#addPermisosChecklist'));
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
          usuariosTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar usuario');
      });
  });

  $(document).on('click', '.btn-edit', function () {
    const row = usuariosTable.row($(this).closest('tr')).data();

    const $addModal = $('#addUserModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    const userId = parseInt(row.id, 10);
    const isSuper = userId === 1;
    const currentUserId = parseInt($('#currentUserId').val(), 10);
    const currentUserRole = parseInt($('#currentUserRole').val(), 10);
    const isOwnAccount = userId === currentUserId;
    const isAdmin = currentUserRole === 1;

    $('#editUserIdHidden').val(userId);
    $('#editUserName').val(row.nombre_usuario);
    $('#editUserEmail').val(row.correo_electronico);
    $('#editUserRole').val(row.rol_id).prop('disabled', false).css('pointerEvents', isSuper ? 'none' : '').toggleClass('readonly-look', isSuper);
    $('#editTrabajadorRef').val(row.id_trabajador_ref || '');
    $('#editUserPassword').val('');
    $('#editCurrentPassword').val('');
    $('#editUserRoleNote').toggle(isSuper);

    var roleId = parseInt(row.rol_id);
    togglePermisosChecklist(isSuper ? 1 : roleId, $('#editPermisosChecklist'));
    $('#editUserRole').off('change.permisos').on('change.permisos', function () {
      togglePermisosChecklist(parseInt($(this).val()), $('#editPermisosChecklist'));
    });

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

    const avatar = row.avatar;
    const $preview = $('#editAvatarPreview');
    if (avatar) {
      $preview.show().find('img').attr('src', `${window.BASE_URL || '/'}${avatar}`);
    } else {
      $preview.hide();
    }

    const userPermisos = row.permisos || [];
    $('#permisosChecklist input[type="checkbox"]').each(function () {
      $(this).prop('checked', userPermisos.indexOf(parseInt($(this).val())) !== -1);
    });

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
          usuariosTable.ajax.reload(null, false);

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

  $(document).on('click', '.btn-delete', function () {
    const row = usuariosTable.row($(this).closest('tr')).data();
    const id = row.id;
    const nombre = row.nombre_usuario;

    Swal.fire({
      title: '¿Eliminar usuario?',
      html: `
        <p>¿Deseas eliminar a <strong>${Helpers.escapeHtml(nombre)}</strong>?</p>
        <p style="font-size:0.85rem;color:#6b7280;">Esta acción no se puede deshacer.</p>
        <hr>
        <div class="text-start">
          <label class="form-label" style="font-weight:600;">Ingresa la contraseña de tu usuario:</label>
          <input type="password" id="swal-delete-password" class="form-control" placeholder="Contraseña" autocomplete="off">
        </div>
      `,
      icon: 'warning',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      preConfirm: () => {
        const password = document.getElementById('swal-delete-password').value;
        if (!password) {
          Swal.showValidationMessage('Debes ingresar tu contraseña');
          return false;
        }
        return password;
      },
      didOpen: () => {
        const input = document.getElementById('swal-delete-password');
        if (input) setTimeout(() => input.focus(), 100);
      },
    }).then((result) => {
      if (!result.isConfirmed || !result.value) return;

      const password = result.value;
      Ajax.post(`${baseUrl}?action=delete_ajax`, { id, current_password: password })
        .then((response) => {
          if (response.success) {
            Helpers.toast('success', 'Usuario eliminado correctamente');
            usuariosTable.ajax.reload(null, false);
          } else {
            Helpers.toast('error', response.message);
          }
        })
        .catch((err) => {
          Helpers.toast('error', err);
        });
    });
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
    // Desmarcar todos los permisos
    $(this).find('.permisos-checklist input[type="checkbox"]').prop('checked', false);
  });

  initDataTable();
});
