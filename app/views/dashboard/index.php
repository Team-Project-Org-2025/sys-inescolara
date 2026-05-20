<?php

include_once __DIR__ . '/../common/links.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio-Ineco-Lara</title>
    
    <?= $css_links ?>
</head>
<body>
    
    <!-- Overlay para sidebar en móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <?php 
    $currentPage = 'dashboard';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <!-- Contenido principal -->
    <main class="main-content">
        <!-- Header del dashboard -->
        <header class="dashboard-header">
            <div class="dashboard-header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Abrir menú">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h1 class="dashboard-page-title">Dashboard</h1>
            </div>
            
            <div class="dashboard-header-right">
                <div class="dashboard-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Buscar...">
                </div>
                
                <!-- Notificaciones -->
                <button class="header-icon-btn" aria-label="Notificaciones">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notification-badge"></span>
                </button>
                
                <!-- Usuario -->
                <div class="sidebar-user" style="padding: 0.5rem; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem;">
                    <div class="sidebar-user-avatar" style="width: 36px; height: 36px; background-color: #e5a835; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #1a1f2e; overflow: hidden; flex-shrink: 0;">
                        <?php
                        $headerAvatar = $_SESSION['user_avatar'] ?? null;
                        $headerName = $_SESSION['user_nombre'] ?? 'U';
                        if ($headerAvatar): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($headerAvatar) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($headerName, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:0.875rem;font-weight:500;color:#374151;white-space:nowrap;"><?= htmlspecialchars($headerName) ?></span>
                </div>
            </div>
        </header>
        
        <!-- Contenido del dashboard -->
        <div class="dashboard-content">
            <h1>prueba</h1>
            <p style="color: var(--text-secondary);">Dashboard cargado correctamente con sidebar.</p>
        </div>
    </main>
    
    <?= $scripts_links ?>
</body>
</html>