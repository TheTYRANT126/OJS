<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Portal AMCAD'; ?></title>

    <!-- Google Fonts - AMCAD -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Andika+New+Basic:wght@400;700&display=swap" rel="stylesheet">

    <!-- Tus estilos -->
    <link rel="stylesheet" href="css/main.css">

    <?php if (isset($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header AMCAD -->
    <header class="amcad-header">
        <div class="container">
            <div class="header-wrapper">
                <div class="header-top-row">
                    <div class="logo">
                        <a href="index.php">
                            <h1 style="font-family: 'Playfair Display', serif; color: #0170B9; margin: 0;">
                                AMCAD
                            </h1>
                        </a>
                    </div>
                    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mainNav">
                        <span class="menu-toggle-bar"></span>
                        <span class="menu-toggle-bar"></span>
                        <span class="menu-toggle-bar"></span>
                    </button>
                </div>

                <nav class="main-nav" id="mainNav">
                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="busqueda.php">Búsqueda</a></li>
                        <li><a href="catalogo.php">Catálogo</a></li>
                        <li><a href="../index.php/index">OJS Admin</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
