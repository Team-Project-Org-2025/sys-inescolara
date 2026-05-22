<?php
include_once __DIR__ . '/../common/links.php';
$title = 'Notificaciones';
$currentPage = 'notifications';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - INECOLARA</title>
    <?= $css_links ?>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php include_once __DIR__ . '/../partials/sidebar.php'; ?>
    <main class="main-content">
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        <div class="dashboard-content">
            <div class="notif-page-header">
                <div>
                    <h1 class="dashboard-page-title">Notificaciones</h1>
                    <p class="dashboard-page-subtitle">Todas tus notificaciones en un solo lugar.</p>
                </div>
                <button class="btn btn-outline" id="markAllBtn" onclick="marcarTodasLeidas()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 12 8 19 23 4"></polyline></svg>
                    Marcar todas como leídas
                </button>
            </div>

            <div class="notif-list" id="notifList">
                <?php if (empty($notifications)): ?>
                    <div class="notif-empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--text-muted)"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <p>No tienes notificaciones.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <div class="notif-item <?= $n['leida'] ? 'read' : 'unread' ?>" data-id="<?= $n['id_notificacion'] ?>">
                            <div class="notif-icon notif-<?= htmlspecialchars($n['tipo'] ?? 'info') ?>">
                                <?php
                                $icon = match ($n['tipo'] ?? 'info') {
                                    'success' => '<polyline points="22 4 8 18 2 12"></polyline>',
                                    'warning' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
                                    'task_assigned' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
                                    default => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
                                };
                                ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $icon ?></svg>
                            </div>
                            <div class="notif-body">
                                <div class="notif-title"><?= htmlspecialchars($n['titulo']) ?></div>
                                <?php if ($n['mensaje']): ?>
                                    <div class="notif-message"><?= htmlspecialchars($n['mensaje']) ?></div>
                                <?php endif; ?>
                                <div class="notif-time"><?= date('d/m/Y H:i', strtotime($n['fecha_creacion'])) ?></div>
                            </div>
                            <div class="notif-actions">
                                <?php if (!$n['leida']): ?>
                                    <button class="notif-mark-btn" onclick="marcarLeida(<?= $n['id_notificacion'] ?>)" title="Marcar como leída">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 12 8 19 23 4"></polyline></svg>
                                    </button>
                                <?php endif; ?>
                                <button class="notif-delete-btn" onclick="eliminarNotif(<?= $n['id_notificacion'] ?>)" title="Eliminar">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="notif-pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="pagination-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?= $scripts_links ?>
    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
</body>
</html>
