<?php
require_once 'controllers/AuthController.php';
$authController = new AuthController();
?>

<nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <?php if ($authController->hasPermission('productos')): ?>
            <li class="nav-item">
                <a class="nav-link" href="productos.php">
                    <i class="fas fa-box"></i> Productos
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($authController->hasPermission('ventas')): ?>
            <li class="nav-item">
                <a class="nav-link" href="ventas.php">
                    <i class="fas fa-shopping-cart"></i> Ventas
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($authController->hasPermission('cajas')): ?>
            <li class="nav-item">
                <a class="nav-link" href="cajas.php">
                    <i class="fas fa-cash-register"></i> Cajas
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($authController->hasPermission('informes')): ?>
            <li class="nav-item">
                <a class="nav-link" href="informes.php">
                    <i class="fas fa-chart-bar"></i> Informes
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($authController->hasPermission('empleados')): ?>
            <li class="nav-item">
                <a class="nav-link" href="empleados.php">
                    <i class="fas fa-users"></i> Empleados
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
