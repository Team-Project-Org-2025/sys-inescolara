# Checklist de prueba beta — Sys Inescolara

> Plantilla de la prueba manual **beta**. La llena `run-checklist` (o
> `run-beta --test=checklist`) pregunta por pregunta y genera un acta en
> `tests/tools/beta/formatos/resultados/checklist-beta-<fecha>.md`.
>
> Resultado de cada escenario: **P** = pasa, **F** = falla, **N/A** = no aplica.

## Identificación

| Campo | Valor |
| --- | --- |
| Fecha | {{FECHA}} |
| Tester | {{TESTER}} |
| Entorno | {{ENTORNO}} |
| Navegador | {{NAVEGADOR}} |
| Versión | {{VERSION}} |

## Escenarios

| # | Escenario | Resultado | Observaciones |
| ---: | --- | :---: | --- |
| 1 | Acceso público: inicio, catálogo, servicios, nosotros y contacto cargan sin errores visibles. | {{R1}} | {{O1}} |
| 2 | Inicio de sesión con un usuario real, incluido el reCAPTCHA, y llegada al dashboard. | {{R2}} | {{O2}} |
| 3 | El dashboard muestra los indicadores (KPIs) y la actividad reciente sin errores. | {{R3}} | {{O3}} |
| 4 | Navegación por los módulos principales (plantas, especies, lotes, insumos, ventas) sin 404 ni tablas vacías por error. | {{R4}} | {{O4}} |
| 5 | Alta, edición y baja lógica de un registro de prueba en un módulo CRUD. | {{R5}} | {{O5}} |
| 6 | Flujo de venta: selección de lote, cálculo de precio, registro y comprobante PDF. | {{R6}} | {{O6}} |
| 7 | Compras y cuentas por pagar: recepción de una orden y registro de un pago. | {{R7}} | {{O7}} |
| 8 | Reportes: filtros, generación en pantalla y exportación a PDF. | {{R8}} | {{O8}} |
| 9 | Notificaciones: se reciben, se marcan como leídas y desaparecen del contador. | {{R9}} | {{O9}} |
| 10 | Cierre de sesión y protección de una ruta interna después de salir. | {{R10}} | {{O10}} |
| 11 | Rendimiento percibido aceptable y mensajes de error claros ante datos inválidos. | {{R11}} | {{O11}} |
| 12 | Compatibilidad con el navegador y la resolución indicados; sin desbordes ni controles inaccesibles. | {{R12}} | {{O12}} |

## Observaciones generales

{{OBSERVACIONES}}

## Cierre

| Rol | Nombre | Firma / fecha |
| --- | --- | --- |
| Tester | {{TESTER}} | |
| Responsable | | |
