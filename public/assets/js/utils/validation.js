// Expresiones regulares reutilizables
export const REGEX = {
  cedula: /^\d{7,10}$/,
  codigo: /^\d{9}$/,
  factura: /^\d{8}$/,
  nombre: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,100}$/,
  nombreProducto: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{1,40}$/,
  nombrePlanta: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.]{3,150}$/,
  email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
  telefono: /^\d{11}$/,
  precio: /^(100(\.00?)?|[1-9]?\d(\.\d{1,2})?)$/,
  precioRango: /^(100(\.00?)?|[1-9]?\d(\.\d{1,2})?)$/,
  direccion: /^.{5,150}$/,
  cargo: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,50}$/,
  referencia: /^\d{8,10}$/,
  referenciaVenta: /^[A-Za-z0-9\-]{1,15}$/,
  banco: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.]{3,30}$/,
  password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,30}$/,
  passwordEdit:
    /^(?:.{0}|(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,30})$/,
  //hoy: new Date().toISOString().split("T")[0],
  fechaFormato: /^\d{4}-\d{2}-\d{2}$/, 
  cantidad: /^[1-9]\d*$/,
  ubicacion: /^[A-Za-z0-9\s\-\.#]{3,100}$/,
};

export const hoy = new Date().toISOString().split("T")[0];

// Mensajes de error personalizados
export const MESSAGES = {
  cedula: 'Cédula inválida (7-10 dígitos)',
  codigo: 'Código inválido (9 dígitos)',
  factura: 'Factura inválida (8 dígitos)',
  nombre: 'Nombre inválido (2-100 caracteres, solo letras)',
  nombreProducto: 'Nombre inválido (1-40 caracteres)',
  email: 'Email inválido',
  telefono: 'Teléfono inválido (11 dígitos)',
  precio: 'Precio inválido (formato: 0.00)',
  direccion: 'Dirección muy corta (mínimo 5 caracteres)',
  cargo: 'Cargo inválido (2-50 caracteres)',
  referencia: 'Referencia bancaria inválida (8-10 dígitos)',
  referenciaVenta: 'Referencia inválida (máx 15 caracteres, solo letras, números y guión)',
  banco: 'Nombre del banco inválido (3-30 caracteres)',
  password: 'Contraseña debe tener 8-30 caracteres, mayúsculas, minúsculas, números y símbolos',
  required: 'Este campo es requerido',
  select: 'Seleccione una opción',
  tracking: 'Tracking inválida (8 dígitos)',
  fecha: 'Fecha inválidda',
  fechaFutura: 'La fecha no puede ser posterior al día de hoy',
  cantidad: 'Cantidad inválida (solo números, sin ceros a la izquierda)',
  ubicacion: 'Ubicación inválida (3-100 caracteres)',

};


//Valida un campo individual
export const validateField = ($input, regex = null, errorMsg = '') => {
  const valor = $input.val().trim();

  // Remover mensajes de error previos
  $input.siblings('.invalid-feedback').remove();

  // Campo vacío
  if (valor === '') {
    $input.addClass('is-invalid').removeClass('is-valid');
    if (errorMsg) $input.after(`<div class="invalid-feedback">${errorMsg}</div>`);
    return false;
  }

  // Validar con regex
  if (regex && !regex.test(valor)) {
    $input.addClass('is-invalid').removeClass('is-valid');
    if (errorMsg) $input.after(`<div class="invalid-feedback">${errorMsg}</div>`);
    return false;
  }

  // Válido
  $input.removeClass('is-invalid').addClass('is-valid');
  return true;
};

//Valida un select
export const validateSelect = ($select) => {
  const valor = $select.val();
  if (!valor || valor === '') {
    $select.addClass('is-invalid').removeClass('is-valid');
    return false;
  }
  $select.removeClass('is-invalid').addClass('is-valid');
  return true;
};

//Configura validación en tiempo real para un formulario
export const setupRealTimeValidation = ($form, rules, isEdit = false) => {
  Object.entries(rules).forEach(([campo, tipo]) => {
    const $input = $form.find(`[name="${campo}"]`);
    if (!$input.length) return;

    if (tipo === 'fechaFuturaCheck') {
      $input.on('input change blur', () => validateNoFutureDate($input));
      return;
    }

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

    if (tipo === 'fechaFuturaCheck') {
      if (!validateNoFutureDate($input)) isValid = false;
      return;
    }

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

// Valida que la fecha no sea posterior a la constante hoy 
export const validateNoFutureDate = ($input) => {
  const valor = $input.val().trim();
  
  if (!REGEX.fechaFormato.test(valor)) {
    return validateField($input, REGEX.fechaFormato, MESSAGES.fechaFormato);
  }
  
  if (valor > hoy) {
    return validateField($input, /^(?!.*)$/, MESSAGES.fechaFutura);
  }
  // Válido - limpiar validación
  $input.removeClass('is-invalid').addClass('is-valid');
  $input.siblings('.invalid-feedback').remove();
  return true;
};
