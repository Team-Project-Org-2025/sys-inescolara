# Matriz de Trazabilidad de Pruebas — Sys Inescolara

Generado: `{{GENERADO}}` · Orden: sidebar del sistema

## Resumen

| Requisitos | Casos | Casos PASS | Casos FAIL | Casos SKIP | No ejecutados |
| ---: | ---: | ---: | ---: | ---: | ---: |
| {{RESUMEN_REQUISITOS}} | {{RESUMEN_CASOS}} | {{RESUMEN_PASS}} | {{RESUMEN_FAIL}} | {{RESUMEN_SKIP}} | {{RESUMEN_NO_EJECUTADO}} |

Pruebas individuales ejecutadas: **{{PRUEBAS}}** · PASS: **{{PRUEBAS_PASS}}** · FAIL: **{{PRUEBAS_FAIL}}** · SKIP: **{{PRUEBAS_SKIP}}**.

Módulos con al menos una prueba omitida: **{{MODULOS_SKIP_CANTIDAD}}** ({{MODULOS_SKIP_LISTA}}).

## Resumen por módulo

| # | Módulo | Modelo | Unitaria | Integración | Humo | Omitidos | Estado |
| ---: | --- | --- | --- | --- | --- | ---: | --- |
{{FILAS_MODULOS}}

## Matriz detallada

| ID Requisito / HU | Descripción del Requisito (o HU) | ID Caso de Prueba | Tipo de Prueba | Descripción del Caso de Prueba | Resultado Esperado | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- |
{{FILAS_DETALLE}}

## Pruebas omitidas (SKIP)

{{TABLA_OMITIDAS}}

## Cómo leer esta matriz

- **PASS:** todos los casos del tipo ejecutaron sin fallos. Un caso PASS puede incluir pruebas SKIP; revisa la columna **Omitidos** y la sección de pruebas omitidas.
- **FAIL:** al menos un caso falló; revisa `{{RUTA_RESULTADOS}}`.
- **SKIP:** el caso se omitió por una limitación documentada (por ejemplo SQL exclusivo de MySQL).
- **NO EJECUTADO:** no existe el log JUnit de esa suite; ejecuta los comandos de `GUIA_PRUEBAS.md`.
- La evidencia incluye la clase/método y el conteo `PASS/total`.
