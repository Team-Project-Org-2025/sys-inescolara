<?php

/**
 * Layout principal para páginas públicas
 * Variables esperadas: $title, $content
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Vivero Inecolara') ?> - INECOLARA</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main>
        <?= $content ?>
    </main>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <script src="<?= BASE_URL ?>public/assets/js/data.js"></script>
    <script src="<?= BASE_URL ?>public/assets/js/main.js"></script>
</body>

</html>