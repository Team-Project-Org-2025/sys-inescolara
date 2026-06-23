<?php

namespace SysInescolara\traits;

trait ValidationTrait
{

    private static array $patterns = [
        'nombre'          => '/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,50}$/',
        'nombreProducto'  => '/^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s\-\.\,]{1,50}$/',
        'cedula'          => '/^\d{7,10}$/',
        'telefono'        => '/^\d{11}$/',
        'rif'             => '/^[JGCVEPjgcvep]-?\d{8}-?\d$/',
        'cargo'           => '/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,50}$/',
        'email'           => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        'precio'          => '/^\d+(\.\d{1,2})?$/',
        'cantidad'        => '/^\d+$/',
        'referencia'      => '/^\d{8,10}$/',
        'referenciaVenta' => '/^[A-Za-z0-9\-]{1,15}$/',
        'password'        => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._-])[A-Za-z\d@$!%*?&._-]{8,30}$/',
    ];

    private static array $messages = [
        'nombre'          => 'Debe contener solo letras (2-50 caracteres)',
        'nombreProducto'  => 'Nombre inválido (1-50 caracteres)',
        'cedula'          => 'Cédula inválida (7-10 dígitos)',
        'telefono'        => 'Teléfono inválido (11 dígitos)',
        'rif'             => 'RIF inválido (formato: J-12345678-9)',
        'cargo'           => 'Cargo inválido (2-50 caracteres)',
        'email'           => 'Correo electrónico inválido',
        'precio'          => 'Precio inválido (formato: 0.00)',
        'cantidad'        => 'Debe ser un número entero positivo',
        'referencia'      => 'Referencia inválida (8-10 dígitos)',
        'referenciaVenta' => 'Referencia inválida (máx 15 caracteres)',
        'password'        => 'Debe tener 8-30 caracteres, mayúsculas, minúsculas, números y símbolos',
        'required'        => 'Este campo es requerido',
    ];

    protected function validateData(array $data): void
    {
        $errors = [];

        foreach ($this->validationRules as $field => $rule) {
            $type = is_array($rule) ? ($rule['type'] ?? null) : $rule;
            $required = is_array($rule) ? ($rule['required'] ?? true) : true;

            $value = $data[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;

            if ($required && ($value === null || $value === '')) {
                $errors[] = self::$messages['required'] . ": $field";
                continue;
            }

            if (!$required && ($value === null || $value === '')) {
                continue;
            }

            if ($type !== null && isset(self::$patterns[$type])) {
                if (!preg_match(self::$patterns[$type], (string)$value)) {
                    $errors[] = self::$messages[$type] ?? "Formato inválido: $field";
                }
            }

            if (is_string($value)) {
                $value = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode('; ', $errors));
        }
    }
}
