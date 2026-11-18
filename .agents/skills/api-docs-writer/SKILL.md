---
name: api-docs-writer
description: Documenta endpoints del backend PHP nativo de SYS INESCOLARA. Úsala cuando haya que escribir o actualizar fichas de API por módulo (método, ruta, parámetros, permisos, respuestas reales, efectos secundarios), mantener docs/api/ o cruzar la documentación con REQUISITOS.md y las colecciones Postman.
license: MIT
metadata:
  domain: documentation
  scope: api-contract
  stack: PHP 8.2 · MariaDB
---

# API Docs Writer · SYS INESCOLARA

Produce documentación **verificada**, no inferida. Cada ficha debe poder
reproducirse con un request real.

## Flujo obligatorio por endpoint

1. **Localiza el controlador**: `app/controllers/{Modulo}Controller.php`.
2. **Identifica el dispatcher**:
   - `match ($_SERVER['REQUEST_METHOD'] . '_' . $action)`
   - o una función por segmento (`/notifications/get_unread`).
3. **Lee el handler completo**: parámetros que lee (`$_POST`, `$_GET`,
   `getRequestData()`), validaciones y `throw`.
4. **Lee el modelo**: tabla, columnas, `getAll()` y efectos (`AuditLog`,
   actualización de stock, soft delete).
5. **Lee el JS**: `public/assets/js/dashboard/{modulo}.js` confirma el nombre
   real del parámetro y el envelope esperado.
6. **Ejecuta el request** contra local y **captura la respuesta real**.
7. **Escribe la ficha** con la plantilla.
8. **Enlaza el caso Postman** correspondiente.

Si no puedes ejecutar el request, marca la ficha como `> ⚠️ No verificado` y
explica por qué. Nunca presentes una respuesta inventada como real.

## Estructura de `docs/api/`

```
docs/api/
├── README.md
├── 00-convenciones.md
├── 01-autenticacion.md
├── inventario/   comercial/   compras/
├── operaciones/  sistema/     publico/
└── _cobertura.md
```

Un archivo por módulo. Nombre en kebab-case (`unidades-medida.md`). Dentro:

1. Encabezado con propósito del módulo y tabla de permisos.
2. Índice de endpoints.
3. Fichas en el orden: vista → listado → crear → editar → eliminar → auxiliares.
4. Sección final de deuda/observaciones.

## Reglas

- **Una ficha = una acción.** No agrupes `add_ajax` y `edit_ajax` en una tabla.
- Documenta el **nombre exacto** del parámetro: `action` vs `accion`.
- Indica el **content-type** real (`multipart` si sube archivos).
- Incluye el permiso (`modulo:accion`) o `Solo sesión`.
- Copia la respuesta **recortada** (2–5 líneas) pero fiel.
- Documenta códigos de error **reales**, no los ideales.
- Los efectos secundarios son parte del contrato: auditoría, stock,
  notificaciones, borrado lógico.
- Si el comportamiento actual es un bug, documéntalo en `Notas` y no lo
  corrijas en el mismo cambio.
- Sin emojis en el cuerpo de la ficha; solo en las alertas de estado.

## Plantilla

[`reference/endpoint-template.md`](reference/endpoint-template.md)

## Checklist de cierre

[`reference/quality-checklist.md`](reference/quality-checklist.md)

## Integración con el resto del repo

| Fuente | Uso |
|---|---|
| `REQUISITOS.md` | Trazar RF ↔ endpoint |
| `pruebas/collections/` | Casos ejecutables |
| `pruebas/TRACEABILITY.md` | Estado de cobertura |
| `docs/api/_cobertura.md` | Matriz consolidada |
| `SPEC.md` | Contexto de arquitectura (puede estar desactualizado) |

No confíes en `SPEC.md` ni `README.md` para nombres de tablas o rutas: el
código manda.
