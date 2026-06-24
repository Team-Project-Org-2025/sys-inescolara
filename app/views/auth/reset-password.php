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
                <?= \SysInescolara\helpers\Csrf::render() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres"
                               required minlength="8" autocomplete="new-password" maxlength="30">
                        <button type="button" class="password-toggle" id="passwordToggle1" aria-label="Mostrar contraseña">
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
                    <div class="password-strength-bar" id="strengthBar"></div>
                    <div class="password-strength-text" id="strengthText"></div>
                </div>

                <div class="form-group">
                    <label for="password2">Confirmar Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" id="password2" name="password2" placeholder="Repite la contraseña"
                               required minlength="8" autocomplete="new-password" maxlength="30">
                        <button type="button" class="password-toggle" id="passwordToggle2" aria-label="Mostrar contraseña">
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
                    <div class="password-match-text" id="matchText"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Cambiar Contraseña
                </button>

                <div class="form-row" style="justify-content:center;margin-top:var(--space-4);">
                    <a href="<?= BASE_URL ?>login" class="form-link">Volver al inicio de sesión</a>
                </div>
            </form>
            <script src="<?= BASE_URL ?>public/assets/js/auth.js"></script>
        </div>
    </div>
</div>
