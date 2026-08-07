<?php
include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Ayuda - INECOLARA</title>
    <?= $css_links ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.8.0/dist/driver.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/ayuda.css">
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php
    $currentPage = 'ayuda';
    include_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <main class="main-content">
        <?php $title = 'Centro de Ayuda'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

        <div class="dashboard-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Centro de Ayuda</h1>
                    <p style="color: var(--text-secondary); margin-bottom: 0;">Guía rápida para el uso del sistema SYSINECOLARA.</p>
                </div>
                <button class="btn btn-primary" id="btnStartTour">
                    <i class="fas fa-play me-2"></i>Iniciar recorrido
                </button>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Preguntas frecuentes</h5>
                    <div class="accordion help-accordion" id="helpAccordion">

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#help1">
                                            ¿Cómo registro una nueva planta?
                                        </button>
                                    </h2>
                                    <div id="help1" class="accordion-collapse collapse show" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            Ve al módulo <strong>Gestionar Planta → Administrar planta</strong> y presiona el botón
                                            <em>Registrar planta</em>. Completa los datos requeridos (especie, ubicación, cantidad)
                                            y guarda. La planta quedará disponible en el inventario.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help2">
                                            ¿Cómo proceso una venta?
                                        </button>
                                    </h2>
                                    <div id="help2" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            Abre <strong>Gestionar Venta → Procesar venta</strong>, selecciona el cliente y los ejemplares,
                                            confirma el total y finaliza la venta. Puedes descargar el comprobante en PDF desde el listado de ventas.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help3">
                                            ¿Dónde veo el inventario disponible?
                                        </button>
                                    </h2>
                                    <div id="help3" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            En <strong>Gestionar Activos → Inventario</strong>. Ahí podrás consultar existencias,
                                            movimientos y el estado de cada ejemplar.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help4">
                                            ¿Cómo genero un reporte?
                                        </button>
                                    </h2>
                                    <div id="help4" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            Entra a <strong>Reportes</strong> en la sección HERRAMIENTAS del menú lateral, elige el tipo
                                            de reporte, el rango de fechas y presiona <em>Generar</em>.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help5">
                                            ¿Qué hago si olvidé mi contraseña?
                                        </button>
                                    </h2>
                                    <div id="help5" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            En la pantalla de inicio de sesión presiona <em>¿Olvidaste tu contraseña?</em> y sigue las
                                            instrucciones que se envían a tu correo. Si no tienes acceso al correo, contacta a tu
                                            administrador del sistema.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help6">
                                            ¿Cómo asigno una tarea a un trabajador?
                                        </button>
                                    </h2>
                                    <div id="help6" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            Ve a <strong>Gestionar Tarea → Asignar tarea</strong> y presiona el botón
                                            <em>Asignar Tarea</em>. Completa los campos requeridos, Opcionalmente agrega descripción, fecha de asignación, consumo de insumos
                                            y uso de herramientas. Al guardar, el trabajador vinculado recibe una notificación
                                            de <em>Nueva tarea asignada</em>.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help7">
                                            ¿Dónde veo las tareas que me asignaron?
                                        </button>
                                    </h2>
                                    <div id="help7" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            En <strong>Gestionar Tarea → Asignar tarea</strong> se listan todas las asignaciones
                                            con su estatus (pendiente / completada). a su vez recibirás una notificación que te lleva directo a esta pantalla.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help8">
                                            ¿Cómo hago un respaldo de la base de datos?
                                        </button>
                                    </h2>
                                    <div id="help8" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            En <strong> Gestionar Configuración → Respaldo</strong> presiona
                                            <em>Crear Respaldo Completo</em>. El sistema respalda la base de datos principal y
                                            la de seguridad. Desde la tabla puedes descargar, restaurar o eliminar los
                                            respaldos guardados.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help9">
                                            ¿Cómo agrego un usuario al sistema?
                                        </button>
                                    </h2>
                                    <div id="help9" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            En <strong>Gestionar Configuración → Usuario</strong> presiona
                                            <em>Nuevo Usuario</em>. Completa nombre de usuario,correo,contraseña y rol;
                                            vincula un trabajador (para recibir
                                            notificaciones de tareas) y selecciona los módulos y acciones permitidos.
                                            Guarda para crear la cuenta.
                                        </div>
                                    </div>
                                </div>

                            </div>
                    </div>
                </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card contact-card h-100">
                        <div class="card-body">
                            <i class="fas fa-envelope contact-icon"></i>
                            <div class="contact-label">Correo de soporte</div>
                            <span>soporte@inecolara.gob.ve</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card contact-card h-100">
                        <div class="card-body">
                            <i class="fas fa-phone contact-icon"></i>
                            <div class="contact-label">Teléfonos</div>
                            <span>+58 412-3557704/+58 426-7239855</span>
                            <span>+58 414-4237719/+58 424-5280342</span>
                            <span>+58 424-5759005</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card contact-card h-100">
                        <div class="card-body">
                            <i class="fas fa-user-shield contact-icon"></i>
                            <div class="contact-label">Soporte técnico</div>
                            <span>Administradores del sistema</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.8.0/dist/driver.js.iife.js"></script>
    <script src="<?= BASE_URL ?>public/assets/js/dashboard/ayuda.js"></script>
</body>
</html>
