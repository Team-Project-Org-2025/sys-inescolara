// Expresiones regulares reutilizables
export const REGEX = {
  cedula: /^\d{7,10}$/,
  codigo: /^\d{9}$/,
  factura: /^\d{8}$/,
  nombre: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,100}$/,
  nombreProducto: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{1,40}$/,
  nombrePlanta: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,150}$/,
  email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
  telefono: /^\d{11}$/,
  precio: /^(100(\.00?)?|[1-9]?\d(\.\d{1,2})?)$/,
  precioRango: /^(100(\.00?)?|[1-9]?\d(\.\d{1,2})?)$/,
  direccion: /^.{5,150}$/,
  ubicacion: /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{2,100}$/,
  cargo: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,50}$/,
  referencia: /^\d{8,10}$/,
  referenciaVenta: /^[A-Za-z0-9\-]{1,15}$/,
  banco: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.]{3,30}$/,
  password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,30}$/,
  passwordEdit: /^(?:.{0}|(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,30})$/,
};

// Mensajes de error personalizados
export const MESSAGES = {
  cedula: 'Cédula inválida (7-10 dígitos)',
  codigo: 'Código inválido (9 dígitos)',
  factura: 'Factura inválida (8 dígitos)',
  nombre: 'Nombre inválido (2-100 caracteres, solo letras)',
  nombreProducto: 'Nombre inválido (1-40 caracteres)',
  nombrePlanta: 'Nombre de planta inválido (mínimo 3 caracteres, sin números)',
  email: 'Email inválido',
  telefono: 'Teléfono inválido (11 dígitos)',
  precio: 'Precio inválido (formato: 0.00)',
  direccion: 'Dirección muy corta (mínimo 5 caracteres)',
  ubicacion: 'Ubicación inválida (solo letras, números y espacios)',
  cargo: 'Cargo inválido (2-50 caracteres)',
  referencia: 'Referencia bancaria inválida (8-10 dígitos)',
  referenciaVenta: 'Referencia inválida (máx 15 caracteres, solo letras, números y guión)',
  banco: 'Nombre del banco inválido (3-30 caracteres)',
  password: 'Contraseña debe tener 8-30 caracteres, mayúsculas, minúsculas, números y símbolos',
  required: 'Este campo es requerido',
  select: 'Seleccione una opción',
  tracking: 'Tracking inválida (8 dígitos)',
  default: 'Campo inválido',
};

//Valida un campo individual
export const validateField = ($input, regex = null, errorMsg = '') => {
  const valor = $input.val().trim();
  const isRequired = $input.prop('required'); // <--- Detecta si es obligatorio en el HTML

  // Remover mensajes de error previos
  $input.siblings('.invalid-feedback').remove();

  // Si el campo está vacío
  if (valor === '') {
    if (isRequired) {
      // Si es requerido y está vacío -> Error
      $input.addClass('is-invalid').removeClass('is-valid');
      $input.after(`<div class="invalid-feedback">Este campo es requerido.</div>`);
      return false;
    } else {
      // Si NO es requerido y está vacío -> Es totalmente válido (Ej: Nombre técnico vacío)
      $input.removeClass('is-invalid is-valid');
      return true;
    }
  }

  // Validar con regex (solo si existe la regex)
  if (regex && !regex.test(valor)) {
    $input.addClass('is-invalid').removeClass('is-valid');
    if (errorMsg) $input.after(`<div class="invalid-feedback">${errorMsg}</div>`);
    return false;
  }

  // Válido si supera la regex
  $input.removeClass('is-invalid').addClass('is-valid');
  return true;
};

//Valida un select
// Valida un select respetando si es requerido u opcional
export const validateSelect = ($select) => {
  const valor = $select.val();
  const isRequired = $select.prop('required'); // <--- Detecta si el HTML exige que se elija una opción

  // Si no se ha seleccionado nada o es la opción por defecto (valor vacío)
  if (!valor || valor === '') {
    if (isRequired) {
      // Si es obligatorio y está vacío -> Error
      $select.addClass('is-invalid').removeClass('is-valid');
      return false;
    } else {
      // Si NO es obligatorio y está en la opción vacía -> Es totalmente válido
      $select.removeClass('is-invalid is-valid');
      return true;
    }
  }

  // Si tiene un valor seleccionado diferente de vacío -> Siempre es válido
  $select.removeClass('is-invalid').addClass('is-valid');
  return true;
};

//Configura validación en tiempo real para un formulario
export const setupRealTimeValidation = ($form, rules, isEdit = false) => {
  Object.entries(rules).forEach(([campo, tipo]) => {
    const $input = $form.find(`[name="${campo}"]`);
    if (!$input.length) return;

    // Determinar regex y mensaje
    let regex = REGEX[tipo];
    let message = MESSAGES[tipo];

    // Caso especial para password en edición
    if (campo === 'password' && isEdit) {
      regex = REGEX.passwordEdit;
    }

    // Si es select
    if ($input.is('select')) {
      $input.on('change blur', () => validateSelect($input));
      return;
    }

    // Si es input normal
    $input.on('input blur', () => {
      // Password opcional en edición
      if (campo === 'password' && isEdit && $input.val() === '') {
        $input.removeClass('is-valid is-invalid');
        return;
      }
      validateField($input, regex, message);
    });
  });
};

//Valida todo el formulario antes de submit
export const validateForm = ($form, rules, isEdit = false) => {
  let isValid = true;

  Object.entries(rules).forEach(([campo, tipo]) => {
    const $input = $form.find(`[name="${campo}"]`);
    if (!$input.length) return;

    // Password opcional en edición
    if (campo === 'password' && isEdit && $input.val() === '') {
      return;
    }

    let regex = REGEX[tipo];
    let message = MESSAGES[tipo];

    if (campo === 'password' && isEdit) {
      regex = REGEX.passwordEdit;
    }

    if ($input.is('select')) {
      if (!validateSelect($input)) isValid = false;
    } else {
      if (!validateField($input, regex, message)) isValid = false;
    }
  });

  return isValid;
};

//Limpia validaciones de un formulario
export const clearValidation = ($form) => {
  $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
  $form.find('.invalid-feedback').remove();
};
