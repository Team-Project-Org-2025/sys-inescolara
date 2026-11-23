# Documentación de API · SYS INESCOLARA

Contrato de los endpoints del backend, documentado **por módulo** y verificado
contra la aplicación en ejecución.

## Cómo leer esta documentación

| Quiero… | Ir a |
|---|---|
| Entender rutas, envelopes y códigos | [`00-convenciones.md`](00-convenciones.md) |
| Autenticarme y mantener la sesión | [`01-autenticacion.md`](01-autenticacion.md) |
| Ver los endpoints de un módulo | carpeta del dominio correspondiente |
| Ver qué falta documentar | [`_cobertura.md`](_cobertura.md) |
| Ejecutar las pruebas | `pruebas/README.md` |

## Estructura

```text
docs/api/
├── 00-convenciones.md     reglas transversales
├── 01-autenticacion.md    login, sesión, recuperación
├── inventario/            plantas, especies, ubicaciones, lotes, precios,
│                          inventario, trazabilidad, mermas, insumos,
│                          herramientas, unidades
├── comercial/             clientes, ventas, cuentas-cobrar, ornatos, ampliación
├── compras/               compras, proveedores, cuentas-pagar
├── operaciones/           tareas, recolección
├── sistema/               dashboard, usuarios, roles, perfil,
│                          notificaciones, auditoría, respaldos, reportes
├── publico/               home, catálogo, servicios, nosotros, contacto
└── _cobertura.md          matriz RF ↔ endpoint ↔ caso Postman
```

## Estados

| Marca | Significado |
|---|---|
| ✅ | Documentado y verificado con una respuesta real |
| 🟡 | Documentado desde el código, sin respuesta capturada |
| ⬜ | Pendiente |

## Verificar una ficha

Toda ficha verificada se puede reproducir así:

```powershell
# 1. Servicios
#    Apache (XAMPP) y MySQL en marcha
# 2. Base de datos
mysql -u root < database/sysinescolara_vacia.sql
mysql -u root < database/sysinescolara-seguridad.sql
mysql -u root < pruebas/data/seed.sql

# 3. Dependencias y migraciones
composer install
php scripts/migrate.php

# 4. Ejecutar la colección del módulo
newman run "pruebas/collections/inventario/Plantas.postman_collection.json" `
  -e "pruebas/environments/SysInescolara Local.postman_environment.json" `
  --env-var "password=Admin123!" `
  --env-var "viewerPassword=Postman123!"
```

## Relación con el código

Cada ficha se extrae de:

1. `app/controllers/{Modulo}Controller.php` — ruta, parámetros, permisos.
2. `app/models/{Modelo}.php` — tabla, columnas, efectos.
3. `public/assets/js/dashboard/{modulo}.js` — llamada real del frontend.
4. Respuesta HTTP ejecutada contra local.

Si el código y esta documentación discrepan, **manda el código**: abre un issue
y actualiza la ficha.
