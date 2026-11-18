# `run-api` — colección Postman contra el entorno beta

Ejecuta la colección `pruebas/collections/SYSINECOLARA API Tests.postman_collection.json`
contra un entorno real, pasando la URL y las credenciales como variables de
Postman.

```bash
# Linux / macOS (recomendado: contraseña por variable de entorno)
BETA_PASSWORD='***' ./tests/tools/beta/scripts/run-api.sh \
  --base-url=https://mi-app.onrender.com --usuario=admin

# Windows
set BETA_PASSWORD=***
tests\tools\beta\scripts\run-api.bat --base-url=https://mi-app.onrender.com --usuario=admin
```

Equivale a:

```bash
./tests/tools/beta/scripts/run-beta.sh --test=api
```

## Qué ejecuta

La colección cubre:

1. Limpieza de sesión y obtención del CSRF.
2. Login con reCAPTCHA.
3. Listados de especies, plantas, clientes, insumos, lotes y ventas.
4. Alta, edición y eliminación de una planta de prueba (con limpieza).

## Opciones

| Opción | Efecto |
| --- | --- |
| `--base-url=URL` | URL del entorno. También `BETA_BASE_URL`. |
| `--usuario=USUARIO` | Usuario. También `BETA_USUARIO`. |
| `--password=CLAVE` | Contraseña. También `BETA_PASSWORD`. |
| `--csv` | Envía `pruebas/data/plantas.csv` a la colección. |
| `--insecure` | No aplica a Postman; se mantiene por uniformidad. |
| `--dry-run` | Muestra el comando sin ejecutarlo. |

## Resultados

| Archivo | Contenido |
| --- | --- |
| `formatos/resultados/junit-api.xml` | Un caso por solicitud de la colección. |
| `formatos/resultados/api-<fecha>.log` | Salida de Postman con la contraseña redactada. |

El reporte JSON intermedio de Postman se elimina al terminar porque puede
contener cuerpos de petición con credenciales.

## Requisitos

- Postman CLI en el `PATH`:

  ```bash
  postman --version
  ```

  Si no está instalado, consulte `pruebas/README.md`.
- Contraseña válida del entorno. Sin ella, la prueba se marca **OMITIDA** con
  código `3`.

## Seguridad

La colección **escribe** en la base de datos: crea una planta, la edita y la
elimina. Use un entorno de staging o un usuario de pruebas. No la ejecute contra
producción con datos reales.
