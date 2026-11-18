# `run-checklist` — checklist manual de la prueba beta

Guía al tester por 12 escenarios funcionales, registra **P**asa / **F**alla /
**N**o aplica y las observaciones, y genera un acta en Markdown con la misma
estructura de la plantilla.

```bash
# Linux / macOS
./tests/tools/beta/scripts/run-checklist.sh --base-url=https://mi-app.onrender.com

# Windows
tests\tools\beta\scripts\run-checklist.bat --base-url=https://mi-app.onrender.com
```

Equivale a:

```bash
./tests/tools/beta/scripts/run-beta.sh --test=checklist
```

## Flujo

1. Pide **tester**, **entorno**, **navegador** y **versión**.
2. Pregunta, uno por uno, los 12 escenarios de la plantilla.
3. Para cada escenario acepta:
   - `P` → pasa (valor por defecto).
   - `F` → falla.
   - `N` → no aplica.
4. Pide observaciones por escenario y observaciones generales.
5. Escribe el acta y el JUnit.

## Escenarios

| # | Escenario |
| ---: | --- |
| 1 | Acceso público: inicio, catálogo, servicios, nosotros y contacto. |
| 2 | Inicio de sesión con reCAPTCHA y llegada al dashboard. |
| 3 | KPIs y actividad reciente del dashboard. |
| 4 | Navegación por los módulos principales. |
| 5 | Alta, edición y baja lógica de un registro de prueba. |
| 6 | Flujo de venta y comprobante PDF. |
| 7 | Compras, cuentas por pagar y registro de un pago. |
| 8 | Reportes con filtros y exportación a PDF. |
| 9 | Notificaciones: recibir, marcar como leídas y contador. |
| 10 | Cierre de sesión y protección de rutas internas. |
| 11 | Rendimiento percibido y mensajes de error claros. |
| 12 | Compatibilidad con navegador y resolución. |

## Resultados

| Archivo | Contenido |
| --- | --- |
| `formatos/resultados/checklist-beta-<fecha>.md` | Acta llena, lista para adjuntar. |
| `formatos/resultados/junit-checklist.xml` | Un caso por escenario. |

## Códigos de salida

| Código | Significado |
| ---: | --- |
| `0` | Ningún escenario falló. |
| `1` | Al menos un escenario se marcó como `F`. |
| `3` | Faltó la plantilla o se cerró la entrada antes de terminar. |

## Plantilla

La estructura y los textos de cada escenario viven en
`formatos/plantillas/checklist-beta.plantilla.md`. Para cambiar un escenario,
edite la plantilla manteniendo las columnas y los marcadores `{{R<n>}}` y
`{{O<n>}}`; el script los detecta automáticamente.

Las columnas obligatorias son:

```markdown
| # | Escenario | Resultado | Observaciones |
| ---: | --- | :---: | --- |
| 1 | Texto del escenario | {{R1}} | {{O1}} |
```
