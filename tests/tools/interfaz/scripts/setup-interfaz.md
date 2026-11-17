# `setup-interfaz.ps1` — preparar el entorno E2E

> **Tipo:** preparación · PowerShell
>
> **Atajo:** verifica PHP, PHPUnit, php-webdriver y ChromeDriver, crea las
> carpetas de resultados y deja listo el usuario de prueba.

## Qué hace

1. Comprueba que `php` esté disponible.
2. Instala dependencias con Composer si falta `vendor/php-webdriver` o si usas
   `-Install`.
3. Verifica que exista PHPUnit.
4. Verifica que exista `tests/Interfaz/bin/chromedriver.exe` y muestra su
   versión.
5. Crea `tests/tools/interfaz/formatos/resultados/`, `screenshots/` y `downloads/`.
6. Ejecuta [`seed-interfaz.php`](seed-interfaz.md) para crear/actualizar
   `e2e_admin`.
7. Avisa si la aplicación no responde en
   `http://localhost/sys-inescolara/login`.

No inicia Apache, MySQL ni ChromeDriver: solo comprueba y prepara.

## Requisitos

- PHP 8.2+ en el `PATH`.
- Composer disponible.
- Google Chrome y ChromeDriver compatible en
  `tests/Interfaz/bin/chromedriver.exe`.
- MySQL con las bases del proyecto para crear el usuario.
- Apache iniciado para la comprobación final (opcional, solo avisa).

> El script usa la ruta `chromedriver.exe`, por lo que está pensado para
> Windows/XAMPP.

## Uso

```powershell
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\setup-interfaz.ps1 [-SkipSeed] [-Install]
```

### Parámetros

| Parámetro | Descripción |
| --- | --- |
| `-SkipSeed` | No crea/actualiza el usuario de prueba. |
| `-Install` | Fuerza `composer install` aunque `vendor/php-webdriver` ya exista. |

## Ejemplos

### Preparación normal

```powershell
composer install
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\setup-interfaz.ps1
```

### Sin tocar el usuario

```powershell
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\setup-interfaz.ps1 -SkipSeed
```

### Forzar dependencias

```powershell
powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\setup-interfaz.ps1 -Install
```

## Salida esperada

```text
==> Verificando PHP
==> Verificando dependencias de Composer
==> Verificando ChromeDriver
==> Preparando carpetas de resultados
==> Creando/actualizando usuario de prueba
==> Comprobando la aplicación web
Aplicación disponible (HTTP 200).

Entorno de interfaz listo.
Ejecute: powershell -ExecutionPolicy Bypass -File tests\tools\interfaz\scripts\run-interfaz.ps1
```

## Solución de problemas

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| `PHP no está disponible en PATH` | PHP no está en el `PATH`. | Añade `C:\xampp\php` al `PATH`. |
| `PHPUnit no está instalado` | Falta `vendor/`. | `composer install --dev`. |
| `php-webdriver no está instalado` | Falta la dependencia de desarrollo. | `composer require --dev php-webdriver/webdriver`. |
| `No se encontró ChromeDriver` | Falta el binario. | Descárgalo desde <https://googlechromelabs.github.io/chrome-for-testing/> y colócalo en `tests\Interfaz\bin`. |
| `No se pudo acceder a .../login` | Apache apagado. | Inicia Apache en XAMPP. |
| `No se pudo crear el usuario` | MySQL apagado o `.env` incorrecto. | Inicia MySQL y revisa las credenciales. |

## Ver también

- Ejecutar la suite: [`run-interfaz.md`](run-interfaz.md)
- Usuario de prueba: [`seed-interfaz.md`](seed-interfaz.md)
- Documentación de la suite: [`Interfaz/README.md`](../../../Interfaz/README.md)
