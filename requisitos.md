Actúa como un Ingeniero de QA experto en pruebas de backend y automatización con Postman. Estoy trabajando en un proyecto PHP llamado SYSINECOLARA y necesito que me generes los scripts de pruebas (Post-response scripts) y las instrucciones para automatizar la validación de mi API usando el "Collection Runner" de Postman.

Aquí tienes el contexto técnico de mi proyecto:
- Stack: PHP 8.2 (MVC nativo), MySQL, FrontController para ruteo.
- Autenticación: Sesiones PHP (cookie PHPSESSID) y protección CSRF (token enviado en headers para peticiones POST/PUT/DELETE).
- Patrón de respuesta: Las vistas devuelven HTML, pero los endpoints de las tablas (DataTables) y las acciones AJAX (add/edit/delete) devuelven JSON.
- Modelos clave a probar: Plantas, Clientes, Ventas, Insumos, Lotes.
- Las rutas amigables no usan index.php?action=... sino que son tipo: http://localhost/sys-inescolara/plantas/get_plants

Necesito que generes lo siguiente:

1. **Configuración de Variables de Entorno (Environment):**
   - Un JSON de ejemplo para importar en Postman con las variables: `baseUrl` (ej: http://localhost/sys-inescolara), `authCookie` (PHPSESSID) y `csrfToken`.

2. **Script de Pre-request (Pre-request Script) para el Login:**
   - Un script que se ejecute antes del login para limpiar variables antiguas.
   - Un script "Post-response" para la petición de Login que capture la cookie `PHPSESSID` (usando `pm.cookies.get('PHPSESSID')`) y la guarde en una variable de entorno automáticamente, para no tener que copiarla a mano en cada petición.

3. **Scripts de Prueba (Tests) para un Endpoint GET (Ej: get_plants):**
   - Genera el código JavaScript para la pestaña "Tests" que verifique:
     a) Que el status code sea 200.
     b) Que el tiempo de respuesta sea menor a 1000ms.
     c) Que la respuesta sea un JSON válido.
     d) Que contenga la estructura de DataTables (`data`, `recordsTotal`, `recordsFiltered`) y que el array `data` no esté vacío.

4. **Scripts de Prueba para un Endpoint POST (Ej: add_ajax de Plantas):**
   - Genera el código JavaScript para "Tests" que verifique que el status sea 200 o 201, y que el JSON de respuesta contenga un mensaje de éxito (ej: `status: "success"` o `message`).
   - Indícame cómo configurar el Header `X-CSRF-Token` dinámicamente usando la variable de entorno.

5. **Un archivo CSV de ejemplo para Data-Driven Testing:**
   - Crea un CSV con 3 filas de ejemplo (con columnas como nombre, especie, precio) para probar la creación masiva de Plantas usando el Collection Runner de Postman.

Por favor, proporciona el código exacto, comentado y listo para copiar en Postman. No uses sintaxis antigua de Postman (como `tests[]`), usa la sintaxis moderna de `pm.test()`, `pm.expect()` y `pm.response.json()`.