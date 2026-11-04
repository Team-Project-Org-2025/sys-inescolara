# Comandos y guía paso a paso — Pruebas de API con Postman

Todos los comandos de esta guía se ejecutan desde la raíz del proyecto:

```bash
cd /home/anyelberdev/Escritorio/proyectos/sys-inescolara
```

Si usas Windows con XAMPP, cambia la ruta por la tuya:

```powershell
cd C:\xampp\htdocs\sys-inescolara
```

Matriz de referencia: [`TRACEABILITY.md`](TRACEABILITY.md)
Instrucciones conceptuales: [`README.md`](README.md)

---

## 0. Requisitos

| Herramienta | Verificación |
| --- | --- |
| Apache/XAMPP o Docker sirviendo la app | `curl -I http://localhost/sys-inescolara/login` → `200` |
| Postman CLI | `postman --version` → `1.86.0` o superior |
| PHP 8.2+ | `php -v` |
| `curl` | `curl --version` |
| Credenciales de un usuario administrador | `usuario` + `password` |
| Un `especie_id` existente | La suite lo detecta sola en el request `09` |

No necesitas `postman login` para ejecutar una colección local. El aviso
`Postman credentials are required for publishing run details` es normal y no
afecta la corrida.

---

## 1. Verificar que el servidor responde

```bash
BASE="http://localhost/sys-inescolara"

curl -s -o /dev/null -w "login: %{http_code}\n" "$BASE/login"
curl -s -o /dev/null -w "plantas sin sesión: %{http_code}\n" \
  -H "X-Requested-With: XMLHttpRequest" "$BASE/plantas/get_plants"
```

Esperado:

```text
login: 200
plantas sin sesión: 401
```

Si `login` no da `200`, inicia Apache/XAMPP o el contenedor antes de continuar.
Si `plantas` no da `401`, revisa la URL base.

---

## 2. Instalar el Postman CLI (solo si falta)

```bash
command -v postman && postman --version
```

Si no existe:

```bash
npm install -g postman-cli
postman --version
```

> En Linux también puedes usar el instalador oficial:
> `curl -o- "https://dl-cli.pstmn.io/install/linux64.sh" | sh`

---

## 3. Configurar reCAPTCHA de prueba (solo local)

El login exige reCAPTCHA. Para que el Runner pueda autenticarse sin navegador,
usa las llaves de prueba oficiales de Google. **Nunca las uses en producción.**

```bash
cp .env .env.postman.bak

sed -i 's|^RECAPTCHA_SITE_KEY=.*|RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI|' .env
sed -i 's|^RECAPTCHA_SECRET_KEY=.*|RECAPTCHA_SECRET_KEY=6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe|' .env

grep '^RECAPTCHA_' .env
```

Esperado:

```dotenv
RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
RECAPTCHA_SECRET_KEY=6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
```

No hace falta reiniciar Apache: `env_loader.php` lee el `.env` en cada petición.

> El archivo `.env.postman.bak` queda ignorado por git (`.env.*` está en
> `.gitignore`). Restáuralo al terminar; ver sección 9.

Si no quieres tocar el `.env`, salta a la sección 6.2 (usar una sesión del
navegador) y omite la carpeta `00 · Autenticación`.

---

## 4. Definir las credenciales

### Opción A — Usar tu usuario administrador (recomendado)

```bash
export POSTMAN_USUARIO='admin'
export POSTMAN_PASSWORD='tu-password-real'
```

### Opción B — Si no conoces la contraseña, crea un usuario temporal

Genera el hash bcrypt:

```bash
php -r 'echo password_hash("Postman123!", PASSWORD_BCRYPT), PHP_EOL;'
```

Copia el hash y ejecútalo en la base de datos de seguridad (phpMyAdmin o
consola MySQL):

```sql
INSERT INTO `usuarios`
  (`nombre_usuario`, `password_hash`, `correo_electronico`, `id_rol`,
   `estatus`, `intentos_fallidos`, `created_at`)
VALUES
  ('postman_qa', '<PEGA_AQUI_EL_HASH>', 'postman_qa@example.test', 1,
   'Activo', 0, NOW());
```

Luego:

```bash
export POSTMAN_USUARIO='postman_qa'
export POSTMAN_PASSWORD='Postman123!'
```

> El `id_rol = 1` corresponde al rol administrador, que evita los filtros de
> permisos por módulo.
>
> La limpieza de este usuario está en la sección 9.3. No lo dejes en la base de
> datos.

---

## 5. Ejecutar la colección

### 5.1 Una corrida simple (sin CSV)

```bash
POSTMAN_USUARIO="$POSTMAN_USUARIO" POSTMAN_PASSWORD="$POSTMAN_PASSWORD" \
  ./pruebas/scripts/run.sh
```

Equivalente sin el helper:

```bash
postman collection run \
  "pruebas/collections/SYSINECOLARA API Tests.postman_collection.json" \
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" \
  --env-var "usuario=$POSTMAN_USUARIO" \
  --env-var "password=$POSTMAN_PASSWORD"
```

### 5.2 Data-Driven Testing con 3 filas

```bash
POSTMAN_USUARIO="$POSTMAN_USUARIO" POSTMAN_PASSWORD="$POSTMAN_PASSWORD" \
  ./pruebas/scripts/run.sh --csv
```

Equivalente sin el helper:

```bash
postman collection run \
  "pruebas/collections/SYSINECOLARA API Tests.postman_collection.json" \
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" \
  -d "pruebas/data/plantas.csv" \
  --env-var "usuario=$POSTMAN_USUARIO" \
  --env-var "password=$POSTMAN_PASSWORD"
```

`-d` toma una iteración por cada fila del CSV. No necesitas `-n`; si quieres
forzarlo:

```bash
postman collection run ... -d "pruebas/data/plantas.csv" -n 3
```

### 5.3 Contra otro host (Render, staging)

```bash
POSTMAN_USUARIO="$POSTMAN_USUARIO" POSTMAN_PASSWORD="$POSTMAN_PASSWORD" \
POSTMAN_BASE_URL="https://tu-app.onrender.com" \
  ./pruebas/scripts/run.sh
```

O directamente:

```bash
postman collection run ... --env-var "baseUrl=https://tu-app.onrender.com"
```

> En Render, el login con llaves reales de reCAPTCHA no se puede automatizar.
> Usa la sección 6.2 (cookie del navegador) y ejecuta solo `10` y `20`.

### 5.4 Ejecutar solo una carpeta o un request

```bash
# Solo autenticación
postman collection run "pruebas/collections/SYSINECOLARA API Tests.postman_collection.json" \
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" \
  -i "00 · Autenticación" \
  --env-var "usuario=$POSTMAN_USUARIO" --env-var "password=$POSTMAN_PASSWORD"

# Solo listados
postman collection run "pruebas/collections/SYSINECOLARA API Tests.postman_collection.json" \
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" \
  -i "10 · GET (listados)"

# Un único request (requiere sesión previa o correr antes la autenticación)
postman collection run "pruebas/collections/SYSINECOLARA API Tests.postman_collection.json" \
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" \
  -i "10 · GET Plantas"
```

### 5.5 Verificar el código de salida

```bash
./pruebas/scripts/run.sh --csv
echo "exit=$?"
```

- `0` → todas las assertions pasaron.
- distinto de `0` → al menos una assertion falló.

El CLI devuelve el código de salida de las pruebas, no solo del HTTP. Un `200`
puede fallar una assertion.

---

## 6. Importar y ejecutar en la interfaz de Postman

### 6.1 Importación

1. Abre Postman.
2. **File → Import** (o `Ctrl+O`).
3. Arrastra estos dos archivos:
   - `pruebas/collections/SYSINECOLARA API Tests.postman_collection.json`
   - `pruebas/environments/SysInescolara Local.postman_environment.json`
4. Arriba a la derecha, selecciona el environment **SysInescolara Local**.
5. Haz clic en el ojo del environment y edita:
   - `usuario` → tu usuario administrador.
   - `password` → tu contraseña.
   - `baseUrl` → tu URL si es distinta de `http://localhost/sys-inescolara`.
6. Deja `recaptchaToken` en `PASSED` si estás usando las llaves de prueba.

### 6.2 Usar una sesión del navegador (llaves reales de reCAPTCHA)

1. Inicia sesión normalmente en el navegador.
2. Abre DevTools → **Application → Cookies** → tu dominio.
3. Copia el valor de `PHPSESSID`.
4. En Postman, environment → `authCookie` → pega el valor.
5. En el Runner, desmarca o no ejecutes la carpeta `00 · Autenticación`.
6. Ejecuta `10 · GET (listados)` y `20 · POST (escritura y limpieza)`.

La cookie del navegador caduca cuando cierras la sesión. Si empiezas a recibir
`401 No autorizado`, repite el proceso.

### 6.3 Collection Runner

1. Clic derecho sobre la colección → **Run collection**.
2. Verifica que el environment esté seleccionado.
3. En **Data**, selecciona `pruebas/data/plantas.csv`.
4. Deja **Iterations** en `3` (una por fila) o usa el valor que Postman detecte.
5. **Run SYSINECOLARA API Tests**.
6. Revisa la pestaña **Tests**: todas las assertions deben estar en verde.

---

## 7. Resultado esperado

Con el CSV de 3 filas:

```text
iterations:        3
requests:         36   (12 × 3)
test-scripts:     36
prerequest-scripts: 48
assertions:      186   (62 × 3)
failed:            0
```

Por iteración:

| Request | Assertions |
| --- | ---: |
| `00` logout | 2 |
| `01` GET login | 6 |
| `02` POST login | 4 |
| `09` GET especies | 4 |
| `10` GET plantas | 7 |
| `11` GET clientes | 6 |
| `12` GET insumos | 6 |
| `13` GET lotes | 7 |
| `14` GET ventas | 6 |
| `20` crear planta | 6 |
| `21` editar planta | 4 |
| `22` eliminar planta | 4 |
| **Total** | **62** |

Sin CSV (una iteración) el total es **62 assertions** y **12 requests**.

---

## 8. Diagnóstico de fallos

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| `401 No autorizado` en los GET | No se ejecutó el login, la cookie expiró o `authCookie` está vacía | Ejecuta la carpeta `00 · Autenticación` completa; revisa `authCookie` en el environment |
| `302` hacia `/login` en un GET | Sesión inválida y la petición no se marcó como AJAX | Verifica el header `X-Requested-With: XMLHttpRequest` |
| `404` con HTML en un POST | Error de validación atrapado por el `FrontController` | Revisa el payload: nombre solo con letras/espacios, `especie_id` existente |
| `419 Token CSRF inválido` | POST no AJAX sin `_csrf_token` | Envía `X-Requested-With: XMLHttpRequest` o el `_csrf_token` correcto |
| `Verificación de seguridad fallida` | reCAPTCHA con llaves reales y token `PASSED` | Usa llaves de prueba en local o una cookie de navegador |
| `plantas` llega vacío | La tabla no tiene filas activas | Crea una planta desde el sistema o revisa el seed |
| `clientes`, `supplies` o `ventas` vacíos | Tablas sin datos; el test lo permite | Agrega la clave a `seededCollections` si tu BD sí tiene datos |
| `No se encontró PHPSESSID` | Cookie jar deshabilitado o respuesta sin `Set-Cookie` | La colección tiene fallback por header; revisa que el login devuelva `302` |
| `--output` falla con “You must be signed in” | El reporte YAML requiere Postman Cloud | Usa la salida de consola o `run.sh`; no necesitas `--output` |
| `Postman credentials are required...` | Aviso al intentar publicar el run | Ignóralo; la corrida local ya terminó |

Para ver la respuesta cruda de un request, en Postman abre **Console**
(`Ctrl+Alt+C`) o usa los comandos `curl` de la sección 10.

---

## 9. Limpieza después de probar

### 9.1 Restaurar el `.env`

```bash
mv .env.postman.bak .env
grep '^RECAPTCHA_' .env
```

Confirma que volvieron tus llaves reales. Si no existe el backup, edita el
`.env` manualmente y restaura `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET_KEY`.

### 9.2 Borrar las plantas de prueba

La suite hace soft delete, así que quedan filas con `activo = 0`. Para
eliminarlas físicamente:

```bash
mysql -u root -p sysinescolara -e "
DELETE FROM plantas
WHERE nombre_comun IN (
  'Rosa de Prueba Uno', 'Ixora de Prueba Dos', 'Jazmin de Prueba Tres',
  'Planta Postman'
)
OR nombre_comun LIKE '%Editada';
"
```

Verifica que solo queden tus plantas reales:

```bash
mysql -u root -p sysinescolara -e \
  "SELECT id_planta, nombre_comun, activo FROM plantas ORDER BY id_planta;"
```

> Ajusta `-u root -p` y el nombre de la base según tu `.env`
> (`DB_USER`, `DB_PASSWORD`, `DB_NAME`).

### 9.3 Borrar el usuario temporal (solo si usaste la opción B)

El orden importa por la FK de `auditoria_logs`:

```bash
mysql -u root -p 'SysInescolara-Seguridad' -e "
DELETE FROM auditoria_logs
WHERE id_usuario = (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'postman_qa');

DELETE FROM usuario_permisos
WHERE id_usuario = (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'postman_qa');

DELETE FROM usuarios WHERE nombre_usuario = 'postman_qa';
"
```

---

## 10. Comandos `curl` equivalentes (para depurar)

Útiles cuando Postman no está disponible o quieres aislar un problema.

```bash
BASE="http://localhost/sys-inescolara"
JAR="/tmp/sysinescolara-cookies.txt"
rm -f "$JAR"

# 1) Sesión anónima + CSRF
CSRF=$(curl -s -c "$JAR" "$BASE/login" \
  | grep -o 'name="_csrf_token" value="[a-f0-9]*"' \
  | sed 's/.*value="//;s/"//')
echo "CSRF capturado: ${#CSRF} caracteres"

# 2) Login (requiere llaves de prueba de reCAPTCHA)
curl -s -b "$JAR" -c "$JAR" -o /dev/null \
  -w "login=%{http_code} redirect=%{redirect_url}\n" \
  -X POST "$BASE/login" \
  --data-urlencode "nombre_usuario=$POSTMAN_USUARIO" \
  --data-urlencode "password=$POSTMAN_PASSWORD" \
  --data-urlencode "g-recaptcha-response=PASSED" \
  --data-urlencode "_csrf_token=$CSRF"

# 3) Listar plantas
curl -s -b "$JAR" \
  -H "X-Requested-With: XMLHttpRequest" \
  "$BASE/plantas/get_plants" | head -c 500
echo

# 4) Crear una planta (nombre solo con letras y espacios)
curl -s -b "$JAR" \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --data-urlencode "nombre_comun=Planta Curl" \
  --data-urlencode "nombre_tecnico=Especie de prueba" \
  --data-urlencode "especie_id=1" \
  --data-urlencode "_csrf_token=$CSRF" \
  "$BASE/plantas/add_ajax"
echo
```

---

## 11. Actualizar la matriz de trazabilidad

La matriz [`TRACEABILITY.md`](TRACEABILITY.md) es una **foto** de la corrida
verificada. Si cambias la colección o los datos:

1. Ejecuta la suite con CSV:
   ```bash
   ./pruebas/scripts/run.sh --csv
   ```
2. Si todo pasa, actualiza en `TRACEABILITY.md`:
   - la fecha de generación;
   - el número de requests/assertions si cambió;
   - los estados `✅ PASS` / `➖ N/A` si agregaste módulos.
3. Si agregas un endpoint, crea un caso `CP-API-XX` y una fila en la matriz
   detallada y en la tabla de cobertura de los 31 requisitos.

Checklist rápido:

```text
[ ] El servidor responde 200 en /login
[ ] Las llaves de reCAPTCHA de prueba están activas (solo local)
[ ] usuario/password son correctos
[ ] La corrida muestra 0 failed
[ ] authCookie y csrfToken se llenaron solos
[ ] Se restauró el .env
[ ] Se eliminaron plantas y usuario temporales
```

---

## 12. Notas para Windows / PowerShell

Variables de entorno:

```powershell
$env:POSTMAN_USUARIO = "admin"
$env:POSTMAN_PASSWORD = "tu-password"
```

Ejecutar la colección:

```powershell
postman collection run `
  "pruebas\collections\SYSINECOLARA API Tests.postman_collection.json" `
  -e "pruebas\environments\SysInescolara Local.postman_environment.json" `
  -d "pruebas\data\plantas.csv" `
  --env-var "usuario=$env:POSTMAN_USUARIO" `
  --env-var "password=$env:POSTMAN_PASSWORD"
```

Para respaldar el `.env`:

```powershell
Copy-Item .env .env.postman.bak
```

Restaurar:

```powershell
Move-Item -Force .env.postman.bak .env
```
