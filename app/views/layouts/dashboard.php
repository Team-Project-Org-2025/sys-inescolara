<?php

/**
 * Layout para el dashboard (área interna)
 * Variables esperadas: $title, $content, $user, $currentPage
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Dashboard') ?> - INECOLARA</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>public/assets/images/favicon.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="dashboard-body">
    <div class="dashboard-layout">
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <div class="dashboard-main">
            <?php include __DIR__ . '/../partials/dashboard-header.php'; ?>

            <main class="dashboard-content">
                <?= $content ?>
            </main>
        </div>
    </div>

    <script src="<?= BASE_URL ?>public/assets/js/data.js"></script>
    <script src="<?= BASE_URL ?>public/assets/js/main.js"></script>
</body>

</html>