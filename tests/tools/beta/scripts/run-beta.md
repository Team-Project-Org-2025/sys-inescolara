# `run-beta` — ejecutor general de pruebas beta

Ejecuta las pruebas beta automáticas (`humo` + `api`) o una sola, siempre contra
un entorno real. El checklist manual se lanza aparte porque es interactivo.

## Uso

```bash
# Linux / macOS
./tests/tools/beta/scripts/run-beta.sh [prueba|all|core] [opciones]

# Windows
tests\tools\beta\scripts\run-beta.bat [prueba|all|core] [opciones]
```

Sin argumentos equivale a `all` (`humo` + `api`).

## Selección

| Valor | Ejecuta |
| --- | --- |
| `all` | `humo` + `api`. |
| `core` | Solo `humo`. |
| `humo` | Comprobaciones HTTP. |
| `api` | Colección Postman. |
| `checklist` | Checklist manual interactivo. |
| `--test=VALOR` | Equivalente al argumento posicional. |
| `--manual` | Menú interactivo. |
| `--list` | Lista las pruebas y sus requisitos. |

Alias: `smoke`, `http`, `postman`, `manual`.

## Opciones

| Opción | Efecto |
| --- | --- |
| `--base-url=URL` | URL base del entorno beta. |
| `--usuario=USUARIO` | Usuario de la colección Postman. |
| `--password=CLAVE` | Contraseña de la colección Postman. |
| `--csv` | Envía `pruebas/data/plantas.csv` a la colección. |
| `--insecure` | No verifica TLS (solo entornos de prueba). |
| `--max-ms=N` | Falla si una página pública tarda más de N ms. |
| `--dry-run` | Muestra el plan sin ejecutarlo. |
| `--verbose` | Muestra información adicional. |
| `--no-junit` | No genera JUnit. |
| `--results-dir=RUTA` | Cambia el directorio de resultados. |
| `--no-interaction` | Falla si una prueba requiere interacción. |
| `-h`, `--help` | Ayuda. |

## Ejemplos

```bash
# Humo y API contra staging
BETA_PASSWORD='***' ./tests/tools/beta/scripts/run-beta.sh \
  --base-url=https://mi-app.onrender.com --usuario=admin

# Solo disponibilidad
./tests/tools/beta/scripts/run-beta.sh humo \
  --base-url=https://mi-app.onrender.com

# API con CSV
BETA_PASSWORD='***' ./tests/tools/beta/scripts/run-beta.sh api --csv

# Checklist manual
./tests/tools/beta/scripts/run-beta.sh checklist

# Menú
./tests/tools/beta/scripts/run-beta.sh --manual
```

## Qué genera

| Archivo | Contenido |
| --- | --- |
| `formatos/resultados/junit-humo.xml` | JUnit de las 9 comprobaciones HTTP. |
| `formatos/resultados/junit-api.xml` | JUnit de la colección Postman. |
| `formatos/resultados/junit-checklist.xml` | JUnit del checklist. |
| `formatos/resultados/humo-<fecha>.log` | Detalle HTTP. |
| `formatos/resultados/api-<fecha>.log` | Salida de Postman redactada. |
| `formatos/resultados/checklist-beta-<fecha>.md` | Acta manual. |

## Requisitos

- PHP 8.2+ con la extensión `curl` para `humo`.
- Postman CLI en el `PATH` (`postman --version`) para `api`.
- Credenciales válidas del entorno para `api`.
- Plantilla `formatos/plantillas/checklist-beta.plantilla.md` para `checklist`.

## Códigos de salida

| Código | Significado |
| ---: | --- |
| `0` | Todo pasó. |
| `1` | Algún fallo. |
| `2` | Error de uso. |
| `3` | Faltan requisitos del entorno. |
