<?php
include_once __DIR__ . '/../common/links.php';
$title = 'Mi Perfil';
$currentPage = 'perfil';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php include_once __DIR__ . '/../partials/sidebar.php'; ?>
    <main class="main-content">
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        <div class="dashboard-content">
            <div class="perfil-card">
                <div class="perfil-header">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= BASE_URL . htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="perfil-avatar-img">
                    <?php else: ?>
                        <div class="perfil-avatar">
                            <?= strtoupper(substr($user['nombre_usuario'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 style="font-size:1.5rem;font-weight:600;margin:0 0 4px 0;">
                            <?= htmlspecialchars($user['nombre_usuario'] ?? '') ?>
                            <?php if (!empty($user['trabajador_nombre'])): ?>
                                <span style="font-size:0.9rem;color:var(--text-muted);font-weight:400;">— <?= htmlspecialchars($user['trabajador_nombre']) ?></span>
                            <?php endif; ?>
                        </h1>
                        <p style="margin:0;color:var(--text-muted);font-size:0.875rem;"><?= htmlspecialchars($user['correo_electronico'] ?? '') ?></p>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="perfil-alert perfil-alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="perfil-alert perfil-alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="perfil-form">
                    <div class="perfil-field">
                        <label for="avatar">Foto de perfil</label>
                        <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/gif,image/webp">
                        <span style="font-size:0.75rem;color:var(--text-muted);">Máximo 5 MB. Formatos: JPG, PNG, GIF, WebP.</span>
                    </div>
                    <div class="perfil-field">
                        <label for="nombre">Nombre de usuario</label>
                        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($user['nombre_usuario'] ?? '') ?>" required maxlength="50">
                    </div>
                    <div class="perfil-field">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['correo_electronico'] ?? '') ?>" maxlength="254">
                    </div>
                    <div class="perfil-field">
                        <label for="current_password">Contraseña actual</label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password" maxlength="30">
                    </div>
                    <hr style="border:none;border-top:1px solid var(--color-gray-200);margin:var(--space-2) 0;">
                    <div class="perfil-field">
                        <label for="password">Nueva contraseña <span style="color:var(--text-muted);font-weight:400;font-size:0.8rem;">(dejar vacío para mantener la actual)</span></label>
                        <input type="password" id="password" name="password" autocomplete="new-password" maxlength="30">
                    </div>
                    <div class="perfil-field">
                        <label for="password2">Confirmar contraseña</label>
                        <input type="password" id="password2" name="password2" autocomplete="new-password" maxlength="30">
                    </div>
                    <button type="submit" class="perfil-btn">Guardar cambios</button>
                </form>
            </div>
        </div>
    </main>

    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
</body>
</html>
