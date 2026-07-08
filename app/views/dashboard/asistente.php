<?php
include_once __DIR__ . '/../common/links.php';

$bgImage = BASE_URL . 'public/assets/images/bg-chat.jpg';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente IA - INECOLARA</title>
    <?= $css_links ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/asistente.css">
    <style>
        main.main-content {
            height: 100vh;
            overflow: hidden;
            background: url('<?= $bgImage ?>') center/cover fixed !important;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        main.main-content::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.55);
            z-index: 0;
            pointer-events: none;
        }
        main.main-content > * {
            position: relative;
            z-index: 1;
        }
        .dashboard-content#chatbotRoot {
            padding: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
    </style>
</head>
<body>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php 
    $currentPage = 'asistente';
    include_once __DIR__ . '/../partials/sidebar.php'; 
    ?>
    
    <main class="main-content">
        <?php $title = 'Asistente IA'; ?>
        <?php include_once __DIR__ . '/../partials/dashboard-header.php'; ?>
        
        <div class="dashboard-content" id="chatbotRoot"></div>
    </main>
    
    <script src="<?= BASE_URL ?>public/assets/js/dashboard/notifications.js"></script>
    <?= $scripts_links ?>
    <script src="<?= BASE_URL ?>public/assets/js/asistente.js"></script>
</body>
</html>
