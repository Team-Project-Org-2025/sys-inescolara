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
            <div class="alert alert-success">
                <span>Te hemos enviado un correo con las instrucciones para restablecer tu contraseña. Revisa tu bandeja de entrada.</span>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>recuperar-password/enviar" method="POST" class="auth-form" id="recuperarForm">
                <div class="form-group">
                    <label for="correo">Correo Electrónico</label>
                    <input type="email" id="correo" name="correo" placeholder="tu-correo@ejemplo.com"
                           value="<?= htmlspecialchars($old['correo'] ?? '') ?>"
                           required autocomplete="email" maxlength="254">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Enviar Enlace de Recuperación
                </button>

                <div class="form-row" style="justify-content:center;margin-top:var(--space-4);">
                    <a href="<?= BASE_URL ?>login" class="form-link">Volver al inicio de sesión</a>
                </div>
            </form>
        </div>
    </div>
</div>
