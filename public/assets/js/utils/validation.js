// Expresiones regulares reutilizables
export const REGEX = {
  cedula: /^\d{7,8}$/,
  codigo: /^\d{9}$/,
  factura: /^\d{8}$/,
  nombre: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,100}$/,
  nombreProducto: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,40}$/,
  nombrePlanta: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,150}$/,
  email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
  telefono: /^\d{11}$/,
  precio: /^(100(\.00?)?|[1-9]?\d(\.\d{1,2})?)$/,
  precioRango: /^(100(\.00?)?|[1-9]?\d(\.\d{1,2})?)$/,
  direccion: /^.{5,150}$/,
  ubicacion: /^[A-Za-z0-9áéíóúÁÉÍÓÚñÑ\s\-\.#]{3,100}$/,
  cargo: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,50}$/,
  referencia: /^\d{8,10}$/,
  referenciaVenta: /^[A-Za-z0-9\-]{1,15}$/,
  banco: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.]{3,30}$/,
  password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,30}$/,
  passwordEdit: /^(?:.{0}|(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,30})$/,
  fechaFormato: /^\d{4}-\d{2}-\d{2}$/, 
  cantidad: /^[1-9]\d*$/,
};

export const hoy = new Date().toISOString().split("T")[0];

export const validateNoFutureDate = ($input) => {
  const valor = $input.val().trim();
  const isRequired = $input.prop('required');

  $input.siblings('.invalid-feedback').remove();

  if (valor === '') {
    if (isRequired) {
      $input.addClass('is-invalid').removeClass('is-valid');
      $input.after(`<div class="invalid-feedback">Este campo es requerido.</div>`);
      return false;
    }
    $input.removeClass('is-invalid is-valid');
    return true;
  }

  if (!REGEX.fechaFormato.test(valor)) {
    $input.addClass('is-invalid').removeClass('is-valid');
    $input.after(`<div class="invalid-feedback">${MESSAGES.fecha}</div>`);
    return false;
  }

  if (valor > hoy) {
    $input.addClass('is-invalid').removeClass('is-valid');
    $input.after(`<div class="invalid-feedback">${MESSAGES.fechaFutura}</div>`);
    return false;
  }

  $input.removeClass('is-invalid').addClass('is-valid');
  return true;
};

// Mensajes de error personalizados
export const MESSAGES = {
  cedula: 'Cédula inválida (7-8 dígitos)',
  codigo: 'Código inválido (9 dígitos)',
  factura: 'Factura inválida (8 dígitos)',
  nombre: 'Nombre inválido (2-100 caracteres, solo letras)',
  nombreProducto: 'Nombre inválido (3-40 caracteres)',
  nombrePlanta: 'Nombre de planta inválido (mínimo 3 caracteres, sin números)',
  email: 'Email inválido',
  telefono: 'Teléfono inválido (11 dígitos)',
  precio: 'Precio inválido (formato: 0.00)',
  direccion: 'Dirección muy corta (mínimo 5 caracteres)',
  ubicacion: 'Ubicación inválida (3-100 caracteres, letras, números, espacios, guiones y puntos)',
  cargo: 'Cargo inválido (2-50 caracteres)',
  referencia: 'Referencia bancaria inválida (8-10 dígitos)',
  referenciaVenta: 'Referencia inválida (máx 15 caracteres, solo letras, números y guión)',
  banco: 'Nombre del banco inválido (3-30 caracteres)',
  password: 'Contraseña debe tener 8-30 caracteres, mayúsculas, minúsculas, números y símbolos',
  required: 'Este campo es requerido',
  select: 'Seleccione una opción',
  tracking: 'Tracking inválida (8 dígitos)',
  fecha: 'Fecha inválida',
  fechaFutura: 'La fecha no puede ser posterior al día de hoy',
  cantidad: 'Cantidad inválida (solo números, sin ceros a la izquierda)',

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

    if (tipo === 'fechaFuturaCheck') {
      $input.on('input change blur', () => validateNoFutureDate($input));
      return;
    }

    // ========================================================================
    // BLOQUEO 1: BLOQUEAR LETRAS (Para Teléfonos, Cédulas, Códigos)
    // ========================================================================
    if (['telefono', 'cedula', 'codigo', 'factura', 'referencia'].includes(tipo)) {
      
      // 1. Evitar que se escriban letras (Evento de pulsación de tecla)
      $input.on('keypress', function (e) {
        const charCode = (e.which) ? e.which : e.keyCode;
        // Permite solo caracteres correspondientes a los números del 0 al 9 (ASCII 48-57)
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
          e.preventDefault();
        }
      });

      

      // 2. Limpiar caracteres inválidos inmediatamente si el usuario arrastra o pega texto
      $input.on('input', function () {
        this.value = this.value.replace(/\D/g, '');
      });
    }

    // ========================================================================
    // BLOQUEO 2: BLOQUEAR NÚMEROS (Para Nombres de personas, Cargos)
    // ========================================================================
    if (['nombre', 'cargo', 'nombrePlanta', 'nombreProducto'].includes(tipo)) {
      
      // 1. Evitar que se escriban números del 0 al 9 (ASCII 48 al 57)
      $input.on('keypress', function (e) {
        const charCode = (e.which) ? e.which : e.keyCode;
        if (charCode >= 48 && charCode <= 57) {
          e.preventDefault(); // Cancela la pulsación si es un número
        }
      });

      // 2. Limpiar el campo si el usuario intenta pegar números con el mouse
      $input.on('input', function () {
        this.value = this.value.replace(/[0-9]/g, ''); // Remueve cualquier dígito
      });
    }
    // ========================================================================

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
