CREATE TABLE `usuarios` (
  `id_usuario` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_usuario` varchar(50) UNIQUE NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `correo_electronico` varchar(100) UNIQUE,
  `id_rol` int,
  `id_trabajador_ref` int UNIQUE COMMENT 'Vínculo 1:1 con trabajadores en DB Core',
  `estatus` enum('Activo','Inactivo','Bloqueado') DEFAULT 'Activo',
  `intentos_fallidos` int DEFAULT 0,
  `ultimo_acceso` timestamp,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `roles` (
  `id_rol` int PRIMARY KEY AUTO_INCREMENT,
  `nombre_rol` varchar(30),
  `descripcion_rol` text
);

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion_rol`) VALUES
(1, 'Administrador', 'Acceso total al sistema');

CREATE TABLE `permisos` (
  `id_permiso` int PRIMARY KEY AUTO_INCREMENT,
  `codigo_permiso` varchar(50) UNIQUE COMMENT 'Ej: VENTAS_REGISTRAR, INV_ELIMINAR',
  `descripcion_permiso` varchar(150)
);

INSERT INTO `usuarios` (`id_usuario`, `nombre_usuario`, `password_hash`, `correo_electronico`, `id_rol`, `id_trabajador_ref`, `estatus`, `intentos_fallidos`, `ultimo_acceso`, `created_at`) VALUES
(1, 'admin', '$2y$10$yrUxEDB84M3WLn2pr9sLp.jUEI8KJc8XFAP51u4mmBThlW/1B84TK', 'admin@inecolara.gob.ve', 1, NULL, 'Activo', 0, NULL, CURRENT_TIMESTAMP);

CREATE TABLE `rol_permisos` (
  `id_rol` int,
  `id_permiso` int,
  PRIMARY KEY (`id_rol`, `id_permiso`)
);

CREATE TABLE `sesiones_activas` (
  `id_sesion` int PRIMARY KEY AUTO_INCREMENT,
  `id_usuario` int,
  `token_sesion` varchar(255) UNIQUE,
  `device_info` varchar(255),
  `fecha_expiracion` datetime,
  `ip_address` varchar(45)
);

CREATE TABLE `auditoria_logs` (
  `id_log` int PRIMARY KEY AUTO_INCREMENT,
  `id_usuario` int,
  `accion` varchar(50),
  `tabla_afectada` varchar(50),
  `id_registro_afectado` int,
  `valor_anterior` json COMMENT 'MySQL soporta tipo JSON para datos estructurados',
  `valor_nuevo` json,
  `endpoint_solicitado` varchar(255),
  `fecha_accion` timestamp DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `usuarios` ADD FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);

ALTER TABLE `rol_permisos` ADD FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);

ALTER TABLE `rol_permisos` ADD FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`);

ALTER TABLE `sesiones_activas` ADD FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

ALTER TABLE `auditoria_logs` ADD FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);
