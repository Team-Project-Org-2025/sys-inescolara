---
name: phpunit-tests
description: Ejecuta y escribe pruebas PHPUnit en sys-inescolara — suites Unit, Integration y Smoke (unitarias, integración y humo) con convenciones del proyecto
metadata:
  workflow: testing
---

## Qué hace esta skill

Gestionar las pruebas PHPUnit del proyecto (PHP 8.2, PHPUnit 12.5, MVC nativo):
**Unitarias**, **de Integración** y **de Humo (Smoke)**.

## Comandos de ejecución

Siempre desde la raíz del proyecto. El binario es `vendor/bin/phpunit`
(si no existe: `composer install`).

```bash
# Suite completa (Unit + Integration + Smoke)
vendor/bin/phpunit

# Por tipo de prueba
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
vendor/bin/phpunit --testsuite Smoke

# Un archivo / una clase
vendor/bin/phpunit tests/Unit/AutoloadTest.php

# Por nombre de método
vendor/bin/phpunit --filter nombre_del_metodo

# Verbose + imprimir cobertura de código fuente
vendor/bin/phpunit --display-coverage   # solo si se genera coverage
vendor/bin/phpunit --display-incomplete --display-skipped
```

Los testsuites están definidos en `phpunit.xml` (`Unit`, `Integration`,
`Smoke`); sin `--testsuite`, PHPUnit ejecuta las tres (todo `tests/`).

## Estructura

```
tests/
├── Unit/          # Sin BD, sin red, sin sesiones. Rápido y determinista
├── Integration/   # Con BD real (dual DB) o con helpers/controladores reales
├── Smoke/         # Sanity check: arranque, autoload, rutas, archivos clave
└── Unit/AutoloadTest.php  # ejemplo existente
```

- Namespace PSR-4: `SysInescolara\Tests\Unit`, `SysInescolara\Tests\Integration`,
  `SysInescolara\Tests\Smoke` (mapeado en `composer.json` → `tests/`).
- Archivo: `*Test.php`; clase `final`; método con atributo `#[Test]`.
- Atributos de PHPUnit 12: `PHPUnit\Framework\Attributes\Test`,
  `DataProvider`, `RequiresPhp`… (no anotaciones `@test` antiguas).

## Convenciones de escritura de pruebas

### Unit (`tests/Unit/`)
- Probar lógica pura: `SysInescolara\helpers\Csrf`, `Auth` (con sesión
  simulada), `SysInescolara\traits\ValidationTrait`, `ImageUploader` (con tmp),
  funciones de `app/controllers/controller_helpers.php` (`jsonResponse`,
  `isAjaxRequest`, `getRequestData` con `$_POST`/`$_SERVER` fake).
- Los controladores son **funciones globales** (sin namespace, salvo
  `FrontController`): probarlos unitariamente requiere `require_once` del
  archivo y llamada directa; preferir helper lógico extraído si existe.
- Nunca tocar BD ni red. Si se necesita PDO, usar `sqlite::memory:` o un mock.

### Integración (`tests/Integration/`)
- Requieren BD real (MariaDB/MySQL local, dual DB: `sysinescolara` +
  `SysInescolara-Seguridad`).
- Cargar `app/helpers/env_loader.php` antes del autoloader si se necesita `.env`.
- Siempre usar transacción + rollback en `setUp`/`tearDown`
  (`beginTransaction` … `rollBack`) para no ensuciar datos.
- Jamás DELETE/TRUNCATE fuera de transacción. Nunca contra prod (Render/Aiven).
- Probar modelos reales (`Planta`, `Lote`, `Venta`…), `Database`, flujos
  controlador → modelo, CSRF real, `Auth::attempt`.

### Humo (`tests/Smoke/`)
- Rápidas, sin dependencias frágiles. Verificar que la app "vive":
  - `class_exists`/`trait_exists` de clases clave (FrontController, Database, Auth, Csrf).
  - Archivos existentes: vistas clave, `app/controllers/FrontController.php`,
    `database/sysinescolara_definitiva.sql`.
  - `parseUrl()` de `FrontController` resuelve rutas predefinidas
    (`catalogo`, `servicios`, `nosotros`, `contacto`).
  - `composer.json`/`phpunit.xml` válidos; autoloader PSR-4 funciona.
- Sin BD si es posible; si la usan, solo `SELECT 1`.

## Flujo al ejecutar

1. Ejecutar la suite pedida (o `vendor/bin/phpunit` completo si no se indica).
2. `phpunit.xml` tiene `failOnWarning`, `failOnRisky`,
   `failOnPhpunitDeprecation` activos → cualquier warning/risky falla la suite.
3. Si falla: leer el fallo, localizar el archivo `tests/...`, corregir **la
   prueba o el código fuente** (no "arreglar" la prueba desactivando asserts).
4. Re-ejecutar hasta verde (exit code 0). Reportar conteo de tests.

## Troubleshooting

- `vendor/bin/phpunit: not found` → `composer install`.
- Deprecaciones de PHP por versión (CLI local puede ser 8.3 vs proyecto 8.2):
  revisar mensaje; no silenciar con `@`.
- Clases no encontradas tras añadir archivo → `composer dump-autoload`.
- Tests de integración fallan por BD → verificar que MariaDB/MySQL local
  (XAMPP/Docker) esté corriendo y `.env` tenga credenciales correctas.
- Salida inesperada en tests → `beStrictAboutOutputDuringTests="true"` está
  activo; usar `$this->expectOutputString()` o evitar `echo`.
