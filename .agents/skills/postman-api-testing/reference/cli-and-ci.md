# CLI y CI

## 1. Ejecutar una colección

### Postman CLI

```bash
postman collection run "pruebas/collections/inventario/Plantas.postman_collection.json" \
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" \
  --env-var "usuario=admin" \
  --env-var "password=Admin123!"
```

`postman login` **no** es necesario para corridas locales. El aviso
"Postman credentials are required for publishing run details" es normal.

### Newman

```bash
newman run "pruebas/collections/inventario/Plantas.postman_collection.json" \
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" \
  --env-var "usuario=admin" \
  --env-var "password=Admin123!"
```

## 2. Data-driven con CSV

El CSV debe tener una fila por iteración, sin BOM:

```csv
nombre_comun,nombre_tecnico,especie_id,precio
Rosa de Prueba Uno,Rosa canina,1,15.50
```

```bash
postman collection run <coleccion> -e <entorno> -d pruebas/data/plantas.csv -n 3
```

En los requests se referencia con `{{nombre_comun}}`, `{{precio}}`, etc.
`-n 3` fija el número de iteraciones (si se omite, usa las filas del CSV).

## 3. Reportes

```bash
postman collection run <coleccion> -e <entorno> \
  --reporters cli,json \
  --reporter-json-export pruebas/reportes/plantas.json
```

`newman` equivalentes: `-r cli,json --reporter-json-export ...`.

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
postman collection run "..."
```

## 6. CI (GitHub Actions)

No hay workflow en el repo todavía. Esqueleto mínimo:

```yaml
name: API Tests

on: [push, pull_request]

jobs:
  postman:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ALLOW_EMPTY_PASSWORD: "yes"
          MYSQL_DATABASE: sysinescolara
        ports: ["3306:3306"]
        options: >-
          --health-cmd="mysqladmin ping -h localhost"
          --health-interval=5s --health-timeout=5s --health-retries=10
    steps:
      - uses: actions/checkout@v4
      - run: sudo apt-get install -y php8.2-cli php8.2-mysql
      - run: composer install --no-interaction
      - run: mysql -h 127.0.0.1 -u root < database/sysinescolara_vacia.sql
      - run: mysql -h 127.0.0.1 -u root sysinescolara-seguridad < database/sysinescolara-seguridad.sql
      - run: php -S 127.0.0.1:8080 -t . &
      - run: npm install -g newman
      - run: newman run "pruebas/collections/**/*.postman_collection.json" -e "pruebas/environments/SysInescolara Local.postman_environment.json"
        env:
          POSTMAN_BASE_URL: http://127.0.0.1:8080
```

Ajusta rutas y credenciales al pipeline real. Lo importante: **la BD debe
importarse antes de correr** y el servidor debe usar las llaves de prueba de
reCAPTCHA.

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
