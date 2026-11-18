# Cookbook de aserciones

Patrones probados contra SYS INESCOLARA. Copia y adapta; no inventes claves
que no aparezcan en la respuesta real.

## 1. Listado correcto

```js
pm.test('200 OK', () => pm.response.to.have.status(200));

pm.test('Responde JSON', () => {
  pm.response.to.be.json;
});

pm.test('Envelope de éxito', () => {
  const body = pm.response.json();
  pm.expect(body).to.have.property('success', true);
});
```

> El nombre del arreglo cambia por módulo: `plantas`, `species`, `clients`,
> `supplies`, `batches`, `data`… **Léelo del controlador** antes de escribirlo.

## 2. Listado con datos (DataTables)

Algunos endpoints devuelven el formato que espera DataTables:

```js
pm.test('Estructura DataTables', () => {
  const body = pm.response.json();
  pm.expect(body).to.have.property('data').that.is.an('array');
});
```

## 3. Crear recurso

```js
pm.test('Creación exitosa', () => {
  const body = pm.response.json();
  pm.expect(body.success).to.eql(true);
  pm.expect(body.message).to.be.a('string').and.not.empty;
});

// Guardar el id para editar/eliminar
const body = pm.response.json();
const id = body.id ?? body.id_planta ?? body.data?.id;
pm.test('Devuelve el id creado', () => pm.expect(id).to.be.a('number'));
pm.collectionVariables.set('plantaId', id);
```

## 4. Editar recurso

```js
pm.test('Edición exitosa', () => {
  pm.expect(pm.response.json().success).to.eql(true);
});
```

Verificación real (no confiar en el mensaje): consultar el listado y buscar el id.

```js
pm.sendRequest({
  url: `${pm.collectionVariables.get('baseUrl')}/plantas?action=get_plants`,
  header: { 'X-Requested-With': 'XMLHttpRequest' }
}, (err, res) => {
  pm.test('El cambio persiste', () => {
    const item = res.json().plantas.find(p => p.id === pm.collectionVariables.get('plantaId'));
    pm.expect(item).to.exist;
  });
});
```

## 5. Eliminar (soft delete)

```js
pm.test('Eliminación exitosa', () => {
  pm.expect(pm.response.json().success).to.eql(true);
});
```

El borrado es lógico (`activo = 0`): el registro puede seguir apareciendo en
`getById` pero no en el listado. Verifica contra el listado, no contra la fila.

## 6. Caso 401 — sin sesión

```js
pm.test('Sin sesión devuelve 401', () => {
  pm.response.to.have.status(401);
});

pm.test('Mensaje de no autorizado', () => {
  const body = pm.response.json();
  pm.expect(body.success).to.eql(false);
  pm.expect(body.message).to.match(/no autorizado/i);
});
```

Para que este caso funcione, el request debe ejecutarse **sin** cookie. Usa una
carpeta separada con `auth: noauth` o borra la cookie antes:

```js
pm.cookies.clear();
```

## 7. Caso 403 — usuario sin permiso

Requiere una sesión de un usuario con rol limitado (`viewerCookie`).

```js
pm.test('Sin permiso devuelve 403', () => {
  pm.response.to.have.status(403);
});

pm.test('Mensaje de permiso', () => {
  pm.expect(pm.response.json().message).to.match(/permiso/i);
});
```

## 8. Caso 400 — acción inexistente

```js
pm.test('Acción inválida devuelve 400', () => {
  pm.response.to.have.status(400);
});
pm.test('Mensaje de acción inválida', () => {
  pm.expect(pm.response.json().message).to.match(/acci[oó]n ajax inv[aá]lida/i);
});
```

## 9. Validación de campos

Los controladores lanzan `\Exception` con texto en español y `handleError()`
responde 500 (no 422):

```js
pm.test('Campo requerido rechazado', () => {
  pm.expect([400, 500]).to.include(pm.response.code);
  pm.expect(pm.response.json().message).to.match(/requerid/i);
});
```

## 10. Esquema de respuesta

```js
const schema = {
  type: 'object',
  required: ['success'],
  properties: {
    success: { type: 'boolean' },
    message: { type: 'string' }
  }
};

pm.test('Esquema válido', () => {
  pm.response.to.have.jsonSchema(schema);
});
```

## 11. Tiempo de respuesta

```js
pm.test('Responde en menos de 2 s', () => {
  pm.expect(pm.response.responseTime).to.be.below(2000);
});
```

## 12. Anti-patrones

| Evita | Por qué |
|---|---|
| `pm.expect(body.data.length).above(0)` en un listado vacío | La BD de pruebas puede estar limpia |
| Hardcodear `id = 1` | Puede no existir o estar borrado lógicamente |
| Asumir `{success, data}` siempre | El envelope varía por módulo |
| Asumir `?action=` en Ventas | Ventas usa `?accion=` |
| Probar solo el camino feliz | Los 401/403/400 son requisito de la suite |
| Depender del orden de ejecución sin `postman.setNextRequest` | La carpeta puede correr aislada |
