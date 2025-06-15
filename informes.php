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

// Informe: Ventas totales por producto
$stmt = $pdo->query("
    SELECT p.nombre, SUM(vi.cantidad) AS total_vendido, SUM(vi.cantidad * vi.precio_unitario) AS total_ingresos
    FROM ventas_items vi
    JOIN productos p ON vi.id_producto = p.id
    GROUP BY p.id
    ORDER BY total_vendido DESC
");
$ventasPorProducto = $stmt->fetchAll();

// Informe: Ventas totales por empleado
$stmt = $pdo->query("
    SELECT u.nombre, COUNT(v.id) AS ventas_realizadas, SUM(v.total) AS total_ingresos
    FROM ventas v
    JOIN usuarios u ON v.id_usuario = u.id
    GROUP BY u.id
    ORDER BY ventas_realizadas DESC
");
$ventasPorEmpleado = $stmt->fetchAll();

// Total de ventas generales
$stmt = $pdo->query("SELECT COUNT(*) AS total_ventas, SUM(total) AS ingresos_totales FROM ventas");
$totales = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes - MiniMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .sidebar { position: fixed; top: 56px; left: 0; height: calc(100vh - 56px); width: 250px; background: white; box-shadow: 2px 0 4px rgba(0,0,0,0.1); z-index: 1000; }
        .main-content { margin-left: 250px; padding: 2rem; }
        .nav-link { color: #495057; padding: 12px 20px; border-radius: 8px; margin: 2px 10px; transition: all 0.3s ease; }
        .nav-link:hover { background: #007bff; color: white; }
        .nav-link.active { background: #007bff; color: white; }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; }
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
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['nombre']) ?>
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
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="productos.php"><i class="fas fa-box me-2"></i>Productos</a></li>
                <li class="nav-item"><a class="nav-link" href="ventas.php"><i class="fas fa-shopping-cart me-2"></i>Ventas</a></li>
                <li class="nav-item"><a class="nav-link" href="cajas.php"><i class="fas fa-cash-register me-2"></i>Cajas</a></li>
                <li class="nav-item"><a class="nav-link active" href="informes.php"><i class="fas fa-chart-bar me-2"></i>Informes</a></li>
                <?php if ($_SESSION['rol'] === 'jefe'): ?>
                <li class="nav-item"><a class="nav-link" href="empleados.php"><i class="fas fa-users me-2"></i>Empleados</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2><i class="fas fa-chart-bar me-2 text-warning"></i>Informes y Reportes</h2>

        <!-- Resumen General -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h3><?= $totales['total_ventas'] ?></h3>
                        <p>Total de Ventas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h3>$<?= number_format($totales['ingresos_totales'], 2) ?></h3>
                        <p>Ingresos Totales</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ventas por Producto -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-box me-2"></i>Ventas por Producto</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Producto</th><th>Cantidad Vendida</th><th>Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventasPorProducto as $vp): ?>
                            <tr>
                                <td><?= htmlspecialchars($vp['nombre']) ?></td>
                                <td><span class="badge bg-primary"><?= $vp['total_vendido'] ?></span></td>
                                <td><strong>$<?= number_format($vp['total_ingresos'], 2) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ventas por Empleado -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-users me-2"></i>Ventas por Empleado</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Empleado</th><th>Ventas Realizadas</th><th>Ingresos Generados</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventasPorEmpleado as $ve): ?>
                            <tr>
                                <td><?= htmlspecialchars($ve['nombre']) ?></td>
                                <td><span class="badge bg-success"><?= $ve['ventas_realizadas'] ?></span></td>
                                <td><strong>$<?= number_format($ve['total_ingresos'], 2) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
