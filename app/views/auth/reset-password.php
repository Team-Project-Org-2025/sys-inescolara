<?php
/**
 * Vista: Cambiar Contraseña (desde enlace de recuperación)
 * Variables esperadas: $token, $correo, $error (opcional)
 */
?>
<div class="auth-container">
    <div class="auth-panel-left">
        <div class="auth-panel-content">
            <img src="<?= BASE_URL ?>public/assets/images/logo_de_inecolara-sin-fondo.png" alt="INECOLARA" class="auth-logo">
            <h1 class="auth-panel-title">SysInescolara</h1>
            <p class="auth-panel-text">
                Crea una nueva contraseña para tu cuenta.
            </p>
            <ul class="auth-features">
                <li>Mínimo 8 caracteres</li>
                <li>Al menos una mayúscula</li>
                <li>Al menos un número</li>
                <li>Al menos un carácter especial</li>
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
                <h2 class="auth-form-title">Cambiar Contraseña</h2>
                <p class="auth-form-subtitle">Ingresa tu nueva contraseña para <?= htmlspecialchars($correo ?? '') ?></p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>recuperar-password/restablecer" method="POST" class="auth-form" id="resetForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres"
                           required minlength="8" autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label for="password2">Confirmar Contraseña</label>
                    <input type="password" id="password2" name="password2" placeholder="Repite la contraseña"
                           required minlength="8" autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Cambiar Contraseña
                </button>

                <div class="form-row" style="justify-content:center;margin-top:var(--space-4);">
                    <a href="<?= BASE_URL ?>login" class="form-link">Volver al inicio de sesión</a>
                </div>
            </form>
        </div>
    </div>
</div>
