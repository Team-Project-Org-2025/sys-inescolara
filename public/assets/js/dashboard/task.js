import * as Helpers from '../utils/helpers.js';
import * as Ajax from '../utils/ajax-handler.js';
import { setupRealTimeValidation, validateForm } from '../utils/validation.js';

$(document).ready(function () {
  const baseUrl = `${window.BASE_URL || '/'}tasks`;
  let tasksTable = null;

  const taskValidationRules = {
    nombre_tarea: 'required',
    descripcion: 'text', // opcional
  };

  const initDataTable = () => {
    if (typeof SkeletonHelper !== 'undefined') {
      SkeletonHelper.showTableSkeleton('tasksTable', 5, 4);
    }
    tasksTable = $('#tasksTable').DataTable({
      ajax: {
        url: `${baseUrl}?action=get_tasks`,
        method: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        dataSrc: 'tasks',
      },
      columns: [
        { data: 'id' },
        { data: 'nombre_tarea' },
        {
          data: 'descripcion',
          render: (data) => data || '<span class="text-muted">—</span>'
        },
        {
          data: null,
          orderable: false,
          render: (data) => {
            return `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre_tarea="${Helpers.escapeHtml(data.nombre_tarea)}"
                        data-descripcion="${Helpers.escapeHtml(data.descripcion || '')}">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${Helpers.escapeHtml(data.id)}"
                        data-nombre="${Helpers.escapeHtml(data.nombre_tarea)}">
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
              SkeletonHelper.showTableSkeleton('tasksTable', 5, 4);
            }
            tasksTable.ajax.reload(null, false);
          },
        },
      ],
    });
  };

  // Abrir modal para agregar
  $('#btnAddTask').on('click', function () {
    const $editModal = $('#editTaskModal');
    if ($editModal.hasClass('show')) {
      $editModal.modal('hide');
    }
    $('#addTaskModal').modal({ focus: false }).modal('show');
  });

  // Submit agregar
  $('#addTaskForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), taskValidationRules)) {
      Helpers.toast('error', 'Por favor, complete el campo obligatorio.');
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
          Helpers.toast('success', 'Tarea agregada correctamente');
          $('#addTaskModal').modal('hide');
          tasksTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al agregar tarea');
      });
  });

  // Editar: llenar modal
  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);

    const $addModal = $('#addTaskModal');
    if ($addModal.hasClass('show')) {
      $addModal.modal('hide');
    }

    $('#editTaskId').val($btn.data('id'));
    $('#editTaskName').val($btn.data('nombre_tarea'));
    $('#editTaskDesc').val($btn.data('descripcion'));

    $('#editTaskModal').modal({ focus: false }).modal('show');
  });

  // Submit editar
  $('#editTaskForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm($(this), taskValidationRules, true)) {
      Helpers.toast('error', 'Por favor, complete el campo obligatorio.');
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
          Helpers.toast('success', 'Tarea actualizada correctamente');
          $('#editTaskModal').modal('hide');
          tasksTable.ajax.reload(null, false);
        } else {
          Helpers.toast('error', response.message);
        }
      })
      .fail((err) => {
        Helpers.toast('error', err.responseJSON?.message || 'Error al actualizar tarea');
      });
  });

  // Eliminar
  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre');

    Helpers.confirmDialog(
      '¿Eliminar tarea?',
      `¿Deseas eliminar <strong>${Helpers.escapeHtml(nombre)}</strong>?`,
      () => {
        Ajax.post(`${baseUrl}?action=delete_ajax`, { id })
          .then((response) => {
            if (response.success) {
              Helpers.toast('success', 'Tarea eliminada correctamente');
              tasksTable.ajax.reload(null, false);
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

  // Limpiar modales al cerrar
  $('#addTaskModal, #editTaskModal').on('hidden.bs.modal', function () {
    const $form = $(this).find('form');
    Helpers.resetForm($form);
  });

  initDataTable();
  setupRealTimeValidation($('#addTaskForm'), taskValidationRules);
  setupRealTimeValidation($('#editTaskForm'), taskValidationRules, true);
});