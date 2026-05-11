import * as Validations from '../utils/validation.js';
import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';

$(document).ready(function() {
    const baseUrl = (window.BASE_URL || '/') + 'user';
    let usersTable = null;
    
    const initDataTable = () => {
        SkeletonHelper.showTableSkeleton('usersTable', 5, 4);
        usersTable = $('#usersTable').DataTable({
            ajax: {
                url: `${baseUrl}?action=get_users`,
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                dataSrc: 'users'
            },
            columns: [
                { data: 'id' },
                { data: 'nombre_usuario' },
                { data: 'rol_id' },
                {
                    data: null,
                    orderable: false,
                    render: (data) => {
                        return `
                            <button class="btn btn-sm btn-outline-primary btn-edit" 
                                    data-id="${Helpers.escapeHtml(data.id)}"
                                    data-nombre_usuario="${Helpers.escapeHtml(data.nombre_usuario)}"
                                    data-rol_id="${Helpers.escapeHtml(data.rol_id)}">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete"
                                    data-id="${Helpers.escapeHtml(data.id)}"
                                    data-nombre_usuario="${Helpers.escapeHtml(data.nombre_usuario)}">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        `;
                    }
                }
            ],
            pageLength: 10,
            responsive: true,
            autoWidth: false,
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            },
            dom: '<"d-flex justify-content-between align-items-center mb-2"lfB>tip',
            buttons: [{
                text: '<i class="fas fa-sync-alt"></i> Actualizar',
                className: 'btn btn-outline-secondary btn-sm',
                action: () => {
                    SkeletonHelper.showTableSkeleton('usersTable', 5, 4);
                    usersTable.ajax.reload(null, false);
                }
            }]
        });
    };

    //Agregar usuario
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();

        const rules = {
            nombre_usuario: 'nombre',
            password: 'password'
        };

        if (!Validations.validateForm($(this), rules)) {
            Helpers.toast('warning', 'Corrija los campos resaltados');
            return;
        }

        const formData = $(this).serialize();

        Ajax.post(`${baseUrl}?action=add_ajax`, formData)
            .then(response => {
                if (response.success) {
                    Helpers.toast('success', 'Usuario agregado correctamente');
                    $('#addUserModal').modal('hide');
                    usersTable.ajax.reload(null, false);
                } else {
                    Helpers.toast('error', response.message);
                }
            })
            .catch(err => {
                Helpers.toast('error', err);
            });
    });


    //Editar usuario
    $(document).on('click', '.btn-edit', function() {
        const $btn = $(this);
        
        $('#editUserId').val($btn.data('id'));
        $('#editUserIdHidden').val($btn.data('id'));
        $('#editUserName').val($btn.data('nombre_usuario'));
        $('#editUserRole').val($btn.data('rol_id'));
        $('#editUserPassword').val('');
        
        Validations.clearValidation($('#editUserForm'));
        $('#editUserModal').modal('show');
    });

    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();

        const rules = {
            nombre_usuario: 'nombre',
            password: 'password'
        };

        if (!Validations.validateForm($(this), rules, true)) {
            Helpers.toast('warning', 'Corrija los campos resaltados');
            return;
        }

        // Filtrar password vacío
        let data = $(this).serializeArray();
        data = data.filter(item => item.name !== 'password' || item.value !== '');

        Ajax.post(`${baseUrl}?action=edit_ajax`, $.param(data))
            .then(response => {
                if (response.success) {
                    Helpers.toast('success', 'Usuario actualizado correctamente');
                    $('#editUserModal').modal('hide');
                    usersTable.ajax.reload(null, false);
                } else {
                    Helpers.toast('error', response.message);
                }
            })
            .catch(err => {
                Helpers.toast('error', err);
            });
    });

    //Eliminar usuario
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre_usuario');

        Helpers.confirmDialog(
            '¿Eliminar usuario?',
            `¿Deseas eliminar a <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
            () => {
                Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
                    .then(response => {
                        if (response.success) {
                            Helpers.toast('success', 'Usuario eliminado correctamente');
                            usersTable.ajax.reload(null, false);
                        } else {
                            Helpers.toast('error', response.message);
                        }
                    })
                    .catch(err => {
                        Helpers.toast('error', err);
                    });
            },
            'Sí, eliminar'
        );
    });

    const addRules = {
        nombre_usuario: 'nombre',
        password: 'password'
    };

    const editRules = {
        nombre_usuario: 'nombre',
        password: 'password'
    };

    Validations.setupRealTimeValidation($('#addUserForm'), addRules, false);
    Validations.setupRealTimeValidation($('#editUserForm'), editRules, true);

    //Limpiar modales
    $('#addUserModal, #editUserModal').on('hidden.bs.modal', function() {
        const $form = $(this).find('form');
        Helpers.resetForm($form);
    });

    initDataTable();
});