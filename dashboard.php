<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

// Obtener conexión usando el patrón Singleton
$db = Database::getInstance();
$pdo = $db->getConnection();

// Ventas del día
$stmt = $pdo->prepare("SELECT COUNT(*) as ventas_hoy FROM ventas WHERE DATE(fecha) = CURDATE()");
$stmt->execute();
$ventasHoy = $stmt->fetch()['ventas_hoy'];

// Productos en stock (suma de todos los stocks)
$stmt = $pdo->query("SELECT SUM(stock) as total_stock FROM productos");
$totalStock = $stmt->fetch()['total_stock'] ?? 0;

// Empleados activos
$stmt = $pdo->query("SELECT COUNT(*) as empleados FROM usuarios WHERE rol = 'empleado'");
$empleados = $stmt->fetch()['empleados'];

// Pedidos pendientes (simulado, por ejemplo ventas sin fecha_cierre, si aplica)
// Lo dejamos 0 por ahora
$pedidosPendientes = 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MiniMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 56px; /* Espacio para navbar fijo */
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            height: calc(100vh - 56px);
            width: 250px;
            background: white;
            box-shadow: 2px 0 4px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: calc(100vh - 56px);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .stats-card.sales {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stats-card.products {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .stats-card.employees {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .stats-card.orders {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }
        .stats-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 0;
        }
        .stats-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .nav-link {
            color: #495057;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            background: #007bff;
            color: white;
            transform: translateX(5px);
        }
        .nav-link.active {
            background: #007bff;
            color: white;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-store me-2"></i>MiniMarket
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    <?= htmlspecialchars($_SESSION['nombre']) ?>
                    <small class="text-light">(<?= ucfirst($_SESSION['rol']) ?>)</small>
                </span>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Salir
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="productos.php">
                        <i class="fas fa-box me-2"></i>Productos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ventas.php">
                        <i class="fas fa-shopping-cart me-2"></i>Ventas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cajas.php">
                        <i class="fas fa-cash-register me-2"></i>Cajas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="informes.php">
                        <i class="fas fa-chart-bar me-2"></i>Informes
                    </a>
                </li>
                <?php if ($_SESSION['rol'] === 'jefe'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="empleados.php">
                        <i class="fas fa-users me-2"></i>Empleados
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1><i class="fas fa-chart-line me-2"></i>Dashboard</h1>
            <p class="mb-0">Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>. Acá tenes un resumen del supermercado.</p>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card stats-card sales h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-2x mb-3"></i>
                        <h2 class="stats-number"><?= $ventasHoy ?></h2>
                        <p class="stats-label mb-0">Ventas Hoy</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card products h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-boxes fa-2x mb-3"></i>
                        <h2 class="stats-number"><?= number_format($totalStock) ?></h2>
                        <p class="stats-label mb-0">Productos en Stock</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card employees h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-3"></i>
                        <h2 class="stats-number"><?= $empleados ?></h2>
                        <p class="stats-label mb-0">Empleados</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card orders h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-3"></i>
                        <h2 class="stats-number"><?= $pedidosPendientes ?></h2>
                        <p class="stats-label mb-0">Pedidos Pendientes</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-4 mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-plus-circle me-2 text-primary"></i>Acciones Rápidas</h5>
                    <div class="d-grid gap-2">
                        <a href="productos.php" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>Agregar Producto
                        </a>
                        <a href="ventas.php" class="btn btn-outline-success">
                            <i class="fas fa-shopping-cart me-2"></i>Nueva Venta
                        </a>
                        <a href="cajas.php" class="btn btn-outline-warning">
                            <i class="fas fa-cash-register me-2"></i>Gestionar Caja
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-info-circle me-2 text-info"></i>Información del Sistema</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-calendar me-2 text-muted"></i>Fecha: <?= date('d/m/Y') ?></li>
                        <li><i class="fas fa-clock me-2 text-muted"></i>Hora: <span class="current-time"><?= date('H:i:s') ?></span></li>
                        <li><i class="fas fa-user me-2 text-muted"></i>Usuario: <?= htmlspecialchars($_SESSION['usuario']) ?></li>
                        <li><i class="fas fa-shield-alt me-2 text-muted"></i>Rol: <?= ucfirst($_SESSION['rol']) ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Actualizar hora en tiempo real
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            const timeElements = document.querySelectorAll('.current-time');
            timeElements.forEach(el => el.textContent = timeString);
        }
        
        setInterval(updateTime, 1000);
        
        // Animación de las tarjetas
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stats-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>
