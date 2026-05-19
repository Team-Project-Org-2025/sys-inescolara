<?php
/**
 * Vista: Login
 * Variables esperadas: $error (string, opcional), $old (array de valores previos, opcional)
 */
?>
<div class="auth-container">
    <!-- Left Panel - Decorative -->
    <div class="auth-panel-left">
        <div class="auth-panel-content">
            <img src="<?= BASE_URL ?>public/images/logo.png" alt="INECOLARA" class="auth-logo">
            <h1 class="auth-panel-title">Sistema de Gestión del Vivero</h1>
            <p class="auth-panel-text">
                Plataforma integral para la administración de inventario, ventas y operaciones del Vivero Institucional INECOLARA.
            </p>
            
            <ul class="auth-features">
                <li>
                    <svg class="auth-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Control de inventario en tiempo real
                </li>
                <li>
                    <svg class="auth-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Gestión de lotes y producción
                </li>
                <li>
                    <svg class="auth-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Punto de venta integrado
                </li>
                <li>
                    <svg class="auth-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Reportes y estadísticas
                </li>
                <li>
                    <svg class="auth-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Asistente de IA integrado
                </li>
            </ul>
        </div>
        
        <div class="auth-panel-footer">
            <p>&copy; <?= date('Y') ?> INECOLARA - Instituto de Ecosocialismo del Estado Lara</p>
        </div>
    </div>
    
    <!-- Right Panel - Login Form -->
    <div class="auth-panel-right">
        <div class="auth-form-container">
            <div class="auth-form-header">
                <a href="<?= BASE_URL ?>" class="auth-back-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Volver al inicio
                </a>
                <h2 class="auth-form-title">Iniciar Sesión</h2>
                <p class="auth-form-subtitle">Ingresa tus credenciales para acceder al sistema</p>
                <p class="auth-form-hint">Prueba: <strong>admin</strong> / <strong>Admin123!</strong> o <strong>admin@inecolara.gob.ve</strong></p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-error" id="loginError">
                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>
            
            <form action="<?= BASE_URL ?>login" method="POST" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-label">Usuario o Correo Electrónico</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <input 
                            type="text" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="admin o usuario@inecolara.gob.ve"
                            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                            required
                            autocomplete="username"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Ingresa tu contraseña"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" id="passwordToggle" aria-label="Mostrar contraseña">
                            <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span class="checkbox-custom"></span>
                        Recordarme
                    </label>
                    <a href="<?= BASE_URL ?>recuperar-password" class="form-link">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg" id="loginBtn">
                    <span class="btn-text">Iniciar Sesión</span>
                    <span class="btn-loader hidden">
                        <svg class="spinner" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round">
                                <animate attributeName="stroke-dasharray" values="0 150;42 150;42 150" dur="1.5s" repeatCount="indefinite"/>
                                <animate attributeName="stroke-dashoffset" values="0;-16;-59" dur="1.5s" repeatCount="indefinite"/>
                            </circle>
                        </svg>
                        Verificando...
                    </span>
                </button>
            </form>
            
            <div class="auth-form-footer">
                <p>¿Necesitas acceso? Contacta al administrador del sistema.</p>
            </div>
        </div>
    </div>
</div>
