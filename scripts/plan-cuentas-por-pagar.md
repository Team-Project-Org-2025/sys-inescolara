# Plan — Módulo Cuentas por Pagar

> Rama separada para implementación futura.
> El módulo de Compras ya está preparado para integrarse.

---

## Tablas

### cuentas_pagar
```sql
CREATE TABLE cuentas_pagar (
    id_cuenta_pagar INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    monto_total DECIMAL(10,2) NOT NULL,
    saldo_pendiente DECIMAL(10,2) NOT NULL,
    fecha_vencimiento DATE DEFAULT NULL,
    estado ENUM('pendiente','parcial','pagada','vencida') NOT NULL DEFAULT 'pendiente',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_cp_compra (id_compra),
    CONSTRAINT fk_cp_compra FOREIGN KEY (id_compra) REFERENCES compra(id_compra)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### pago_compra
```sql
CREATE TABLE pago_compra (
    id_pago_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_cuenta_pagar INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    tipo_pago VARCHAR(30) DEFAULT NULL,
    referencia VARCHAR(50) DEFAULT NULL,
    fecha_pago DATE NOT NULL,
    estado ENUM('pendiente','confirmado','anulado') NOT NULL DEFAULT 'pendiente',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pg_cuenta (id_cuenta_pagar),
    CONSTRAINT fk_pg_cuenta FOREIGN KEY (id_cuenta_pagar) REFERENCES cuentas_pagar(id_cuenta_pagar)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Permisos nuevos
```
CUENTAS_VIEW
CUENTAS_CREATE (pagar)
CUENTAS_DELETE (anular pago)
```

---

## Flujo completo

| Paso | Acción | Compra | Cuenta por pagar |
|---|---|---|---|
| 1 | Crear compra | `pendiente` | — |
| 2 | Recibir stock (ya existe) | `recibida` | Se crea `pendiente` |
| 3 | Pagar parcial | `recibida` | `parcial` |
| 4 | Pagar resto | `pagada` | `pagada` |
| 5 | Cancelar | `cancelada` | `cancelada` |

**Nota:** El pago puede ocurrir antes de recibir (prepago) o después (crédito).  
Si se paga antes de recibir, `cuentas_pagar.estado` puede pasar a `pagada` aunque la compra siga `pendiente`.

---

## Lo que ya está listo en Compras

- `compra.estado` acepta `pendiente`, `recibida`, `cancelada` (y futuramente `pagada`)
- `compra.fecha_recepcion` columna lista
- `aplicarStock()` solo actualiza stock/lotes — **no** cambia el estado
- `marcarRecibida()` setea `recibida` + `fecha_recepcion`

---

## Archivos a crear (rama nueva)

| Archivo | Propósito |
|---|---|
| `app/models/CuentaPagar.php` | Modelo CRUD + lógica de pagos |
| `app/models/PagoCompra.php` | Modelo para pagos individuales |
| `app/controllers/CuentasPagarController.php` | Controlador |
| `app/views/dashboard/cuentas-pagar.php` | Vista |
| `public/assets/js/dashboard/cuentas-pagar.js` | JS |
| `scripts/add-cuentas-pagar-module.sql` | Migración BD + permisos |
| `app/views/partials/sidebar.php` | Agregar entrada al menú |

### Integración con Compras

- En el detalle de compra (`obtener_detalles`), agregar sección que muestre la cuenta por pagar y sus pagos
- Botón "Registrar Pago" en el detalle de compra cuando exista una cuenta pendiente
- Actualizar `compras.js` para mostrar estado `pagada` y los nuevos botones
