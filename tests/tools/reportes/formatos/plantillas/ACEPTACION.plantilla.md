# Matriz de Pruebas de Aceptación — Sys Inescolara

Generado: `{{GENERADO}}` · Codeception 5 + WebDriver (Brave/Chrome headless) · Orden: requisitos RF-01 a RF-31

## Resumen

| Casos | PASS | FAIL | SKIP | No ejecutados | Tiempo total |
| ---: | ---: | ---: | ---: | ---: | ---: |
| {{TOTAL}} | {{PASS}} | {{FAIL}} | {{SKIP}} | {{NO_EJECUTADO}} | {{TIEMPO}} s |

## Resumen por requisito

| # | Requisito | Módulo / pantalla | Casos | Estado |
| ---: | --- | --- | ---: | --- |
{{FILAS_REQUISITOS}}

## Matriz detallada

| ID Requisito / HU | Descripción del Requisito (o HU) | ID Caso de Prueba | Tipo de Prueba | Descripción del Caso de Prueba | Resultado Esperado | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- |
{{FILAS_DETALLE}}

{{SECCION_FALLOS}}
## Entorno de ejecución

- **Aplicación:** `{{URL}}` (servidor PHP embebido de aceptación).
- **Navegador:** `{{BROWSER}}` en modo headless.
- **PHP:** {{PHP}}.
- **Usuario de pruebas:** `{{USER}}` (rol Administrador).
- **Reporte JUnit:** `{{REPORTE}}`.

## Notas

- La suite usa las claves de prueba oficiales de Google reCAPTCHA v2 en `tests/aceptacion/server/index.php`; no modifica `.env` ni la aplicación en producción.
- Las pruebas de aceptación de módulos validan carga real, encabezado, contenido y DataTables por AJAX. Las pruebas CRUD crean datos con prefijo `ACE` y los eliminan al final.
- Un caso `PASS` exige que el escenario completo haya terminado sin excepciones ni aserciones fallidas.
- Para regenerar esta matriz: `./tests/tools/aceptacion/scripts/run-aceptacion.sh` y luego `php tests/tools/reportes/scripts/generate_aceptacion.php`.
