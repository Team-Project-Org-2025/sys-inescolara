<?php
/**
 * Header para el dashboard
 * Variables esperadas: $title
 */
?>
<header class="dashboard-header">
    <div class="dashboard-header-left">
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Abrir menú">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
    </div>

    <div class="dashboard-header-right">
        <div class="header-notif-wrapper">
            <button class="header-icon-btn" id="notificationsBtn" aria-label="Notificaciones">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="notification-badge" id="notifBadge" data-user-id="<?= (int)($_SESSION['user_id'] ?? 0) ?>">0</span>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-dropdown-header">
                    <span>Notificaciones</span>
                    <button class="notif-clear-btn" id="clearNotifBtn" style="display:none" onclick="limpiarNotificaciones()">Limpiar todo</button>
                </div>
                <div class="notif-dropdown-list"></div>
                <div class="notif-dropdown-empty" style="display:none">No hay notificaciones nuevas.</div>
            </div>
        </div>

        <?php
        $dhAvatar = $_SESSION['user_avatar'] ?? null;
        $dhName = $_SESSION['user_nombre'] ?? 'U';
        ?>
        <div class="sidebar-user" id="userDropdownBtn" style="cursor:pointer;position:relative;">
            <div class="sidebar-user-avatar">
                <?php if ($dhAvatar): ?>
                    <img src="<?= BASE_URL . htmlspecialchars($dhAvatar) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                    <?= strtoupper(substr($dhName, 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= htmlspecialchars($dhName) ?></span>
                <span class="sidebar-user-role"><?= $_SESSION['user_rol_id'] == 1 ? 'Administrador' : 'Trabajador' ?></span>
            </div>
            <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>

            <div class="user-dropdown-menu" id="userDropdownMenu">
                <a href="<?= BASE_URL ?>dashboard/perfil" class="dropdown-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Mi Perfil
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= BASE_URL ?>login/logout" class="dropdown-item dropdown-item-danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
</header>

<script>
(function() {
    var btn = document.getElementById('userDropdownBtn');
    var menu = document.getElementById('userDropdownMenu');
    if (btn && menu) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('show');
        });
        document.addEventListener('click', function() {
            menu.classList.remove('show');
        });
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
})();
</script>
