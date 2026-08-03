# Diagrama de Clases — Sistema Vivero Inescolara

## Convenciones
- `+` público, `-` privado, `#` protegido
- Atributos primero, luego línea en blanco, luego métodos

---

## SEGURIDAD (`SysInescolara-Seguridad`)

---

### BaseDeDatos

- pdo: PDO

+ __construct(string conexion = 'default')
# db(): PDO
+ beginTransaction(): void
+ commit(): void
+ rollback(): void
- loadEnv(): void

---

### Usuario

# validationRules: array

+ __construct()
- bootstrapDefaults(): void
- normalizeUserRow(array user): array
+ obtenerUltimoId(): ?int
+ autenticar(string identificador, string password): ?array
+ verificarPassword(int id, string password): bool
+ obtenerPorTrabajadorId(int idTrabajador): ?array
+ obtenerUsuarioPorEmail(string email): ?array
+ actualizarPassword(int userId, string newPassword): bool
- updatePasswordHash(int userId, string plainPassword): void
+ obtenerTodos(): array
+ usuarioExiste(?int id, ?string nombreUsuario): bool
+ obtenerPorId(int id): ?array
+ agregar(string nombreUsuario, string password, int rolId, ?string correo, ?string avatar, ?int idTrabajadorRef): bool
+ actualizar(int id, string nombreUsuario, int rolId, ?string correo, ?string password, ?string avatar, ?int idTrabajadorRef): bool
+ eliminar(int id): bool
+ actualizarPerfil(int id, string nombreUsuario, ?string correo, ?string password, ?string avatar): bool
+ obtenerRoles(): array
+ obtenerTodosPermisos(): array
+ obtenerPermisosUsuario(int userId): array
+ establecerPermisosUsuario(int userId, array permisos): void
+ obtenerPermisosRol(int rolId, ?int userId): array
+ esPasswordFuerte(string password): bool

---

### Rol

# validationRules: array

+ __construct()
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ existe(int id): bool
+ existePorNombre(string name, ?int excludeId): bool
+ eliminar(int id): bool
+ obtenerUltimoId(): ?int
+ agregar(string nombreRol, ?string descripcion): bool
+ actualizar(int id, string nombreRol, ?string descripcion): bool

---

### BitacoraAuditoria

+ __construct()
+ registrar(int userId, string accion, string tabla, ?int idRegistro, mixed valorAnterior, mixed valorNuevo): bool
+ obtenerTodos(): array
+ static grabar(string accion, string tabla, ?int idRegistro, mixed valorAnterior, mixed valorNuevo): void
+ obtenerPorId(int id): ?array

---

### RestablecerPassword

+ __construct()
- bootstrapTable(): void
+ crearToken(int usuarioId, string correo): string
+ validarToken(string token): ?array
+ marcarUsado(string token): void
+ eliminarTokensExpirados(): void

---

### Notificacion

+ __construct()
+ obtenerNoLeidasCount(int userId): int
+ obtenerRecientes(int userId, int limit): array
+ obtenerTodas(int userId, int page, int perPage): array
+ marcarLeida(int notificationId, int userId): bool
+ marcarTodasLeidas(int userId): bool
+ marcarAdvertenciasLeidas(int userId): bool
+ marcarTareaAsignadaLeida(int userId, string nombreTarea): bool
+ crear(int userId, string titulo, ?string mensaje, string tipo, ?string link): bool
+ existePorTitulo(int userId, string titulo): bool
+ eliminar(int notificationId, int userId): bool

---

## NEGOCIO (`sysinescolara`)

---

### Planta

- id: ?int
- nombreComun: string
- nombreTecnico: ?string
- idEspecie: ?int
- imagen: ?string
- cantidadTotal: int
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerNombreComun(): string
+ obtenerNombreTecnico(): ?string
+ obtenerIdEspecie(): ?int
+ obtenerImagen(): ?string
+ obtenerCantidadTotal(): int
+ esActivo(): bool
+ establecerNombreComun(string nombreComun): self
+ establecerNombreTecnico(?string nombreTecnico): self
+ establecerIdEspecie(?int idEspecie): self
+ establecerImagen(?string imagen): self
+ establecerCantidadTotal(int cantidadTotal): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ obtenerPorId(int id): ?array
+ obtenerTodos(): array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ cargarPorId(int id): bool

---

### Especie

- id: ?int
- nombreEspecie: string
- descripcion: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerNombreEspecie(): string
+ obtenerDescripcion(): ?string
+ esActivo(): bool
+ establecerNombreEspecie(string nombreEspecie): self
+ establecerDescripcion(?string descripcion): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ obtenerPorId(int id): ?array
+ obtenerTodos(): array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int

---

### Lote

- id: ?int
- idPlanta: ?int
- idUbicacion: ?int
- fechaSiembra: ?string
- cantidadInicial: int
- cantidadActual: int
- idEstado: ?int
- idCategoria: ?int
- idOrigen: ?int
- observacion: ?string
- imagen: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerIdPlanta(): ?int
+ obtenerIdUbicacion(): ?int
+ obtenerFechaSiembra(): ?string
+ obtenerCantidadInicial(): int
+ obtenerCantidadActual(): int
+ obtenerIdEstado(): ?int
+ obtenerIdCategoria(): ?int
+ obtenerIdOrigen(): ?int
+ obtenerObservacion(): ?string
+ obtenerImagen(): ?string
+ esActivo(): bool
+ establecerId(int id): self
+ establecerIdPlanta(int idPlanta): self
+ establecerIdUbicacion(int idUbicacion): self
+ establecerFechaSiembra(string fechaSiembra): self
+ establecerCantidadInicial(int cantidadInicial): self
+ establecerCantidadActual(int cantidadActual): self
+ establecerIdEstado(?int idEstado): self
+ establecerIdCategoria(?int idCategoria): self
+ establecerIdOrigen(?int idOrigen): self
+ establecerObservacion(?string observacion): self
+ establecerImagen(?string imagen): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ obtenerPorId(int id): ?array
+ obtenerTodos(): array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ obtenerEstados(): array
+ obtenerCategorias(): array
+ obtenerOrigenes(): array
+ obtenerIdEstadoVivo(): int
+ obtenerIdOrigenPorNombre(string nombre): int
+ cargarPorId(int id): bool
# descontarStock(int id, int cantidad): bool
# restaurarStock(int id, int cantidad): bool
+ agregar(mixed idPlanta, mixed idUbicacion, mixed fechaSiembra, mixed cantidadInicial, mixed cantidadActual, ...): bool
+ actualizar(mixed id, mixed idPlanta, mixed idUbicacion, ...): bool

---

### Ubicacion

- id: ?int
- nombreUbicacion: string
- descripcion: ?string
- tipo: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerNombreUbicacion(): string
+ obtenerDescripcion(): ?string
+ obtenerTipo(): ?string
+ esActivo(): bool
+ establecerNombreUbicacion(string nombreUbicacion): self
+ establecerDescripcion(?string descripcion): self
+ establecerTipo(?string tipo): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ obtenerPorId(int id): ?array
+ obtenerTodos(): array
+ obtenerPorTipo(string tipo): array
+ existe(int id): bool
- tieneLotesAsociados(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ cargarPorId(int id): bool

---

### Empleado

- id: ?int
- nombreTrabajador: string
- apellidoTrabajador: ?string
- cedulaTrabajador: ?string
- telefonoTrabajador: ?string
- cargo: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerNombreTrabajador(): string
+ obtenerApellidoTrabajador(): ?string
+ obtenerCedulaTrabajador(): ?string
+ obtenerTelefonoTrabajador(): ?string
+ obtenerCargo(): ?string
+ esActivo(): bool
+ establecerNombreTrabajador(string nombreTrabajador): self
+ establecerApellidoTrabajador(?string apellidoTrabajador): self
+ establecerCedulaTrabajador(?string cedulaTrabajador): self
+ establecerTelefonoTrabajador(?string telefonoTrabajador): self
+ establecerCargo(?string cargo): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ cargarPorId(int id): bool
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerCargosDistintos(): array
+ obtenerUltimoId(): ?int
+ agregar(string nombre, ?string apellido, ?string cedula, ?string telefono, ?string cargo, bool activo): bool
+ actualizar(int id, string nombre, ?string apellido, ?string cedula, ?string telefono, ?string cargo, bool activo): bool

---

### Cliente

- id: ?int
- nombreCliente: string
- apellidoCliente: ?string
- tipoCedulaCliente: ?string
- cedulaCliente: ?string
- contactoCliente: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
- bootstrapDefaults(): void
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerNombreCliente(): string
+ obtenerApellidoCliente(): ?string
+ obtenerTipoCedulaCliente(): ?string
+ obtenerCedulaCliente(): ?string
+ obtenerContactoCliente(): ?string
+ esActivo(): bool
+ establecerNombreCliente(string nombreCliente): self
+ establecerApellidoCliente(?string apellidoCliente): self
+ establecerTipoCedulaCliente(?string tipoCedulaCliente): self
+ establecerCedulaCliente(?string cedulaCliente): self
+ establecerContactoCliente(?string contactoCliente): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ obtenerPorId(int id): ?array
+ obtenerTodos(): array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ cargarPorId(int id): bool

---

### Proveedor

- id: ?int
- nombreProveedor: string
- rifProveedor: ?string
- contactoVendedor: ?string
- telefonoProveedor: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
- bootstrapDefaults(): void
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerNombreProveedor(): string
+ obtenerRifProveedor(): ?string
+ obtenerContactoVendedor(): ?string
+ obtenerTelefonoProveedor(): ?string
+ esActivo(): bool
+ establecerNombreProveedor(string nombreProveedor): self
+ establecerRifProveedor(?string rifProveedor): self
+ establecerContactoVendedor(?string contactoVendedor): self
+ establecerTelefonoProveedor(?string telefonoProveedor): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ cargarPorId(int id): bool
+ obtenerTodos(): array
+ obtenerPorRif(string rif): ?array
+ obtenerPorId(int id): ?array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ agregar(string nombre, ?string rif, ?string contacto, ?string telefono): bool
+ actualizar(int id, string nombre, ?string rif, ?string contacto, ?string telefono): bool

---

### Venta

- id: ?int
- referencia: ?string
- idCliente: ?int
- idTrabajador: ?int
- tipoVenta: string
- estado: string
- ivaPorcentaje: float
- fechaVenta: ?string
- fechaVencimiento: ?string
- observaciones: ?string
- activo: int
- const IVA_PORCENTAJE: float
- const IVA_MULTIPLICADOR: float
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerReferencia(): ?string
+ obtenerIdCliente(): ?int
+ obtenerIdTrabajador(): ?int
+ obtenerTipoVenta(): string
+ obtenerEstado(): string
+ obtenerIvaPorcentaje(): float
+ obtenerFechaVenta(): ?string
+ obtenerFechaVencimiento(): ?string
+ obtenerObservaciones(): ?string
+ esActivo(): bool
+ establecerReferencia(?string referencia): self
+ establecerIdCliente(?int idCliente): self
+ establecerIdTrabajador(?int idTrabajador): self
+ establecerTipoVenta(string tipoVenta): self
+ establecerEstado(string estado): self
+ establecerIvaPorcentaje(float ivaPorcentaje): self
+ establecerFechaVenta(?string fechaVenta): self
+ establecerFechaVencimiento(?string fechaVencimiento): self
+ establecerObservaciones(?string observaciones): self
+ establecerActivo(bool activo): self
- validar(): void
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ obtenerVentaConDetalles(int id): ?array
+ existe(int id): bool
+ eliminar(int id): bool
+ cancelar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
- generarReferencia(): string
+ agregar(array datos): int
- agregarDetalles(int idVenta, array productos): void
- agregarPagos(int idVenta, array pagos): void
+ obtenerDetalles(int idVenta): array
+ obtenerPagos(int idVenta): array
+ obtenerLotesDisponibles(string query): array
+ buscarClientes(string query): array
+ obtenerTrabajadoresActivos(): array
+ static encontrar(int id): ?self
+ static todos(): array
+ cargarPorId(int id): bool

---

### Compra

# validationRules: array

+ __construct()
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ existe(int id): bool
+ iniciarTransaccion(): bool
+ confirmarTransaccion(): bool
+ revertirTransaccion(): bool
+ obtenerTodas(): array
+ obtenerDetalles(int idCompra): array
+ agregar(int idProveedor, string fechaCompra, string tipoComprobante, ?string numeroComprobante, float subtotal, float iva, float total, ?string observacion): bool
+ actualizar(int id, int idProveedor, string fechaCompra, string tipoComprobante, ?string numeroComprobante, float subtotal, float iva, float total, ?string observacion): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ agregarDetalle(int idCompra, string tipoItem, int idItem, float cantidad, float costoUnitario, float subtotal, ?string categoriaLote, ?int idUbicacion): bool
+ eliminarDetalles(int idCompra): bool
+ crearCuentaPagar(int idCompra, float total): bool
+ actualizarCuentaPagar(int idCompra, float total): bool
+ eliminarCuentaPagarPorCompra(int idCompra): bool
+ tienePagosCuentaPagar(int idCompra): bool
+ actualizarEstado(int id, string estado): bool
+ marcarRecibida(int id): bool
+ aplicarStock(int idCompra): array
+ obtenerUltimoId(): ?int

---

### Insumo

- id: ?int
- nombreInsumo: string
- idUnidadMedida: ?int
- categoria: ?string
- stockActual: float
- costoUnitarioActual: float
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerNombreInsumo(): string
+ obtenerIdUnidadMedida(): ?int
+ obtenerCategoria(): ?string
+ obtenerStockActual(): float
+ obtenerCostoUnitarioActual(): float
+ esActivo(): bool
+ establecerNombreInsumo(string nombreInsumo): self
+ establecerIdUnidadMedida(?int idUnidadMedida): self
+ establecerCategoria(?string categoria): self
+ establecerStockActual(float stockActual): self
+ establecerCostoUnitarioActual(float costoUnitarioActual): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ obtenerPorId(int id): ?array
+ obtenerTodos(): array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ cargarPorId(int id): bool
+ encontrarPorNombreYCategoria(string name, string category): ?array
+ incrementarStock(int id, float quantity): bool

---

### UnidadMedida

- id: ?int
- nombreUnidadMedida: string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerNombreUnidadMedida(): string
+ esActivo(): bool
+ establecerNombreUnidadMedida(string nombreUnidadMedida): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ obtenerPorId(int id): ?array
+ obtenerTodos(): array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ cargarPorId(int id): bool

---

### Herramienta

- id: ?int
- nombreHerramienta: string
- cantidad: int
- estado: string
- fechaUltimoMantenimiento: ?string
- observacion: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerNombreHerramienta(): string
+ obtenerCantidad(): int
+ obtenerEstado(): string
+ obtenerFechaUltimoMantenimiento(): ?string
+ obtenerObservacion(): ?string
+ esActivo(): bool
+ establecerNombreHerramienta(string nombreHerramienta): self
+ establecerCantidad(int cantidad): self
+ establecerEstado(string estado): self
+ establecerFechaUltimoMantenimiento(?string fecha): self
+ establecerObservacion(?string observacion): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ obtenerPorId(int id): ?array
+ obtenerTodos(): array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ cargarPorId(int id): bool
+ registrarUsoConActualizacionEstado(array usageData): int
+ obtenerTodosConDisponibilidad(): array
+ obtenerUsos(int herramientaId): array

---

### Tarea

- id: ?int
- nombre: string
- descripcion: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerNombre(): string
+ obtenerDescripcion(): ?string
+ esActivo(): bool
+ establecerNombre(string nombre): self
+ establecerDescripcion(?string descripcion): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ asignarTareaConConsumos(array assignmentData, array consumptions, array tools): int
+ actualizarAsignacionConConsumos(int asignacionId, array assignmentData, array consumptions, array tools): void
+ obtenerAsignaciones(): array
+ obtenerAsignacionPorId(int id): ?array
+ obtenerConsumos(int asignacionId): array
+ actualizarEstadosHerramientas(int asignacionId, array toolEstados): void
+ obtenerUsosHerramientas(int asignacionId): array
+ contarUsosHerramientasActivos(int idHerramienta, ?int excludeAsignacionId): int
+ completarAsignacion(int id, string fechaCumplimiento, ?float horasDedicadas): void
+ cancelarAsignacion(int id): void

---

### RecoleccionSemillas

- id: ?int
- idTrabajador: ?int
- idUbicacion: ?int
- fechaAsignacion: ?string
- fechaRecoleccion: ?string
- estatus: string
- observacion: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerIdTrabajador(): ?int
+ obtenerIdUbicacion(): ?int
+ obtenerFechaAsignacion(): ?string
+ obtenerFechaRecoleccion(): ?string
+ obtenerEstatus(): string
+ obtenerObservacion(): ?string
+ esActivo(): bool
+ establecerIdTrabajador(?int idTrabajador): self
+ establecerIdUbicacion(?int idUbicacion): self
+ establecerFechaAsignacion(?string fechaAsignacion): self
+ establecerFechaRecoleccion(?string fechaRecoleccion): self
+ establecerEstatus(string estatus): self
+ establecerObservacion(?string observacion): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ cargarPorId(int id): bool
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ agregar(int idTrabajador, int idUbicacion, string fechaAsignacion, ?string observacion): bool
+ actualizar(int id, int idTrabajador, int idUbicacion, string fechaAsignacion, ?string observacion): bool
+ completar(int id, string fechaRecoleccion): bool
+ agregarDetalle(int idRecoleccion, ?string plantaOrigen, string nombreSemilla, int idUnidadMedida, float cantidad, ?int idInsumo): bool
+ obtenerDetalles(int idRecoleccion): array
+ obtenerCantidadDetalles(int idRecoleccion): int
+ registrarSemillasConTransaccion(int idRecoleccion, array items): int

---

### Trazabilidad

- id: ?int
- idLote: ?int
- cantidad: int
- idEstado: ?int
- fechaRegistro: ?string
- observacion: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerIdLote(): ?int
+ obtenerCantidad(): int
+ obtenerIdEstado(): ?int
+ obtenerFechaRegistro(): ?string
+ obtenerObservacion(): ?string
+ esActivo(): bool
+ establecerIdLote(?int idLote): self
+ establecerCantidad(int cantidad): self
+ establecerIdEstado(?int idEstado): self
+ establecerFechaRegistro(?string fechaRegistro): self
+ establecerObservacion(?string observacion): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ obtenerPorId(int id): ?array
+ obtenerTodos(): array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ cargarPorId(int id): bool
+ obtenerLotesDisponibles(?int includeId): array
+ descontarStockLote(int idLote, int cantidad): bool
+ restaurarStockLote(int idLote, int cantidad): bool

---

### Merma

- id: ?int
- idTrazabilidad: ?int
- idLote: ?int
- cantidad: int
- motivo: ?string
- descripcion: ?string
- fechaMerma: ?string
- impactoEconomico: float
- idUsuarioRegistra: ?int
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerIdTrazabilidad(): ?int
+ obtenerIdLote(): ?int
+ obtenerCantidad(): int
+ obtenerMotivo(): ?string
+ obtenerDescripcion(): ?string
+ obtenerFechaMerma(): ?string
+ obtenerImpactoEconomico(): float
+ obtenerIdUsuarioRegistra(): ?int
+ esActivo(): bool
+ establecerId(int id): self
+ establecerIdTrazabilidad(int idTrazabilidad): self
+ establecerIdLote(?int idLote): self
+ establecerCantidad(int cantidad): self
+ establecerMotivo(string motivo): self
+ establecerDescripcion(?string descripcion): self
+ establecerFechaMerma(string fechaMerma): self
+ establecerImpactoEconomico(float impactoEconomico): self
+ establecerIdUsuarioRegistra(int idUsuarioRegistra): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ cargarPorId(int id): bool
+ obtenerUltimoId(): ?int
+ obtenerCuarentenasDisponibles(): array
+ registrarPerdida(int idTrazabilidad, int cantidad, string motivo, ?string descripcion, string fecha, int idUsuario): int
- obtenerInfoCuarentena(int idTrazabilidad): ?array
- descontarStockCuarentena(int idTrazabilidad, int cantidad): void

---

### Ornato

- id: ?int
- idCliente: ?int
- tipoOrnato: string
- descripcion: ?string
- ubicacion: ?string
- montoTotal: float
- fecha: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerIdCliente(): ?int
+ obtenerTipoOrnato(): string
+ obtenerDescripcion(): ?string
+ obtenerUbicacion(): ?string
+ obtenerMontoTotal(): float
+ obtenerFecha(): ?string
+ esActivo(): bool
+ establecerIdCliente(?int idCliente): self
+ establecerTipoOrnato(string tipoOrnato): self
+ establecerDescripcion(?string descripcion): self
+ establecerUbicacion(?string ubicacion): self
+ establecerMontoTotal(float montoTotal): self
+ establecerFecha(?string fecha): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ cargarPorId(int id): bool
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ agregar(array datos): bool
+ agregarDetalles(int idOrnato, array items): bool
+ actualizar(int id, array datos): bool
+ actualizarDetalles(int idOrnato, array items): bool
+ obtenerDetalles(int idOrnato): array
+ buscarClientes(string query): array

---

### CalculoPrecio

- id: ?int
- idLote: ?int
- precioPlantaBase: float
- costoTotalInsumo: float
- porcentajeGanancia: float
- precioFinalSugerido: float
- fechaCalculo: ?string
- vigente: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerIdLote(): ?int
+ obtenerPrecioPlantaBase(): float
+ obtenerCostoTotalInsumo(): float
+ obtenerPorcentajeGanancia(): float
+ obtenerPrecioFinalSugerido(): float
+ obtenerFechaCalculo(): ?string
+ esVigente(): bool
+ establecerIdLote(?int idLote): self
+ establecerPrecioPlantaBase(float precioPlantaBase): self
+ establecerCostoTotalInsumo(float costoTotalInsumo): self
+ establecerPorcentajeGanancia(float porcentajeGanancia): self
+ establecerPrecioFinalSugerido(float precioFinalSugerido): self
+ establecerFechaCalculo(?string fechaCalculo): self
+ establecerVigente(bool vigente): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ obtenerPorId(int id): ?array
+ obtenerTodos(): array
+ existe(int id): bool
+ eliminar(int id): bool
+ obtenerUltimoId(): ?int
+ cargarPorId(int id): bool
+ agregarDetalle(int idCalculo, int idInsumo, float monto): bool
+ obtenerDetalles(int idCalculo): array
+ eliminarDetalle(int idDetalle): bool
+ actualizarMontoDetalle(int idDetalle, float monto): bool
+ recalcularTotalInsumo(int idCalculo): float
- desmarcarVigentes(?int idLote): void
+ obtenerVigentePorLote(int idLote): ?array
+ guardarDetalles(int idCalculo, array detalles): void

---

### ConsumoInsumo

# validationRules: array

+ __construct()
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ existe(int id): bool
+ obtenerPorAsignacion(int asignacionId): array
+ agregar(array data): bool
+ eliminar(int id): bool

---

### UsoHerramienta

# validationRules: array

+ __construct()
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ existe(int id): bool
+ obtenerPorAsignacion(int asignacionId): array
+ agregar(array data): bool
+ eliminar(int id): bool

---

### CuentaPagar

- id: ?int
- idCompra: ?int
- montoTotal: float
- saldoPendiente: float
- fechaVencimiento: ?string
- estado: string
- observacion: ?string
- activo: int
- createdAt: ?string
- updatedAt: ?string
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerIdCompra(): ?int
+ obtenerMontoTotal(): float
+ obtenerSaldoPendiente(): float
+ obtenerFechaVencimiento(): ?string
+ obtenerEstado(): string
+ obtenerObservacion(): ?string
+ esActivo(): bool
+ obtenerCreatedAt(): ?string
+ obtenerUpdatedAt(): ?string
+ establecerIdCompra(?int idCompra): self
+ establecerMontoTotal(float montoTotal): self
+ establecerFechaVencimiento(?string fechaVencimiento): self
+ establecerObservacion(?string observacion): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ cargarPorId(int id): bool
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ existe(int id): bool
+ eliminar(int id): bool
# iniciarTransaccion(): bool
# confirmarTransaccion(): bool
# revertirTransaccion(): bool
+ obtenerTodas(): array
+ obtenerPorCompra(int idCompra): ?array
+ obtenerPagos(int idCuentaPagar): array
+ restaurar(int id): bool
+ obtenerUltimoId(): ?int
+ crear(int idCompra, float montoTotal, ?string fechaVencimiento, ?string observacion): bool
- actualizarEstadoCompra(int idCompra): void
+ registrarPago(int idCuentaPagar, float monto, string fechaPago, ?string tipoPago, ?string referencia, ?string observacion): bool
+ anularPago(int idPagoCompra): bool

---

### CuentaCobrar

# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerTodos(int start, int length, string search, string estadoFilter): array
+ obtenerPorId(int id): ?array
+ obtenerPagos(int idVenta): array
+ registrarPago(int idVenta, float monto, string metodo, ?string referencia, string fechaPago, ?string banco, int idTrabajador, ?string observaciones): int
- validarPago(int idVenta, float monto, string metodo, ?string referencia, string fechaPago, ?string banco, int idTrabajador): void
+ actualizarEstadoVenta(int idVenta): void
+ obtenerEstadisticas(): array
+ obtenerClientes(): array
+ iniciarTransaccion(): void
+ confirmarTransaccion(): void
+ revertirTransaccion(): void

---

### Ampliacion (Movimiento de Plantas)

- id: ?int
- tipoMovimiento: string
- idCliente: ?int
- idTrabajadorGestor: ?int
- fechaMovimiento: ?string
- observacion: ?string
- activo: int
# validationRules: array
# fillable: array
# guarded: array

+ __construct(array atributos = [])
+ llenar(array atributos): self
- mapColumnaAPropiedad(string column): string
+ obtenerId(): ?int
+ obtenerTipoMovimiento(): string
+ obtenerIdCliente(): ?int
+ obtenerIdTrabajadorGestor(): ?int
+ obtenerFechaMovimiento(): ?string
+ obtenerObservacion(): ?string
+ esActivo(): bool
+ establecerTipoMovimiento(string tipoMovimiento): self
+ establecerIdCliente(?int idCliente): self
+ establecerIdTrabajadorGestor(?int idTrabajadorGestor): self
+ establecerFechaMovimiento(?string fechaMovimiento): self
+ establecerObservacion(?string observacion): self
+ establecerActivo(bool activo): self
- validar(): void
+ guardar(): bool
+ static encontrar(int id): ?self
+ static todos(): array
+ static donde(string columna, mixed valor, string operador): array
+ cargarPorId(int id): bool
+ obtenerTodos(): array
+ obtenerPorId(int id): ?array
+ existe(int id): bool
+ eliminar(int id): bool
+ restaurar(int id): bool
+ obtenerDetalles(int id): array
+ obtenerLotesDisponibles(): array
+ obtenerPlantas(): array
+ obtenerUbicaciones(): array
+ obtenerEspecies(): array
- crearPlanta(string nombreComun, ?string nombreTecnico, int idEspecie): int
+ obtenerUltimoId(): ?int
+ registrarIntercambio(array data): int
+ actualizar(int id, array data): bool
+ buscarClientes(string query): array

---

### Respaldo (independiente, no hereda de BaseDeDatos)

- backupDir: string
- mysqldumpPath: string
- mysqlPath: string

+ __construct()
- obtenerOpcionesConexion(string dbHost, string dbPort, bool useSocketFallback): string
- detectarSocket(): ?string
- encontrarMysqldump(): string
- encontrarMysql(): string
+ crear(string dbHost, string dbPort, string dbName, string dbUser, string dbPass, string prefix): array
+ restaurar(string filename): array
- sanitizarDump(string filepath): void
- detectarBaseDatosDesdeArchivo(string filepath): ?string
- obtenerConfigDb(string dbName): ?array
+ listar(): array
+ eliminar(string filename): bool
+ obtenerRutaArchivo(string filename): ?string
+ obtenerDirectorioBackup(): string
- formatearTimestamp(string ts): string
- formatearTamanio(int bytes): string
+ obtenerNombresBasesDatos(): array
+ obtenerConfigsBasesDatos(): array

---

### DatosTablero

- secDb: PDO

+ __construct()
- crearConexionSeguridad(): PDO
+ obtenerEstadisticas(): array
+ obtenerPlantasPorEspecie(): array
+ obtenerResumenInventario(): array
+ obtenerTareasPendientes(): array
+ obtenerActividadReciente(int limit): array
+ obtenerLotesStockBajo(int threshold): array
+ obtenerInsumosStockBajo(int threshold): array
+ obtenerDatosReporte(string reportType, array params): array

---

### Inventario

+ __construct()
+ obtenerConsolidado(): array

---

### Reportes

- modules: array

+ __construct()
- construirModulos(): array
+ obtenerModulos(): array
- fVal(mixed val): bool
+ obtenerFiltrosModulo(string module): array
- filterActivo(): array
- filterDateRange(string field, string label): array
- filterSelectFromQuery(string field, string label, string sql, string valueCol, string textCol, string prependLabel): array
+ obtenerDatosReporte(string module, array filters): array
- construirWhere(array conditions, array &params): string
- aCond(array &conds, array &params, array filters, string field, string column, ?string alias): void
- aActivo(array &conds, array &params, array filters, string alias): void
- aDateRange(array &conds, array &params, array filters, string field, string column): void
- reportPlantas(array filters): array
- reportLotes(array filters): array
- reportInsumos(array filters): array
- reportProveedores(array filters): array
- reportClientes(array filters): array
- reportTrabajadores(array filters): array
- reportTareas(array filters): array
- reportVentas(array filters): array
- reportCompras(array filters): array
- reportHerramientas(array filters): array
- reportEspecies(array filters): array
- reportInventario(array filters): array
- reportRecoleccion(array filters): array
- reportUbicaciones(array filters): array
- reportUnidadesMedida(array filters): array
- reportCuentasPagar(array filters): array
- reportPrecios(array filters): array
- reportCuentasCobrar(array filters): array
