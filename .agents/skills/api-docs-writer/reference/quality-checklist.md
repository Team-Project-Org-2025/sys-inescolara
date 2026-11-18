# Checklist de calidad

## Por endpoint

- [ ] Método y ruta exactos (incluido `action` vs `accion`).
- [ ] Autenticación indicada (`Sesión` / `Pública` / `No requiere`).
- [ ] Permiso exacto o `Solo sesión`.
- [ ] Content-Type real.
- [ ] Todos los parámetros que lee el handler, con tipo y obligatoriedad.
- [ ] Ejemplo `curl` reproducible.
- [ ] Respuesta 200 **capturada**, no inventada.
- [ ] Errores reales (400/401/403/500) con el mensaje del código.
- [ ] Efectos secundarios (auditoría, stock, notificaciones, soft delete).
- [ ] Notas de deuda o rarezas.
- [ ] Enlace al caso Postman.

## Por módulo

- [ ] Todas las acciones del `match` documentadas.
- [ ] Vista HTML incluida como endpoint (aunque devuelva HTML).
- [ ] Acciones auxiliares (búsquedas, combos, detalles) documentadas.
- [ ] Tabla de permisos del módulo completa.
- [ ] Enlaces internos funcionando.
- [ ] Añadido al índice `docs/api/README.md`.
- [ ] Fila en `_cobertura.md`.
- [ ] Colección Postman del módulo ejecutada en verde.

## Antes de cerrar la fase

- [ ] `php -l` sin errores en los archivos tocados (si hubo código).
- [ ] Los JSON de colección son válidos (`ConvertFrom-Json` / `jq`).
- [ ] No se documentaron endpoints que no existen.
- [ ] No se declararon como "verificados" endpoints no ejecutados.
- [ ] Las contraseñas no están en archivos versionados.
