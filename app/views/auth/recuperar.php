<?php
/**
 * Vista: Recuperar Contraseña
 * Variables esperadas: $error (string, opcional), $success (string, opcional), $old (array, opcional)
 */
?>
<div class="auth-container">
    <div class="auth-panel-left">
        <div class="auth-panel-content">
            <img src="<?= BASE_URL ?>public/assets/images/logo_de_inecolara-sin-fondo.png" alt="INECOLARA" class="auth-logo">
            <h1 class="auth-panel-title">SysInescolara</h1>
            <p class="auth-panel-text">
                Recupera el acceso a tu cuenta de forma segura.
            </p>
            <ul class="auth-features">
                <li>Recibirás un enlace por correo</li>
                <li>El enlace expira en 1 hora</li>
                <li>Elige una contraseña segura</li>
            </ul>
        </div>
        <div class="auth-panel-footer">
            <p>&copy; <?= date('Y') ?> INECOLARA - Instituto de Ecosocialismo del Estado Lara</p>
        </div>
    </div>
    <div class="auth-panel-right">
        <div class="auth-form-container">
            <div class="auth-form-header">
                <img src="<?= BASE_URL ?>public/assets/images/logo_de_inecolara-sin-fondo.png" alt="INECOLARA" class="auth-logo-mobile">
                <a href="<?= BASE_URL ?>login" class="auth-back-link">Volver al inicio de sesión</a>
                <h2 class="auth-form-title">Recuperar Contraseña</h2>
                <p class="auth-form-subtitle">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
            </div>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success animate-slide-up">
                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Te hemos enviado un correo con las instrucciones para restablecer tu contraseña. Revisa tu bandeja de entrada.</span>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error animate-slide-up">
                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>recuperar-password/enviar" method="POST" class="auth-form" id="recuperarForm" novalidate>
                <?= \SysInescolara\helpers\Csrf::render() ?>
                <div class="form-group">
                    <label for="correo" class="form-label">Correo Electrónico</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <input type="email" id="correo" name="correo" class="form-input"
                               placeholder="tu-correo@ejemplo.com"
                               value="<?= htmlspecialchars($old['correo'] ?? '') ?>"
                               required autocomplete="email" maxlength="254">
                    </div>
                </div>

                <div class="form-group recaptcha-wrapper">
                    <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars(getenv('RECAPTCHA_SITE_KEY') ?: '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI') ?>"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="recuperarBtn">
                    <span class="btn-text">Enviar Enlace de Recuperación</span>
                    <span class="btn-loader hidden">
                        <svg class="spinner" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round">
                                <animate attributeName="stroke-dasharray" values="0 150;42 150;42 150" dur="1.5s" repeatCount="indefinite"/>
                                <animate attributeName="stroke-dashoffset" values="0;-16;-59" dur="1.5s" repeatCount="indefinite"/>
                            </circle>
                        </svg>
                        Enviando...
                    </span>
                </button>

                <div class="form-row" style="justify-content:center;margin-top:var(--space-5);">
                    <a href="<?= BASE_URL ?>login" class="form-link">Volver al inicio de sesión</a>
                </div>
            </form>
            <script src="<?= BASE_URL ?>public/assets/js/auth.js"></script>
        </div>
    </div>
</div>
