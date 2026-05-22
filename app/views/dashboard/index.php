<?php

include_once __DIR__ . '/../common/links.php';

$stats = $stats ?? [];
$recentActivity = $recentActivity ?? [];
$lowStockLots = $lowStockLots ?? [];
$lowStockSupplies = $lowStockSupplies ?? [];

$totalAlerts = count($lowStockLots) + count($lowStockSupplies);

$userName = $_SESSION['user_nombre'] ?? 'Usuario';
$userInitial = strtoupper(substr($userName, 0, 1));
$userAvatar = $_SESSION['user_avatar'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - SYSINECOLARA</title>
    
    <?= $css_links ?>

<style>
.welcome-banner {
  background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 35%, #e5a835 100%);
  border-radius: var(--radius-xl);
  padding: var(--space-8);
  margin-bottom: var(--space-6);
  position: relative;
  overflow: hidden;
}
.welcome-banner::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 400px;
  height: 400px;
  border-radius: 50%;
  background: rgba(255,255,255,0.04);
}
.welcome-banner::after {
  content: '';
  position: absolute;
  bottom: -30%;
  left: 10%;
  width: 250px;
  height: 250px;
  border-radius: 50%;
  background: rgba(255,255,255,0.03);
}
.welcome-content {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--space-4);
}
.welcome-text h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 var(--space-1);
}
.welcome-text p {
  font-size: 0.95rem;
  color: rgba(255,255,255,0.85);
  margin: 0;
}
.welcome-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  border: 2px solid rgba(255,255,255,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  font-weight: 700;
  color: #fff;
  overflow: hidden;
  flex-shrink: 0;
}
.welcome-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.welcome-stats-row {
  display: flex;
  gap: var(--space-6);
  margin-top: var(--space-4);
  flex-wrap: wrap;
}
.welcome-stat {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  color: rgba(255,255,255,0.9);
  font-size: 0.85rem;
}
.welcome-stat strong {
  font-size: 1.1rem;
  color: #fff;
}
.kpi-card {
  transition: transform var(--transition-normal), box-shadow var(--transition-normal);
  cursor: default;
}
.kpi-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-lg);
}
.kpi-card a {
  transition: color var(--transition-fast);
}
.kpi-card a:hover {
  color: var(--color-primary-dark) !important;
}
</style>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'dashboard';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
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
                <button class="header-icon-btn" aria-label="Notificaciones">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php if ($totalAlerts > 0): ?>
                    <span class="notification-badge"><?= $totalAlerts ?></span>
                    <?php endif; ?>
                </button>
                
                <div class="sidebar-user" style="padding: 0.5rem; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem;">
                    <div class="sidebar-user-avatar" style="width: 36px; height: 36px; background-color: #e5a835; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #1a1f2e; overflow: hidden; flex-shrink: 0;">
                        <?php if ($userAvatar): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($userAvatar) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?= $userInitial ?>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:0.875rem;font-weight:500;color:#374151;white-space:nowrap;"><?= htmlspecialchars($userName) ?></span>
                </div>
            </div>
        </header>
        
        <div class="dashboard-content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-content">
                    <div class="welcome-text">
                        <h1>Bienvenido a SYSINECOLARA</h1>
                        <p>Panel de control del Vivero Institucional INECOLARA — <?= htmlspecialchars($userName) ?></p>
                        <div class="welcome-stats-row">
                            <div class="welcome-stat">
                                <i class="fas fa-calendar-day"></i>
                                <span><?= date('d/m/Y') ?></span>
                            </div>
                            <div class="welcome-stat">
                                <i class="fas fa-database"></i>
                                <span><strong><?= number_format(array_sum($stats)) ?></strong> registros totales</span>
                            </div>
                        </div>
                    </div>
                    <div class="welcome-avatar">
                        <?php if ($userAvatar): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($userAvatar) ?>" alt="Avatar">
                        <?php else: ?>
                            <?= $userInitial ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <a href="<?= BASE_URL ?>dashboard/plants" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Plantas</span>
                        <div class="kpi-card-icon primary"><i class="fas fa-leaf"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_plantas'] ?? 0) ?></div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ver todas
                    </div>
                </div>
                </a>

                <a href="<?= BASE_URL ?>dashboard/species" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Especies</span>
                        <div class="kpi-card-icon info"><i class="fas fa-seedling"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_especies'] ?? 0) ?></div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ver todas
                    </div>
                </div>
                </a>

                <a href="<?= BASE_URL ?>dashboard/batches" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Lotes</span>
                        <div class="kpi-card-icon warning"><i class="fas fa-boxes"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_lotes'] ?? 0) ?></div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ver todos
                    </div>
                </div>
                </a>

                <a href="<?= BASE_URL ?>dashboard/clients" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Clientes</span>
                        <div class="kpi-card-icon success"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_clientes'] ?? 0) ?></div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ver todos
                    </div>
                </div>
                </a>

                <a href="<?= BASE_URL ?>dashboard/suppliers" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Proveedores</span>
                        <div class="kpi-card-icon primary"><i class="fas fa-truck"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_proveedores'] ?? 0) ?></div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ver todos
                    </div>
                </div>
                </a>

                <a href="<?= BASE_URL ?>dashboard/employees" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Empleados</span>
                        <div class="kpi-card-icon info"><i class="fas fa-id-badge"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_trabajadores'] ?? 0) ?></div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ver todos
                    </div>
                </div>
                </a>

                <a href="<?= BASE_URL ?>dashboard/supplies" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Insumos</span>
                        <div class="kpi-card-icon warning"><i class="fas fa-toolbox"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_insumos'] ?? 0) ?></div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ver todos
                    </div>
                </div>
                </a>

                <a href="<?= BASE_URL ?>dashboard/ventas" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Ventas</span>
                        <div class="kpi-card-icon success"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_ventas'] ?? 0) ?></div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ir a ventas
                    </div>
                </div>
                </a>
            </div>

            <!-- Activity + Alerts -->
            <div class="dashboard-grid">
                <!-- Recent Activity -->
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title"><i class="fas fa-history" style="color:var(--color-secondary);"></i> Actividad Reciente</h3>
                        <a href="<?= BASE_URL ?>dashboard/auditlog" class="text-decoration-none" style="font-size:0.8rem;color:var(--color-secondary);font-weight:500;">Ver bitácora <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="dashboard-card-body" style="padding:0;">
                        <div class="activity-feed">
                            <?php if (empty($recentActivity)): ?>
                                <div class="text-center py-5" style="color:var(--text-muted);">
                                    <i class="fas fa-inbox mb-3 d-block" style="font-size:2.5rem;opacity:0.3;"></i>
                                    <p style="font-size:0.9rem;margin:0;">No hay actividad registrada</p>
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($recentActivity, 0, 8) as $activity): ?>
                                <div class="activity-item" style="padding:var(--space-3) var(--space-5);">
                                    <div class="activity-icon" style="width:34px;height:34px;
                                        <?php
                                        $ac = $activity['accion'] ?? '';
                                        $iconColor = match($ac) {
                                            'CREATE' => 'rgba(46,125,32,0.12);color:#2e7d32',
                                            'UPDATE' => 'rgba(14,165,233,0.12);color:#0ea5e9',
                                            'DELETE' => 'rgba(220,38,38,0.12);color:#dc2626',
                                            'LOGIN'  => 'rgba(229,168,53,0.12);color:#e5a835',
                                            'LOGOUT' => 'rgba(107,114,128,0.12);color:#6b7280',
                                            default  => 'rgba(107,114,128,0.12);color:#6b7280',
                                        };
                                        echo "background:{$iconColor}";
                                        ?>">
                                        <i class="fas fa-<?= match($ac) {
                                            'CREATE' => 'plus-circle',
                                            'UPDATE' => 'pen',
                                            'DELETE' => 'trash-alt',
                                            'LOGIN'  => 'sign-in-alt',
                                            'LOGOUT' => 'sign-out-alt',
                                            default  => 'circle',
                                        } ?>" style="font-size:0.7rem;"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text">
                                            <strong><?= htmlspecialchars($activity['nombre_usuario'] ?? 'Sistema') ?></strong>
                                            <span style="font-weight:400;color:var(--text-secondary);">
                                                <?= match($ac) {
                                                    'CREATE' => 'creó',
                                                    'UPDATE' => 'editó',
                                                    'DELETE' => 'eliminó',
                                                    'LOGIN'  => 'inició sesión',
                                                    'LOGOUT' => 'cerró sesión',
                                                    default  => mb_strtolower($ac),
                                                } ?>
                                                <?php if (!empty($activity['tabla_afectada'])): ?>
                                                    en <?= htmlspecialchars($activity['tabla_afectada']) ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="activity-time">
                                            <?php
                                            $fecha = $activity['fecha_accion'] ?? '';
                                            if ($fecha) {
                                                try {
                                                    $dt = new DateTime($fecha, new DateTimeZone('UTC'));
                                                    $dt->setTimezone(new DateTimeZone('America/Caracas'));
                                                    echo htmlspecialchars($dt->format('d/m/Y h:i A'));
                                                } catch (\Throwable $e) {
                                                    echo htmlspecialchars($fecha);
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                <div>
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h3 class="dashboard-card-title" style="display:flex;align-items:center;gap:var(--space-2);">
                                <span style="width:28px;height:28px;border-radius:var(--radius-md);background:rgba(220,38,38,0.12);display:flex;align-items:center;justify-content:center;color:var(--color-error);font-size:0.8rem;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                                Alertas de Stock
                            </h3>
                            <?php if ($totalAlerts > 0): ?>
                            <span class="badge bg-danger"><?= $totalAlerts ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="dashboard-card-body" style="padding:0;">
                            <?php if ($totalAlerts === 0): ?>
                                <div class="text-center py-5" style="color:var(--text-muted);">
                                    <i class="fas fa-check-circle mb-3 d-block" style="font-size:2.5rem;color:var(--color-success);opacity:0.4;"></i>
                                    <p style="font-size:0.9rem;margin:0;font-weight:500;color:var(--color-success);">Todo en niveles óptimos</p>
                                </div>
                            <?php else: ?>
                                <?php if (!empty($lowStockLots)): ?>
                                <div style="padding:var(--space-2) var(--space-4);font-size:0.75rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid var(--color-gray-100);">
                                    <i class="fas fa-boxes" style="margin-right:var(--space-1);"></i> Lotes con stock bajo
                                </div>
                                <div class="low-stock-list" style="padding:var(--space-2);">
                                    <?php foreach ($lowStockLots as $lot): ?>
                                    <div class="low-stock-item">
                                        <div>
                                            <div style="font-weight:600;font-size:0.85rem;"><?= htmlspecialchars($lot['planta_nombre'] ?? "Lote #{$lot['id_lote']}") ?></div>
                                            <div style="font-size:0.75rem;color:var(--text-muted);">Estado: <?= htmlspecialchars($lot['estado'] ?? 'N/A') ?></div>
                                        </div>
                                        <span class="badge bg-danger"><?= (int)($lot['cantidad_actual'] ?? 0) ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($lowStockSupplies)): ?>
                                <div style="padding:var(--space-2) var(--space-4);font-size:0.75rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid var(--color-gray-100);<?= !empty($lowStockLots) ? 'border-top:1px solid var(--color-gray-100);margin-top:var(--space-1);padding-top:var(--space-2) !important;' : '' ?>">
                                    <i class="fas fa-toolbox" style="margin-right:var(--space-1);"></i> Insumos por reabastecer
                                </div>
                                <div class="low-stock-list" style="padding:var(--space-2);">
                                    <?php foreach ($lowStockSupplies as $supply): ?>
                                    <div class="low-stock-item" style="border-left-color:var(--color-warning);">
                                        <div>
                                            <div style="font-weight:600;font-size:0.85rem;"><?= htmlspecialchars($supply['nombre_insumo'] ?? '') ?></div>
                                            <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($supply['unidad_medida'] ?? '') ?></div>
                                        </div>
                                        <span class="badge bg-warning text-dark"><?= htmlspecialchars((string)($supply['stock_actual'] ?? 0)) ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?= $scripts_links ?>
</body>
</html>
