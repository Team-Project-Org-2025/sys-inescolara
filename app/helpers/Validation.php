<?php

namespace SysInescolara\helpers;

class Validation
{

    // Expresiones regulares (equivalentes a validation.js)
    private static $patterns = [
        'tipo_rif' => '/^[JGC]$/',
        'cedula' => '/^\d{7,10}$/',
        'codigo' => '/^\d{9}$/',
        'factura' => '/^\d{8}$/',
        'nombre' => '/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{4,100}$/',
        'nombreProducto' => '/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{1,40}$/',
        'nombrePrenda' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.]{4,150}$/',
        'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        'telefono' => '/^\d{11}$/',
        'precio' => '/^(100(\.00?)?|[1-9]?\d(\.\d{1,2})?)$/',
        'direccion' => '/^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s.,-]{5,150}$/',
        'cargo' => '/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,50}$/',
        'referencia' => '/^\d{8,10}$/',
        'referenciaVenta' => '/^[A-Za-z0-9\-]{1,15}$/',
        'banco' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.]{3,30}$/',
        'password' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,30}$/',
        'rif' => '/^\d{9}$/',
        'tracking' => '/^\d{8}$/'
    ];

    // Mensajes de error
    private static $messages = [
        'cedula' => 'Cédula inválida (7-10 dígitos)',
        'codigo' => 'Código inválido (9 dígitos)',
        'factura' => 'Factura inválida (8 dígitos)',
        'nombre' => 'Nombre inválido (5-100 caracteres, solo letras)',
        'nombreProducto' => 'Nombre inválido (1-40 caracteres)',
        'email' => 'Email inválido',
        'telefono' => 'Teléfono inválido (11 dígitos)',
        'precio' => 'Precio inválido (formato: 0.00)',
        'direccion' => 'Dirección muy corta (mínimo 5 caracteres)',
        'cargo' => 'Cargo inválido (2-50 caracteres)',
        'referencia' => 'Referencia bancaria inválida (8-10 dígitos)',
        'referenciaVenta' => 'Referencia inválida (máx 15 caracteres)',
        'banco' => 'Nombre del banco inválido (3-30 caracteres)',
        'password' => 'Contraseña debe tener 8-30 caracteres, mayúsculas, minúsculas, números y símbolos',
        'required' => 'Este campo es requerido',
        'rif' => 'RIF inválido (9 dígitos)',
        'tracking' => 'Tracking inválido (8 dígitos)',
        'tipo_rif' => 'Tipo de RIF inválido (debe ser J, G, C)'
    ];

    /**
     * Valida un campo individual
     */
    public static function validateField($value, $type, $required = true)
    {
        $value = is_string($value) ? trim($value) : $value;

        // Verificar si es requerido
        if ($required && empty($value) && $value !== '0') {
            return [
                'valid' => false,
                'message' => self::$messages['required']
            ];
        }

        // Si no es requerido y está vacío, es válido
        if (!$required && empty($value)) {
            return ['valid' => true];
        }

        // Validar con regex si existe el tipo
        if (isset(self::$patterns[$type])) {
            $isValid = preg_match(self::$patterns[$type], $value);
            return [
                'valid' => (bool)$isValid,
                'message' => $isValid ? null : (self::$messages[$type] ?? 'Formato inválido')
            ];
        }

        return ['valid' => true];
    }

    /**
     * Valida múltiples campos según reglas
     * 
     * @param array $data Datos a validar ['campo' => 'valor']
     * @param array $rules Reglas ['campo' => 'tipo'] o ['campo' => ['type' => 'tipo', 'required' => true]]
     * @return array ['valid' => bool, 'errors' => ['campo' => 'mensaje']]
     */
    public static function validate($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            // Permitir reglas simples o complejas
            $type = is_array($rule) ? ($rule['type'] ?? null) : $rule;
            $required = is_array($rule) ? ($rule['required'] ?? true) : true;

            $value = $data[$field] ?? null;
            $result = self::validateField($value, $type, $required);

            if (!$result['valid']) {
                $errors[$field] = $result['message'];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valida y sanitiza un array de datos
     */
    public static function sanitize($data)
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            }
            return $value;
        }, $data);
    }

    /**
     * Valida que un valor numérico esté en un rango
     */
    public static function validateRange($value, $min, $max)
    {
        $num = floatval($value);
        return [
            'valid' => $num >= $min && $num <= $max,
            'message' => "El valor debe estar entre $min y $max"
        ];
    }

    /**
     * Valida formato de fecha
     */
    public static function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        $isValid = $d && $d->format($format) === $date;

        return [
            'valid' => $isValid,
            'message' => $isValid ? null : 'Formato de fecha inválido'
        ];
    }
    public static function validateTipoVenta($tipo)
    {
        $tipo = strtolower(trim($tipo ?? ''));
        if (!in_array($tipo, ['contado', 'credito'])) {
            return ['valid' => false, 'message' => 'El tipo de venta debe ser "contado" o "crédito"'];
        }
        return ['valid' => true];
    }

    public static function validateProductos($productos)
    {
        $productos = json_decode($productos, true);
        if (!$productos || !is_array($productos) || count($productos) === 0) {
            return ['valid' => false, 'message' => 'Debe agregar al menos un producto válido'];
        }

        foreach ($productos as $p) {
            if (empty($p['codigo_prenda']) || empty($p['precio_unitario'])) {
                return ['valid' => false, 'message' => 'Cada producto debe tener código y precio'];
            }
        }

        return ['valid' => true];
    }
}
