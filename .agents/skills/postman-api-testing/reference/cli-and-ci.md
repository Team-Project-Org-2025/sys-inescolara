# CLI y CI

## 1. Ejecutar una colección

### Newman (CLI oficial de la suite)

```bash
# Una colección
newman run "pruebas/collections/inventario/Plantas.postman_collection.json" \
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" \
  --env-var "usuario=admin" \
  --env-var "password=Admin123!"

# Todas (helpers versionados)
./pruebas/scripts/run.sh                      # 29 colecciones
./pruebas/scripts/run-all.sh                  # + JUnit en results/
pwsh -File pruebas\scripts\run-all.ps1        # Windows
```

Newman no requiere `postman login` ni credenciales de la nube. El Postman CLI
(`postman collection run`) quedó fuera de la suite: si lo ves en docs viejas,
usa el equivalente `newman run`.

## 2. Data-driven con CSV

El CSV debe tener una fila por iteración, sin BOM:

```csv
nombre_comun,nombre_tecnico,especie_id,precio
Rosa de Prueba Uno,Rosa canina,1,15.50
```

```bash
newman run <coleccion> -e <entorno> -d pruebas/data/plantas.csv -n 3
```

En los requests se referencia con `{{nombre_comun}}`, `{{precio}}`, etc.
`-n 3` fija el número de iteraciones (si se omite, usa las filas del CSV).
Aplica solo a la suite monolítica anterior; las colecciones por módulo crean y
limpian sus propios registros.

## 3. Reportes

```bash
newman run <coleccion> -e <entorno> \
  --reporters cli,json \
  --reporter-json-export pruebas/reportes/plantas.json
```

Para JUnit (lo que usa CI):

```bash
newman run <coleccion> -e <entorno> \
  --reporters cli,junit \
  --reporter-junit-export results/<coleccion>.xml
```

## 4. Variables por entorno

| Variable | Uso |
|---|---|
| `baseUrl` | `http://localhost/sys-inescolara` (sin slash final) |
| `usuario` | Usuario administrador |
| `password` | Contraseña (secret) |
| `recaptchaToken` | `PASSED` en local |
| `authCookie` | `PHPSESSID` del login |
| `viewerUsuario` / `viewerPassword` | Usuario sin permisos para el caso 403 |
| `<modulo>Id` | Id creado en la corrida |

No guardes contraseñas reales en el JSON del entorno versionado: usa
`--env-var` o un entorno local ignorado por git.

## 5. Script de arranque local (Windows)

```powershell
# 1. Servicios
Get-Process mysqld -ErrorAction SilentlyContinue | Out-Null
if (-not (Get-Process mysqld -ErrorAction SilentlyContinue)) {
  Start-Process "C:\xampp\mysql\bin\mysqld.exe" `
    -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini" -WindowStyle Hidden
}

# 2. Health check
curl.exe -s -o NUL -w "%{http_code}" http://localhost/sys-inescolara/login

# 3. Corrida
newman run "<coleccion>" -e "<entorno>"
```

## 6. CI (GitHub Actions)

El pipeline real está en [`.github/workflows/api-tests.yml`](../../../../.github/workflows/api-tests.yml)
con tres jobs:

- **`api`** — MariaDB 10.4 + PHP 8.2 + composer + newman; carga
  `database/sysinescolara_vacia.sql`, corre `php scripts/migrate.php`, siembra
  `pruebas/data/seed.sql` y `pruebas/data/seed-seguridad.sql`, arranca
  `php -S 127.0.0.1:8000 tests/aceptacion/server/router.php` (fuerza las llaves
  reCAPTCHA de prueba) y ejecuta las 29 colecciones exportando JUnit a
  `results/`.
- **`phpunit`** — `vendor/bin/phpunit -c tests/phpunit.xml`.
- **`lint`** — `php -l` sobre `app/` e `index.php`.

Secretos requeridos en GitHub Actions: `POSTMAN_ADMIN_PASSWORD` y
`POSTMAN_VIEWER_PASSWORD`.

## 7. Diagnóstico

| Error | Causa probable |
|---|---|
| `ECONNREFUSED` | Apache/PHP o MySQL apagados |
| `401` inesperado | Cookie jar perdido entre procesos |
| `303` en un GET | El endpoint redirige; falta header AJAX |
| `500` con `Table ... doesn't exist` | Esquema incompleto; importar `database/sysinescolara_vacia.sql` |
| Login en HTML de error | Llaves reCAPTCHA reales en `.env` |
| `newman: command not found` | `npm i -g newman` |
| Exit code 1 con todos los tests PASS | Un `pm.test` falló; revisa el reporter `cli` |
