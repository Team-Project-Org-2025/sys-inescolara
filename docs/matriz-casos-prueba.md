# Matriz de Requisitos y Casos de Prueba — Suite PHPUnit

Matriz trazable entre requisitos (HU) y casos de prueba de las suites automatizadas
del proyecto SYSINECOLARA. Cada caso de prueba corresponde exactamente a un test
método de la suite (verificado con `--testdox`).

## Convenciones

| Campo | Criterio |
|---|---|
| **ID Requisito / HU** | `HU-<MOD>-<NN>`: Historia de Usuario del módulo |
| **ID Caso de Prueba** | `CP-<MOD>-<NNN>`: correlativo por módulo, en orden Unit → Integración → Humo |
| **Tipo de Pruebas** | `Unitaria` (modelo sin BD) · `Integración` (modelo + SQLite en memoria) · `Humo` (controlador real en subproceso + assets) |
| **Resultado Esperado** | Lo que el test verifica (mensaje exacto, código HTTP o estado de datos) |

## Comandos de ejecución

```bash
composer test                 # Suite completa (120 tests)
composer test:plantas         # Módulo Plantas   (36 tests)
composer test:especies        # Módulo Especies  (39 tests)
composer test:ubicaciones     # Módulo Ubicaciones (44 tests)
composer test:unit            # Solo unitarias
composer test:integration     # Solo integración
composer test:smoke           # Solo humo
```

Estado al momento de la elaboración: **OK (120 tests, 621 assertions)**.

---

## Infraestructura

| ID Req | Descripción del Requisito | ID CP | Tipo | Descripción del Caso de Prueba | Resultado Esperado |
|---|---|---|---|---|---|
| HU-GEN-01 | La suite debe poder autoloadar las clases del proyecto sin errores | CP-GEN-001 | Unitaria | Verificar que las clases del proyecto se autoloadean correctamente | Todas las clases del namespace del proyecto se resuelven sin error |

---

## Módulo Plantas (36 casos)

**Requisitos del módulo:**

| ID | Descripción del Requisito |
|---|---|
| HU-PLA-01 | Consultar el catálogo de plantas activas con especie, stock y precio vigente para la tabla del dashboard |
| HU-PLA-02 | Registrar una planta nueva con nombre común válido y especie asignada |
| HU-PLA-03 | Editar los datos de una planta existente |
| HU-PLA-04 | Desactivar y restaurar una planta mediante borrado lógico |
| HU-PLA-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX |
| HU-PLA-06 | Validar los datos de entrada de la planta antes de persistir en la base de datos |
| HU-PLA-07 | Publicar la vista y el JavaScript con los componentes y endpoints del módulo |
| HU-PLA-08 | Degradar de forma controlada ante fallos de base de datos |

| ID Req | Descripción del Requisito | ID CP | Tipo | Descripción del Caso de Prueba | Resultado Esperado |
|---|---|---|---|---|---|
| HU-PLA-02 | Registrar una planta nueva con nombre común válido y especie asignada | CP-PLA-001 | Unitaria | Verificar los valores iniciales de una instancia nueva de Planta | `id` null, `nombre_comun` '', `cantidad_total` 0, `activo` true |
| HU-PLA-02 | Registrar una planta nueva con nombre común válido y especie asignada | CP-PLA-002 | Unitaria | Verificar que los setters encadenan y normalizan espacios | Setters devuelven `$this`; valores con espacios quedan con `trim()` aplicado |
| HU-PLA-02 | Registrar una planta nueva con nombre común válido y especie asignada | CP-PLA-003 | Unitaria | Verificar que una cantidad negativa se ancla en cero | `setCantidadTotal(-5)` deja `cantidad_total = 0` |
| HU-PLA-02 | Registrar una planta nueva con nombre común válido y especie asignada | CP-PLA-004 | Unitaria | Verificar la conversión de bool a entero en `activo` | `true` → 1, `false` → 0 |
| HU-PLA-02 | Registrar una planta nueva con nombre común válido y especie asignada | CP-PLA-005 | Unitaria | Verificar que `fill()` ignora campos no fillable | `id`, `id_planta` y claves desconocidas no alteran la instancia |
| HU-PLA-02 | Registrar una planta nueva con nombre común válido y especie asignada | CP-PLA-006 | Unitaria | Verificar que `fill()` mapea columnas del modelo a propiedades | Los getters devuelven los valores enviados por clave de columna |
| HU-PLA-06 | Validar los datos de entrada de la planta antes de persistir | CP-PLA-007 | Unitaria | Intentar guardar planta sin nombre común | `InvalidArgumentException`: "Este campo es requerido: nombre_comun" |
| HU-PLA-06 | Validar los datos de entrada de la planta antes de persistir | CP-PLA-008 | Unitaria | Intentar guardar nombre común demasiado corto | `InvalidArgumentException`: "Debe contener solo letras (2-50 caracteres)" |
| HU-PLA-06 | Validar los datos de entrada de la planta antes de persistir | CP-PLA-009 | Unitaria | Intentar guardar nombre común con dígitos | `InvalidArgumentException`: "Debe contener solo letras (2-50 caracteres)" |
| HU-PLA-06 | Validar los datos de entrada de la planta antes de persistir | CP-PLA-010 | Unitaria | Intentar guardar planta sin especie asignada | `InvalidArgumentException`: "Este campo es requerido: id_especie" |
| HU-PLA-06 | Validar los datos de entrada de la planta antes de persistir | CP-PLA-011 | Unitaria | Intentar guardar nombre técnico con caracteres inválidos | `InvalidArgumentException`: "Debe contener solo letras (2-50 caracteres)" |
| HU-PLA-08 | Degradar de forma controlada ante fallos de base de datos | CP-PLA-012 | Unitaria | Guardar con datos válidos sin conexión a BD | `save()` devuelve `false` y registra el error con `error_log` (no lanza excepción) |
| HU-PLA-02 | Registrar una planta nueva con nombre común válido y especie asignada | CP-PLA-013 | Integración | Guardar planta nueva con especie válida | `save()` true, `id` asignado y `find()` recupera la fila |
| HU-PLA-03 | Editar los datos de una planta existente | CP-PLA-014 | Integración | Actualizar nombre, cantidad e imagen de una planta | `save()` true y los cambios persisten |
| HU-PLA-01 | Consultar el catálogo de plantas activas con especie, stock y precio vigente | CP-PLA-015 | Integración | `getAll()` devuelve solo plantas activas ordenadas | Solo filas `activo = 1`, ordenadas por nombre común |
| HU-PLA-01 | Consultar el catálogo de plantas activas con especie, stock y precio vigente | CP-PLA-016 | Integración | `getAll()` incluye especie, stock y precio vigente | Claves `especie_nombre`, `stock`, `precio_vigente` presentes y calculadas |
| HU-PLA-01 | Consultar el catálogo de plantas activas con especie, stock y precio vigente | CP-PLA-017 | Integración | `getAll()` con especie desactivada | `especie_nombre` queda en `null` (JOIN con `e.activo = 1`) |
| HU-PLA-01 | Consultar el catálogo de plantas activas con especie, stock y precio vigente | CP-PLA-018 | Integración | `getById()` con id existente e inexistente | Devuelve la fila con el join; `null` si no existe |
| HU-PLA-04 | Desactivar y restaurar una planta mediante borrado lógico | CP-PLA-019 | Integración | `exists()` refleja el registro de la planta | `true` para id existente, `false` para id inexistente |
| HU-PLA-04 | Desactivar y restaurar una planta mediante borrado lógico | CP-PLA-020 | Integración | `delete()` lógico y posterior `restore()` | Tras delete: `activo = 0` y excluida de `getAll()`; tras restore: `activo = 1` y visible |
| HU-PLA-01 | Consultar el catálogo de plantas activas con especie, stock y precio vigente | CP-PLA-021 | Integración | `where()` filtra por columna solo entre activas | Solo instancias que coinciden y están activas |
| HU-PLA-02 | Registrar una planta nueva con nombre común válido y especie asignada | CP-PLA-022 | Integración | Guardar planta con especie inexistente (violación de FK) | `save()` devuelve `false` y registra el error con `error_log` |
| HU-PLA-03 | Editar los datos de una planta existente | CP-PLA-023 | Integración | `loadById()` carga los atributos en la instancia | `true` con atributos copiados; `false` si el id no existe |
| HU-PLA-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-PLA-024 | Humo | Cargar el controlador sin ejecutar `index()` | Las 8 funciones del módulo están definidas, sin errores fatales |
| HU-PLA-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-PLA-025 | Humo | Acceso AJAX sin sesión | HTTP 401: `{"success":false,"message":"No autorizado","redirect":"…/login"}` |
| HU-PLA-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-PLA-026 | Humo | Acceso web sin sesión | HTTP 302 con cuerpo vacío (redirect a login) |
| HU-PLA-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-PLA-027 | Humo | Acción AJAX inexistente | HTTP 400: "Acción AJAX inválida" |
| HU-PLA-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-PLA-028 | Humo | Método HTTP no permitido para la acción | HTTP 400: "Acción AJAX inválida" |
| HU-PLA-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-PLA-029 | Humo | Usuario autenticado sin permiso `plantas:crear` | HTTP 403: "No tienes permiso para realizar esta acción." |
| HU-PLA-06 | Validar los datos de entrada de la planta antes de persistir | CP-PLA-030 | Humo | `add_ajax` sin nombre común | HTTP 500: "El nombre común es requerido." |
| HU-PLA-06 | Validar los datos de entrada de la planta antes de persistir | CP-PLA-031 | Humo | `edit_ajax` sin nombre común | HTTP 500: "El nombre común es requerido." |
| HU-PLA-03 | Editar los datos de una planta existente | CP-PLA-032 | Humo | `edit_ajax` con id inválido (0) | HTTP 500: "ID inválido" |
| HU-PLA-04 | Desactivar y restaurar una planta mediante borrado lógico | CP-PLA-033 | Humo | `delete_ajax` sin id | HTTP 500: "ID inválido" |
| HU-PLA-01 | Consultar el catálogo de plantas activas con especie, stock y precio vigente | CP-PLA-034 | Humo | `get_plants` con sesión de administrador (requiere MySQL) | HTTP 200: `{"success":true,"plantas":[…],"count":N}` con estructura consistente |
| HU-PLA-07 | Publicar la vista y el JavaScript con los componentes y endpoints del módulo | CP-PLA-035 | Humo | Verificar la vista `plantas.php` | Existen `plantasTable`, `btnAddPlant`, `nombre_comun`, `editPlantName` |
| HU-PLA-07 | Publicar la vista y el JavaScript con los componentes y endpoints del módulo | CP-PLA-036 | Humo | Verificar `plantas.js` | Declara `get_plants`, `add_ajax`, `edit_ajax` y `delete_ajax` |

---

## Módulo Especies (39 casos)

**Requisitos del módulo:**

| ID | Descripción del Requisito |
|---|---|
| HU-ESP-01 | Consultar el catálogo de especies activas ordenado para la tabla del dashboard |
| HU-ESP-02 | Registrar una especie nueva con nombre válido |
| HU-ESP-03 | Editar los datos de una especie existente |
| HU-ESP-04 | Desactivar y restaurar una especie mediante borrado lógico |
| HU-ESP-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX |
| HU-ESP-06 | Validar los datos de entrada de la especie antes de persistir en la base de datos |
| HU-ESP-07 | Publicar la vista y el JavaScript con los componentes y endpoints del módulo |
| HU-ESP-08 | Degradar de forma controlada ante fallos de base de datos |

| ID Req | Descripción del Requisito | ID CP | Tipo | Descripción del Caso de Prueba | Resultado Esperado |
|---|---|---|---|---|---|
| HU-ESP-02 | Registrar una especie nueva con nombre válido | CP-ESP-001 | Unitaria | Verificar los valores iniciales de una instancia nueva de Especie | `id` null, `nombre_especie` '', `descripcion` null, `activo` true |
| HU-ESP-02 | Registrar una especie nueva con nombre válido | CP-ESP-002 | Unitaria | Verificar que los setters encadenan y normalizan espacios | Setters devuelven `$this`; `trim()` aplicado; vacío en `descripcion` queda null |
| HU-ESP-02 | Registrar una especie nueva con nombre válido | CP-ESP-003 | Unitaria | Verificar la conversión de bool a entero en `activo` | `true` → 1, `false` → 0 |
| HU-ESP-02 | Registrar una especie nueva con nombre válido | CP-ESP-004 | Unitaria | Verificar que `fill()` ignora campos no fillable | `id`, `id_especie` y claves desconocidas no alteran la instancia |
| HU-ESP-02 | Registrar una especie nueva con nombre válido | CP-ESP-005 | Unitaria | Verificar que `fill()` mapea columnas del modelo a propiedades | Los getters devuelven los valores enviados por clave de columna |
| HU-ESP-06 | Validar los datos de entrada de la especie antes de persistir | CP-ESP-006 | Unitaria | Intentar guardar especie sin nombre | `InvalidArgumentException`: "Este campo es requerido: nombre_especie" |
| HU-ESP-06 | Validar los datos de entrada de la especie antes de persistir | CP-ESP-007 | Unitaria | Intentar guardar nombre demasiado corto | `InvalidArgumentException`: "Debe contener solo letras (2-50 caracteres)" |
| HU-ESP-06 | Validar los datos de entrada de la especie antes de persistir | CP-ESP-008 | Unitaria | Intentar guardar nombre con dígitos | `InvalidArgumentException`: "Debe contener solo letras (2-50 caracteres)" |
| HU-ESP-02 | Registrar una especie nueva con nombre válido | CP-ESP-009 | Unitaria | Guardar nombre en el límite mínimo (2 caracteres) | Pasa la validación y llega a la capa de BD (sin excepción de validación) |
| HU-ESP-06 | Validar los datos de entrada de la especie antes de persistir | CP-ESP-010 | Unitaria | Guardar descripción con símbolos y números | No hay patrón de validación: pasa sin excepción (texto libre) |
| HU-ESP-08 | Degradar de forma controlada ante fallos de base de datos | CP-ESP-011 | Unitaria | Guardar con datos válidos sin conexión a BD | `RuntimeException`: "Base de datos no disponible en tests unitarios" (`Especie::save()` no captura errores) |
| HU-ESP-02 | Registrar una especie nueva con nombre válido | CP-ESP-012 | Integración | Guardar especie nueva | `save()` true, `id` asignado, `find()` recupera nombre, descripción y `activo` |
| HU-ESP-03 | Editar los datos de una especie existente | CP-ESP-013 | Integración | Actualizar nombre y descripción de una especie | `save()` true y los cambios persisten |
| HU-ESP-03 | Editar los datos de una especie existente | CP-ESP-014 | Integración | Actualizar dejando `descripcion` en null | El campo queda `NULL` en la base de datos |
| HU-ESP-01 | Consultar el catálogo de especies activas ordenado | CP-ESP-015 | Integración | `getAll()` devuelve solo activas ordenadas por nombre | Solo filas activas en orden alfabético; las desactivadas quedan fuera |
| HU-ESP-01 | Consultar el catálogo de especies activas ordenado | CP-ESP-016 | Integración | `getAll()` devuelve arrays con claves de DataTables | Claves exactas: `id`, `nombre_especie`, `descripcion`, `activo` |
| HU-ESP-01 | Consultar el catálogo de especies activas ordenado | CP-ESP-017 | Integración | `all()` devuelve instancias del modelo | Cada elemento es instancia de `Especie` con getters funcionales |
| HU-ESP-01 | Consultar el catálogo de especies activas ordenado | CP-ESP-018 | Integración | `getById()` con id existente e inexistente | Devuelve el array de la fila; `null` si no existe |
| HU-ESP-04 | Desactivar y restaurar una especie mediante borrado lógico | CP-ESP-019 | Integración | `exists()` refleja el registro de la especie | `true` para id existente, `false` para id inexistente |
| HU-ESP-04 | Desactivar y restaurar una especie mediante borrado lógico | CP-ESP-020 | Integración | `delete()` lógico y posterior `restore()` | Tras delete: `activo = 0`, fuera de `getAll()`, `exists()` sigue true; tras restore: visible |
| HU-ESP-01 | Consultar el catálogo de especies activas ordenado | CP-ESP-021 | Integración | `where()` filtra por columna solo entre activas | Solo instancias que coinciden y están activas |
| HU-ESP-04 | Desactivar y restaurar una especie mediante borrado lógico | CP-ESP-022 | Integración | Desactivar especie que tiene plantas asociadas | El soft delete no viola la FK; el JOIN de plantas deja `especie_nombre` en null |
| HU-ESP-08 | Degradar de forma controlada ante fallos de base de datos | CP-ESP-023 | Integración | `getAll()` con la tabla eliminada | Devuelve `[]` y registra el error con `error_log` (método con try/catch) |
| HU-ESP-08 | Degradar de forma controlada ante fallos de base de datos | CP-ESP-024 | Integración | `save()` con la tabla eliminada | Propaga `PDOException` (`Especie::save()` no tiene try/catch) |
| HU-ESP-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-ESP-025 | Humo | Cargar el controlador sin ejecutar `index()` | Las 8 funciones del módulo están definidas, sin errores fatales |
| HU-ESP-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-ESP-026 | Humo | Acceso AJAX sin sesión | HTTP 401: `{"success":false,"message":"No autorizado","redirect":"…/login"}` |
| HU-ESP-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-ESP-027 | Humo | Acceso web sin sesión | HTTP 302 con cuerpo vacío (redirect a login) |
| HU-ESP-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-ESP-028 | Humo | Acción AJAX inexistente | HTTP 400: "Acción AJAX inválida" |
| HU-ESP-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-ESP-029 | Humo | Método HTTP no permitido para la acción | HTTP 400: "Acción AJAX inválida" |
| HU-ESP-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-ESP-030 | Humo | Usuario autenticado sin permiso `especies:crear` | HTTP 403: "No tienes permiso para realizar esta acción." |
| HU-ESP-06 | Validar los datos de entrada de la especie antes de persistir | CP-ESP-031 | Humo | `add_ajax` sin nombre | HTTP 500: "El nombre de la especie es requerido." |
| HU-ESP-06 | Validar los datos de entrada de la especie antes de persistir | CP-ESP-032 | Humo | `edit_ajax` sin nombre | HTTP 500: "El nombre de la especie es requerido." |
| HU-ESP-03 | Editar los datos de una especie existente | CP-ESP-033 | Humo | `edit_ajax` con id inválido (0) | HTTP 500: "ID inválido" |
| HU-ESP-04 | Desactivar y restaurar una especie mediante borrado lógico | CP-ESP-034 | Humo | `delete_ajax` sin id | HTTP 500: "ID inválido" |
| HU-ESP-03 | Editar los datos de una especie existente | CP-ESP-035 | Humo | `edit_ajax` con id inexistente (requiere MySQL) | HTTP 500: "No existe la especie" |
| HU-ESP-04 | Desactivar y restaurar una especie mediante borrado lógico | CP-ESP-036 | Humo | `delete_ajax` con id inexistente (requiere MySQL) | HTTP 500: "No existe la especie" |
| HU-ESP-01 | Consultar el catálogo de especies activas ordenado | CP-ESP-037 | Humo | `get_species` con sesión de administrador (requiere MySQL) | HTTP 200: `{"success":true,"especies":[…],"count":N}` con estructura consistente |
| HU-ESP-07 | Publicar la vista y el JavaScript con los componentes y endpoints del módulo | CP-ESP-038 | Humo | Verificar la vista `especies.php` | Existen `especiesTable`, `btnAddSpecies`, `nombre_especie`, `descripcion`, `editSpeciesName` |
| HU-ESP-07 | Publicar la vista y el JavaScript con los componentes y endpoints del módulo | CP-ESP-039 | Humo | Verificar `especies.js` | Declara `get_species`, `add_ajax`, `edit_ajax` y `delete_ajax` |

---

## Módulo Ubicaciones (44 casos)

**Requisitos del módulo:**

| ID | Descripción del Requisito |
|---|---|
| HU-UBI-01 | Consultar el catálogo de ubicaciones activas con su tipo para la tabla del dashboard |
| HU-UBI-02 | Registrar una ubicación nueva con nombre válido |
| HU-UBI-03 | Editar los datos de una ubicación existente |
| HU-UBI-04 | Desactivar y restaurar una ubicación, bloqueando la desactivación si tiene lotes activos |
| HU-UBI-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX |
| HU-UBI-06 | Validar los datos de entrada de la ubicación antes de persistir en la base de datos |
| HU-UBI-07 | Publicar la vista y el JavaScript con los componentes y endpoints del módulo |
| HU-UBI-08 | Degradar de forma controlada ante fallos de base de datos |

| ID Req | Descripción del Requisito | ID CP | Tipo | Descripción del Caso de Prueba | Resultado Esperado |
|---|---|---|---|---|---|
| HU-UBI-02 | Registrar una ubicación nueva con nombre válido | CP-UBI-001 | Unitaria | Verificar los valores iniciales de una instancia nueva de Ubicación | `id` null, `nombre_ubicacion` '', `descripcion` null, `tipo` null, `activo` true |
| HU-UBI-02 | Registrar una ubicación nueva con nombre válido | CP-UBI-002 | Unitaria | Verificar que los setters encadenan y normalizan espacios | Setters devuelven `$this`; `setDescripcion` vacío → null; `setTipo` solo-trim → `''` (comportamiento documentado) |
| HU-UBI-02 | Registrar una ubicación nueva con nombre válido | CP-UBI-003 | Unitaria | Verificar la conversión de bool a entero en `activo` | `true` → 1, `false` → 0 |
| HU-UBI-02 | Registrar una ubicación nueva con nombre válido | CP-UBI-004 | Unitaria | Verificar que `fill()` ignora campos no fillable | `id`, `id_ubicacion` y claves desconocidas no alteran la instancia |
| HU-UBI-02 | Registrar una ubicación nueva con nombre válido | CP-UBI-005 | Unitaria | Verificar que `fill()` mapea columnas del modelo a propiedades | Los getters devuelven los valores enviados por clave de columna |
| HU-UBI-06 | Validar los datos de entrada de la ubicación antes de persistir | CP-UBI-006 | Unitaria | Intentar guardar ubicación sin nombre | `InvalidArgumentException`: "Este campo es requerido: nombre_ubicacion" |
| HU-UBI-06 | Validar los datos de entrada de la ubicación antes de persistir | CP-UBI-007 | Unitaria | Intentar guardar nombre demasiado corto | `InvalidArgumentException`: "Debe contener solo letras (2-50 caracteres)" |
| HU-UBI-06 | Validar los datos de entrada de la ubicación antes de persistir | CP-UBI-008 | Unitaria | Intentar guardar nombre con dígitos | `InvalidArgumentException`: "Debe contener solo letras (2-50 caracteres)" |
| HU-UBI-02 | Registrar una ubicación nueva con nombre válido | CP-UBI-009 | Unitaria | Guardar nombre en el límite mínimo (2 caracteres) | Pasa la validación; `save()` devuelve `false` por falta de BD y registra `error_log` |
| HU-UBI-06 | Validar los datos de entrada de la ubicación antes de persistir | CP-UBI-010 | Unitaria | Guardar descripción con símbolos y números | No hay patrón de validación: pasa sin excepción (texto libre) |
| HU-UBI-08 | Degradar de forma controlada ante fallos de base de datos | CP-UBI-011 | Unitaria | Guardar con datos válidos sin conexión a BD | `save()` devuelve `false` y registra el error con `error_log` (captura el Throwable) |
| HU-UBI-02 | Registrar una ubicación nueva con nombre válido | CP-UBI-012 | Integración | Guardar ubicación nueva | `save()` true, `id` asignado, `find()` recupera datos y la zona persistió en la columna real |
| HU-UBI-01 | Consultar el catálogo de ubicaciones activas con su tipo | CP-UBI-013 | Integración | `find()` con columna `zona` no fillable | `getTipo()` queda null (comportamiento documentado); `all()` sí aliasa `zona AS tipo` |
| HU-UBI-03 | Editar los datos de una ubicación existente | CP-UBI-014 | Integración | Actualizar nombre, descripción y tipo | `save()` true y los cambios persisten en `nombre_ubicacion`, `descripcion` y `zona` |
| HU-UBI-03 | Editar los datos de una ubicación existente | CP-UBI-015 | Integración | Actualizar dejando `descripcion` en null | El campo queda `NULL` en la base de datos |
| HU-UBI-03 | Editar los datos de una ubicación existente | CP-UBI-016 | Integración | Actualizar dejando `tipo` en null | La columna `zona` queda `NULL` en la base de datos |
| HU-UBI-01 | Consultar el catálogo de ubicaciones activas con su tipo | CP-UBI-017 | Integración | `getAll()` devuelve solo activas ordenadas por nombre | Solo filas `activo = 1`, ordenadas alfabéticamente |
| HU-UBI-01 | Consultar el catálogo de ubicaciones activas con su tipo | CP-UBI-018 | Integración | `getAll()` devuelve arrays con claves de DataTables | Claves exactas: `id`, `nombre_ubicacion`, `descripcion`, `tipo`, `activo` |
| HU-UBI-01 | Consultar el catálogo de ubicaciones activas con su tipo | CP-UBI-019 | Integración | `all()` devuelve filas con `id` y `tipo` aliasados | `id_ubicacion AS id` y `zona AS tipo` disponibles en el array |
| HU-UBI-01 | Consultar el catálogo de ubicaciones activas con su tipo | CP-UBI-020 | Integración | `getById()` con id existente e inexistente | Devuelve la fila con `tipo` aliasado; `null` si no existe |
| HU-UBI-04 | Desactivar y restaurar una ubicación, bloqueando si tiene lotes activos | CP-UBI-021 | Integración | `exists()` refleja el registro de la ubicación | `true` para id existente, `false` para id inexistente |
| HU-UBI-04 | Desactivar y restaurar una ubicación, bloqueando si tiene lotes activos | CP-UBI-022 | Integración | `delete()` lógico y posterior `restore()` | Tras delete: `activo = 0` y fuera de `getAll()`; tras restore: visible |
| HU-UBI-04 | Desactivar y restaurar una ubicación, bloqueando si tiene lotes activos | CP-UBI-023 | Integración | `delete()` con lote activo vinculado | `Exception`: "No se puede desactivar la ubicación: Existen lotes vinculados en el inventario activo." |
| HU-UBI-04 | Desactivar y restaurar una ubicación, bloqueando si tiene lotes activos | CP-UBI-024 | Integración | `delete()` con lote inactivo vinculado | La desactivación procede: `save()` true y `activo = 0` |
| HU-UBI-01 | Consultar el catálogo de ubicaciones activas con su tipo | CP-UBI-025 | Integración | `where()` filtra por columna solo entre activas | Solo instancias que coinciden y están activas |
| HU-UBI-01 | Consultar el catálogo de ubicaciones activas con su tipo | CP-UBI-026 | Integración | `getByTipo()` filtra por la columna `zona` | Solo ubicaciones con ese tipo; `tipo` aliasado correcto en el resultado |
| HU-UBI-03 | Editar los datos de una ubicación existente | CP-UBI-027 | Integración | `loadById()` carga los atributos en la instancia | `true` con atributos copiados; `false` si el id no existe |
| HU-UBI-08 | Degradar de forma controlada ante fallos de base de datos | CP-UBI-028 | Integración | `save()` con la tabla eliminada | `save()` devuelve `false` y registra el error con `error_log` |
| HU-UBI-08 | Degradar de forma controlada ante fallos de base de datos | CP-UBI-029 | Integración | `getAll()` con la tabla eliminada | Propaga `PDOException` (`all()` no tiene try/catch) |
| HU-UBI-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-UBI-030 | Humo | Cargar el controlador sin ejecutar `index()` | Las 8 funciones del módulo están definidas, sin errores fatales |
| HU-UBI-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-UBI-031 | Humo | Acceso AJAX sin sesión | HTTP 401: `{"success":false,"message":"No autorizado","redirect":"…/login"}` |
| HU-UBI-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-UBI-032 | Humo | Acceso web sin sesión | HTTP 302 con cuerpo vacío (redirect a login) |
| HU-UBI-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-UBI-033 | Humo | Acción AJAX inexistente | HTTP 400: "Acción AJAX inválida" |
| HU-UBI-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-UBI-034 | Humo | Método HTTP no permitido para la acción | HTTP 400: "Acción AJAX inválida" |
| HU-UBI-05 | Proteger el módulo con sesión, permisos y enrutamiento correcto de acciones AJAX | CP-UBI-035 | Humo | Usuario autenticado sin permiso `ubicaciones:crear` | HTTP 403: "No tienes permiso para realizar esta acción." |
| HU-UBI-06 | Validar los datos de entrada de la ubicación antes de persistir | CP-UBI-036 | Humo | `add_ajax` sin nombre | HTTP 500: "El nombre de la ubicación es requerido." |
| HU-UBI-06 | Validar los datos de entrada de la ubicación antes de persistir | CP-UBI-037 | Humo | `edit_ajax` sin nombre | HTTP 500: "El nombre de la ubicación es requerido." |
| HU-UBI-03 | Editar los datos de una ubicación existente | CP-UBI-038 | Humo | `edit_ajax` con id inválido (0) | HTTP 500: "ID inválido" |
| HU-UBI-04 | Desactivar y restaurar una ubicación, bloqueando si tiene lotes activos | CP-UBI-039 | Humo | `delete_ajax` sin id | HTTP 500: "ID de ubicación inválido" |
| HU-UBI-03 | Editar los datos de una ubicación existente | CP-UBI-040 | Humo | `edit_ajax` con id inexistente (requiere MySQL) | HTTP 500: "La ubicación que intenta editar no existe." |
| HU-UBI-04 | Desactivar y restaurar una ubicación, bloqueando si tiene lotes activos | CP-UBI-041 | Humo | `delete_ajax` con id inexistente (requiere MySQL) | HTTP 500: "No existe la ubicación solicitada." |
| HU-UBI-01 | Consultar el catálogo de ubicaciones activas con su tipo | CP-UBI-042 | Humo | `get_locations` con sesión de administrador (requiere MySQL) | HTTP 200: `{"success":true,"ubicaciones":[…],"count":N}` con estructura consistente |
| HU-UBI-07 | Publicar la vista y el JavaScript con los componentes y endpoints del módulo | CP-UBI-043 | Humo | Verificar la vista `ubicaciones.php` | Existen `ubicacionesTable`, `btnAddLocation`, `nombre_ubicacion`, `descripcion`, `tipo`, `editLocationName` |
| HU-UBI-07 | Publicar la vista y el JavaScript con los componentes y endpoints del módulo | CP-UBI-044 | Humo | Verificar `ubicaciones.js` | Declara `get_locations`, `add_ajax`, `edit_ajax` y `delete_ajax` |

---

## Resumen de cobertura

| Módulo | Unitaria | Integración | Humo | Total |
|---|---|---|---|---|
| Infraestructura | 1 | 0 | 0 | 1 |
| Plantas | 12 | 11 | 13 | 36 |
| Especies | 11 | 13 | 15 | 39 |
| Ubicaciones | 11 | 18 | 15 | 44 |
| **Total** | **35** | **42** | **43** | **120** |

**Tipos de prueba aplicados:**

- **Unitaria** — modelo aislado sin BD (dobles `NoDb*`): validaciones, setters, fill, degradación controlada.
- **Integración** — modelo real contra SQLite en memoria (dobles `Sqlite*`) con el SQL del modelo: CRUD, soft delete, integridad referencial, comportamiento ante tablas ausentes.
- **Humo** — controlador real en subproceso (`tests/Support/*_harness.php`): rutas HTTP, sesión, permisos, mensajes exactos de error, más verificación estática de vista y JavaScript.
