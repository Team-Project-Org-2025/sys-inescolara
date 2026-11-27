# Checklist defensa - pruebas automáticas (SYS INESCOLARA)

Checklist rápido para ejecutar y presentar las pruebas automáticas del proyecto en la defensa.

## 1) Preparación rápida (antes de ejecutar)

- [ ] Abrir terminal en la raíz del proyecto: `C:\xampp\htdocs\sys-inescolara`
- [ ] Levantar Apache/MySQL (XAMPP) y confirmar app: `http://localhost/sys-inescolara/login`
- [ ] Tener BD de pruebas cargada (esquema + seeds):
  - `database/sysinescolara_vacia.sql`
  - `pruebas/data/seed.sql`
  - `pruebas/data/seed-seguridad.sql`
- [ ] Verificar herramientas:
  ```powershell
  php -v
  node -v
  npm -v
  newman --version
  composer --version
  ```
- [ ] Credenciales de prueba (según entorno/colecciones): `admin/Admin123!`, `postman_viewer/Postman123!`, `postman_admin/Postman123!`.

## 2) Newman / Postman API (automático)

- [ ] Ejecutar todas las colecciones y generar JUnit XML:
  ```powershell
  powershell -ExecutionPolicy Bypass -File .\pruebas\scripts\run-all.ps1
  ```
- [ ] Alternativa (una colección):
  ```powershell
  newman run ".\pruebas\collections\inventario\Plantas.postman_collection.json" -e ".\pruebas\environments\SysInescolara Local.postman_environment.json" --reporters cli,junitfull --reporter-junitfull-export ".\results\Plantas.xml"
  ```
- [ ] Verificar salida:
  - **XML Newman/JUnit:** `results\*.xml` (1 por colección, 29 esperados)
  - **Consola:** resumen Newman sin fallos.

## 3) PHPUnit

- [ ] Ejecutar PHPUnit con JUnit XML:
  ```powershell
  vendor\bin\phpunit -c tests\phpunit.xml --log-junit results\phpunit.xml
  ```
- [ ] Verificar salida:
  - **XML PHPUnit/JUnit:** `results\phpunit.xml`
  - **Consola:** tests/assertions/fallos/skipped.

## 4) Lint PHP

- [ ] Ejecutar lint (como CI):
  ```powershell
  Get-ChildItem .\app -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
  php -l .\index.php
  ```
- [ ] Verificar salida: `No syntax errors detected`.

## 5) Evidencia para carpeta digital / defensa

- [ ] **Listado de requisitos:** `requisitos.md`
- [ ] **Plan maestro:** `FORMATO PLAN MAESTRO DE PRUEBAS 2026 casi listo.docx`
- [ ] **Plan/registro aceptación:** `tests/tools/reportes/formatos/resultados/ACEPTACION.md` (validar si exigen ejecución actualizada)
- [ ] **XML pipelines/CI (local):** `results\*.xml` + `results\phpunit.xml`
- [ ] **XML CI GitHub Actions (oficial):** descargar artifacts del workflow `.github/workflows/api-tests.yml`
  - `newman-junit`
  - `phpunit-junit`
- [ ] **BD de pruebas:** `database/sysinescolara_vacia.sql`, `pruebas/data/seed.sql`, `pruebas/data/seed-seguridad.sql`

## 6) Notas importantes (para exponer)

- [ ] Evidencia local verificada 2026-11-25: Newman **29 colecciones, 778 testcases, 0 fallos**; PHPUnit **949 tests, 11683 assertions, 0 fallos, 7 skipped**; lint **0 errores**.
- [ ] Último CI reportado: Newman falló en `CuentasCobrar, Compras, CuentasPagar, Mermas, Trazabilidad`; pendiente revisar artifact/failure tras push.
- [ ] Hay commits locales no pusheados en `feature/postman-api-testing`; para XML oficial de GitHub Actions, hacer push y descargar artifacts del run.

## Referencias

- `pruebas/README.md` — guía general de pruebas API/Postman.
- `pruebas/COMANDOS.md` — comandos detallados paso a paso.
- `pruebas/scripts/run-all.ps1` — runner automático Windows (Newman + JUnit).
- `.github/workflows/api-tests.yml` — pipeline CI (Newman, PHPUnit, lint).
