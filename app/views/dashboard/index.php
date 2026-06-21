<?php

include_once __DIR__ . '/../common/links.php';

$stats = $stats ?? [];
$recentActivity = $recentActivity ?? [];
$lowStockLots = $lowStockLots ?? [];
$lowStockSupplies = $lowStockSupplies ?? [];
$pendingTasks = $pendingTasks ?? [];
$plantsBySpecies = $plantsBySpecies ?? [];
$inventorySummary = $inventorySummary ?? [];

$totalAlerts = count($lowStockLots) + count($lowStockSupplies);
$totalProd = ($stats['total_plantas'] ?? 0) + ($stats['total_especies'] ?? 0) + ($stats['total_lotes'] ?? 0);

$userName = \SysInescolara\helpers\Auth::name();
$userInitial = strtoupper(substr($userName, 0, 1));
$userAvatar = \SysInescolara\helpers\Auth::avatar();
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
  transform: translateY(-3px) !important;
  box-shadow: var(--shadow-lg) !important;
}
.kpi-card a {
  transition: color var(--transition-fast);
}
.kpi-card a:hover {
  color: var(--color-primary-dark) !important;
}
.kpi-card .kpi-sub {
  font-size: 0.7rem;
  color: var(--text-muted);
  margin-top: var(--space-1);
}
.section-divider {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-muted);
  padding: var(--space-1) 0;
  margin: var(--space-2) 0;
  grid-column: 1 / -1;
}
.section-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--color-gray-200);
}
.quick-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
  margin-bottom: var(--space-6);
}
.quick-action-btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: 0.45rem 1rem;
  font-size: 0.8rem;
  font-weight: 500;
  border-radius: var(--radius-full);
  background: var(--bg-primary);
  border: 1px solid var(--color-gray-200);
  color: var(--text-secondary);
  text-decoration: none;
  transition: all 0.15s ease;
}
.quick-action-btn:hover {
  background: var(--color-gray-100);
  border-color: var(--color-gray-300);
  color: var(--text-primary);
  transform: translateY(-1px);
}
.quick-action-btn i {
  font-size: 0.75rem;
  width: 18px;
  text-align: center;
}
.chart-grid {
  display: grid;
  gap: var(--space-4);
  margin-bottom: var(--space-6);
}
@media (min-width: 768px) {
  .chart-grid {
    grid-template-columns: 1fr 1fr;
  }
}
.chart-box {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-gray-200);
  padding: var(--space-4);
}
.chart-box h4 {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-secondary);
  margin: 0 0 var(--space-3);
  display: flex;
  align-items: center;
  gap: var(--space-2);
}
.chart-box h4 i {
  font-size: 0.8rem;
}
.chart-wrap {
  position: relative;
  height: 200px;
}
#dashboardGridRight {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}
.task-priority-badge {
  font-size: 0.6rem;
  padding: 0.15rem 0.5rem;
  border-radius: var(--radius-full);
  font-weight: 600;
  text-transform: uppercase;
}
.task-item {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-2) var(--space-4);
  border-bottom: 1px solid var(--color-gray-100);
  transition: background 0.12s;
}
.task-item:hover {
  background: rgba(229,168,53,0.04);
}
.task-item:last-child {
  border-bottom: none;
}
.task-item .task-status {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
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
        <?php $title = 'Dashboard'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>

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
                                <span><strong><?= number_format($totalProd + ($stats['total_clientes'] ?? 0) + ($stats['total_proveedores'] ?? 0) + ($stats['total_trabajadores'] ?? 0) + ($stats['total_insumos'] ?? 0) + ($stats['total_herramientas'] ?? 0) + ($stats['total_ventas'] ?? 0)) ?></strong> registros totales</span>
                            </div>
                            <?php if ($totalAlerts > 0): ?>
                            <div class="welcome-stat">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span><strong><?= $totalAlerts ?></strong> alertas</span>
                            </div>
                            <?php endif; ?>
                            <?php if (($stats['total_tareas_pendientes'] ?? 0) > 0): ?>
                            <div class="welcome-stat">
                                <i class="fas fa-tasks"></i>
                                <span><strong><?= $stats['total_tareas_pendientes'] ?></strong> tareas pendientes</span>
                            </div>
                            <?php endif; ?>
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

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="<?= BASE_URL ?>dashboard/plantas" class="quick-action-btn"><i class="fas fa-plus-circle" style="color:var(--color-secondary);"></i> Nueva Planta</a>
                <a href="<?= BASE_URL ?>dashboard/lotes" class="quick-action-btn"><i class="fas fa-plus-circle" style="color:#e5a835;"></i> Nuevo Lote</a>
                <a href="<?= BASE_URL ?>dashboard/supplies" class="quick-action-btn"><i class="fas fa-plus-circle" style="color:#f59e0b;"></i> Nuevo Insumo</a>
                <a href="<?= BASE_URL ?>dashboard/clientes" class="quick-action-btn"><i class="fas fa-plus-circle" style="color:#2e7d32;"></i> Nuevo Cliente</a>
                <a href="<?= BASE_URL ?>dashboard/tasks" class="quick-action-btn"><i class="fas fa-plus-circle" style="color:#0ea5e9;"></i> Nueva Tarea</a>
                <a href="<?= BASE_URL ?>dashboard/ventas" class="quick-action-btn"><i class="fas fa-shopping-cart" style="color:#2e7d32;"></i> Nueva Venta</a>
                <a href="<?= BASE_URL ?>dashboard/reports" class="quick-action-btn"><i class="fas fa-chart-bar" style="color:#8b5cf6;"></i> Reportes</a>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">

                <div class="section-divider"><i class="fas fa-seedling"></i> Producción</div>

                <a href="<?= BASE_URL ?>dashboard/plantas" class="text-decoration-none">
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

                <a href="<?= BASE_URL ?>dashboard/especies" class="text-decoration-none">
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

                <a href="<?= BASE_URL ?>dashboard/lotes" class="text-decoration-none">
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

                <a href="<?= BASE_URL ?>dashboard/tasks" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Tareas Pendientes</span>
                        <div class="kpi-card-icon info"><i class="fas fa-tasks"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_tareas_pendientes'] ?? 0) ?></div>
                    <div class="kpi-card-change <?= ($stats['total_tareas_pendientes'] ?? 0) > 0 ? 'negative' : 'positive' ?>">
                        <i class="fas fa-arrow-right"></i> Ver tareas
                    </div>
                </div>
                </a>

                <div class="section-divider"><i class="fas fa-boxes-stacked"></i> Inventario y Suministros</div>

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

                <a href="<?= BASE_URL ?>dashboard/tools" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Herramientas</span>
                        <div class="kpi-card-icon primary"><i class="fas fa-wrench"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_herramientas'] ?? 0) ?></div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ver todas
                    </div>
                </div>
                </a>

                <a href="<?= BASE_URL ?>dashboard/inventario" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Stock General</span>
                        <div class="kpi-card-icon warning"><i class="fas fa-chart-simple"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_lotes'] ?? 0) ?></div>
                    <div class="kpi-card-change <?= $totalAlerts > 0 ? 'negative' : 'positive' ?>">
                        <i class="fas fa-arrow-right"></i> <?= $totalAlerts > 0 ? "$totalAlerts alertas" : 'Sin alertas' ?>
                    </div>
                </div>
                </a>

                <div class="section-divider"><i class="fas fa-handshake"></i> Comercial</div>

                <a href="<?= BASE_URL ?>dashboard/clientes" class="text-decoration-none">
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

                <a href="<?= BASE_URL ?>dashboard/ventas" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Ventas</span>
                        <div class="kpi-card-icon success"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_ventas'] ?? 0) ?></div>
                    <div class="kpi-sub">Bs. <?= number_format($stats['total_ventas_bs'] ?? 0, 2) ?> vendido</div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ir a ventas
                    </div>
                </div>
                </a>

                <a href="<?= BASE_URL ?>dashboard/compras" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Compras</span>
                        <div class="kpi-card-icon primary"><i class="fas fa-file-invoice"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_compras'] ?? 0) ?></div>
                    <div class="kpi-sub">Bs. <?= number_format($stats['total_compras_bs'] ?? 0, 2) ?> invertido</div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ver todas
                    </div>
                </div>
                </a>

                <a href="<?= BASE_URL ?>dashboard/cuentas_cobrar" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Cuentas x Cobrar</span>
                        <div class="kpi-card-icon warning"><i class="fas fa-hand-holding-usd"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_cuentas_cobrar'] ?? 0) ?></div>
                    <div class="kpi-sub">Bs. <?= number_format(($stats['total_ventas_bs'] ?? 0) - ($stats['total_cobrado'] ?? 0), 2) ?> pendiente</div>
                    <div class="kpi-card-change <?= ($stats['total_cuentas_cobrar'] ?? 0) > 0 ? 'negative' : 'positive' ?>">
                        <i class="fas fa-arrow-right"></i> Ver cuentas
                    </div>
                </div>
                </a>

                <div class="section-divider"><i class="fas fa-cog"></i> Personal y Configuración</div>

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

                <a href="<?= BASE_URL ?>dashboard/proveedores" class="text-decoration-none">
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

                <a href="<?= BASE_URL ?>dashboard/prices" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Precios Vigentes</span>
                        <div class="kpi-card-icon warning"><i class="fas fa-tag"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= number_format($stats['total_precios_vigentes'] ?? 0) ?></div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ver precios
                    </div>
                </div>
                </a>

                <a href="<?= BASE_URL ?>dashboard/reports" class="text-decoration-none">
                <div class="kpi-card">
                    <div class="kpi-card-header">
                        <span class="kpi-card-title">Reportes</span>
                        <div class="kpi-card-icon info"><i class="fas fa-chart-bar"></i></div>
                    </div>
                    <div class="kpi-card-value"><?= count($stats) ?></div>
                    <div class="kpi-sub">métricas disponibles</div>
                    <div class="kpi-card-change positive">
                        <i class="fas fa-arrow-right"></i> Ver reportes
                    </div>
                </div>
                </a>

            </div>

            <!-- Charts Row -->
            <div class="chart-grid">
                <div class="chart-box">
                    <h4><i class="fas fa-leaf" style="color:var(--color-secondary);"></i> Plantas por Especie</h4>
                    <div class="chart-wrap">
                        <canvas id="chartPlantsBySpecies"></canvas>
                    </div>
                </div>
                <div class="chart-box">
                    <h4><i class="fas fa-boxes" style="color:#e5a835;"></i> Nivel de Inventario</h4>
                    <div class="chart-wrap">
                        <canvas id="chartInventorySummary"></canvas>
                    </div>
                </div>
            </div>

            <!-- Activity + Alerts + Tasks -->
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

                <div id="dashboardGridRight">
                    <!-- Pending Tasks -->
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h3 class="dashboard-card-title" style="display:flex;align-items:center;gap:var(--space-2);">
                                <span style="width:28px;height:28px;border-radius:var(--radius-md);background:rgba(14,165,233,0.12);display:flex;align-items:center;justify-content:center;color:#0ea5e9;font-size:0.8rem;">
                                    <i class="fas fa-tasks"></i>
                                </span>
                                Tareas Pendientes
                            </h3>
                            <a href="<?= BASE_URL ?>dashboard/tasks" class="text-decoration-none" style="font-size:0.75rem;color:#0ea5e9;font-weight:500;">Ver todas</a>
                        </div>
                        <div class="dashboard-card-body" style="padding:0;">
                            <?php if (empty($pendingTasks)): ?>
                                <div class="text-center py-4" style="color:var(--text-muted);">
                                    <i class="fas fa-check-circle mb-2 d-block" style="font-size:2rem;color:var(--color-success);opacity:0.4;"></i>
                                    <p style="font-size:0.85rem;margin:0;color:var(--color-success);">No hay tareas pendientes</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($pendingTasks as $task): ?>
                                <div class="task-item">
                                    <span class="task-status" style="background:<?= match($task['estatus_tarea'] ?? '') {
                                        'en_proceso' => '#0ea5e9',
                                        'pendiente' => '#f59e0b',
                                        'pausada' => '#8b5cf6',
                                        default => '#6b7280',
                                    } ?>"></span>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:0.85rem;font-weight:500;color:var(--text-primary);"><?= htmlspecialchars($task['nombre_tarea'] ?? '') ?></div>
                                        <div style="font-size:0.7rem;color:var(--text-muted);">
                                            <?= htmlspecialchars($task['nombre_trabajador'] ?? 'Sin asignar') ?>
                                            <?php if (!empty($task['fecha_cumplimiento'])): ?>
                                                · Vence: <?= htmlspecialchars((new DateTime($task['fecha_cumplimiento']))->format('d/m/Y')) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="task-priority-badge" style="background:<?= match($task['estatus_tarea'] ?? '') {
                                        'en_proceso' => 'rgba(14,165,233,0.12);color:#0ea5e9',
                                        'pendiente' => 'rgba(245,158,11,0.12);color:#f59e0b',
                                        'pausada' => 'rgba(139,92,246,0.12);color:#8b5cf6',
                                        default => 'rgba(107,114,128,0.12);color:#6b7280',
                                    } ?>"><?= htmlspecialchars($task['estatus_tarea'] ?? '') ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Low Stock Alerts -->
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
                                <div class="text-center py-4" style="color:var(--text-muted);">
                                    <i class="fas fa-check-circle mb-2 d-block" style="font-size:2rem;color:var(--color-success);opacity:0.4;"></i>
                                    <p style="font-size:0.85rem;margin:0;font-weight:500;color:var(--color-success);">Todo en niveles óptimos</p>
                                </div>
                            <?php else: ?>
                                <?php if (!empty($lowStockLots)): ?>
                                <div style="padding:var(--space-2) var(--space-4);font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid var(--color-gray-100);">
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
                                <div style="padding:var(--space-2) var(--space-4);font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid var(--color-gray-100);<?= !empty($lowStockLots) ? 'border-top:1px solid var(--color-gray-100);margin-top:var(--space-1);padding-top:var(--space-2) !important;' : '' ?>">
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

    <script>
        window.plantsBySpecies = <?= json_encode($plantsBySpecies) ?>;
        window.inventorySummary = <?= json_encode($inventorySummary) ?>;
    </script>
    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script type="module" src="<?= BASE_URL ?>public/assets/js/dashboard/dashboard.js"></script>
</body>
</html>
