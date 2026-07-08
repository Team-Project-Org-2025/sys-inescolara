const btn = ({ label, icon, className, btnClass = 'btn-outline-primary', extraAttrs = '' }) =>
  `<button class="btn btn-sm ${btnClass} ${className}" ${extraAttrs}><i class="fas ${icon}"></i> ${label}</button>`;

export const btnEdit = (className = 'btn-edit', extraAttrs = '') =>
  btn({ label: 'Editar', icon: 'fa-edit', className, btnClass: 'btn-outline-warning', extraAttrs });

export const btnDelete = (className = 'btn-delete', extraAttrs = '') =>
  btn({ label: 'Eliminar', icon: 'fa-trash', className, btnClass: 'btn-outline-danger', extraAttrs });

export const btnView = (className = 'btn-view', extraAttrs = '') =>
  btn({ label: 'Ver', icon: 'fa-eye', className, btnClass: 'btn-outline-info', extraAttrs });

export const btnPay = (className = 'btn-pagar', extraAttrs = '') =>
  btn({ label: 'Pagar', icon: 'fa-money-bill-wave', className, btnClass: 'btn-outline-success', extraAttrs });

export const btnCancel = (className = 'btn-cancelar', extraAttrs = '') =>
  btn({ label: 'Cancelar', icon: 'fa-ban', className, btnClass: 'btn-outline-danger', extraAttrs });

export const btnComplete = (className = 'btn-completar', extraAttrs = '') =>
  btn({ label: 'Completar', icon: 'fa-check', className, btnClass: 'btn-outline-success', extraAttrs });

export const btnReceive = (className = 'btn-recibir', extraAttrs = '') =>
  btn({ label: 'Recibir', icon: 'fa-check', className, btnClass: 'btn-outline-success', extraAttrs });

export const btnCustom = ({ label, icon, className, btnClass = 'btn-outline-primary', extraAttrs = '' }) =>
  btn({ label, icon, className, btnClass, extraAttrs });

export const btnLink = ({ label, icon, href, className, btnClass = 'btn-outline-primary', extraAttrs = '' }) =>
  `<a href="${href}" class="btn btn-sm ${btnClass} ${className}" ${extraAttrs}><i class="fas ${icon}"></i> ${label}</a>`;

export const btnGroup = (...buttons) =>
  `<div class="d-flex gap-1">${buttons.join('')}</div>`;
