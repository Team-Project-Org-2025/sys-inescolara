<?php

$css_links = '
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="' . BASE_URL . 'public/assets/images/favicon.ico">

    <!-- Google Fonts (Inter + Playfair Display) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <!-- CSS Principal (Design System) -->
    <link rel="stylesheet" href="' . BASE_URL . 'public/assets/css/styles.css">
    
    <!-- CSS del Sidebar -->
    <link rel="stylesheet" href="' . BASE_URL . 'public/assets/css/sidebar.css">
';

// JS Links (cargados al final del body)
$scripts_links = '
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- BASE_URL para JavaScript -->
    <script>window.BASE_URL = "' . BASE_URL . '";</script>
    
    <!-- Utils Globales -->
    <script src="' . BASE_URL . 'public/assets/js/utils/skeleton.js"></script>
    
    <!-- Script del Sidebar -->
    <script src="' . BASE_URL . 'public/assets/js/sidebar.js"></script>

    <!-- Bootstrap 5 jQuery Compatibility Bridge -->
    <script src="' . BASE_URL . 'public/assets/js/utils/bs5-jquery-bridge.js"></script>

    <!-- Maxlength Character Counter -->
    <script src="' . BASE_URL . 'public/assets/js/utils/maxlength-counter.js"></script>

    <!-- Google reCAPTCHA v2 -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
';
