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

$mensaje = '';

// Verificar caja abierta
$stmt = $pdo->prepare("SELECT * FROM cajas WHERE estado = 'abierta' AND id_usuario = ?");
$stmt->execute([$_SESSION['id_usuario']]);
$cajaAbierta = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['abrir_caja'])) {
        $monto = floatval($_POST['monto_apertura']);
        if ($monto >= 0) {
            $stmt = $pdo->prepare("INSERT INTO cajas (id_usuario, monto_apertura, estado) VALUES (?, ?, 'abierta')");
            if ($stmt->execute([$_SESSION['id_usuario'], $monto])) {
                $mensaje = "Caja abierta correctamente.";
                header("Refresh:2");
            } else {
                $mensaje = "Error al abrir caja.";
            }
        } else {
            $mensaje = "Monto de apertura inválido.";
        }
    } elseif (isset($_POST['cerrar_caja'])) {
        $montoCierre = floatval($_POST['monto_cierre']);
        $stmt = $pdo->prepare("UPDATE cajas SET fecha_cierre = NOW(), monto_cierre = ?, estado = 'cerrada' WHERE id = ?");
        if ($stmt->execute([$montoCierre, $cajaAbierta['id']])) {
            $mensaje = "Caja cerrada correctamente.";
            header("Refresh:2");
        } else {
            $mensaje = "Error al cerrar caja.";
        }
    }
}

// Historial de cajas
$stmt = $pdo->prepare("SELECT * FROM cajas WHERE id_usuario = ? ORDER BY fecha_apertura DESC LIMIT 10");
$stmt->execute([$_SESSION['id_usuario']]);
$historialCajas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cajas - MiniMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .sidebar { position: fixed; top: 56px; left: 0; height: calc(100vh - 56px); width: 250px; background: white; box-shadow: 2px 0 4px rgba(0,0,0,0.1); z-index: 1000; }
        .main-content { margin-left: 250px; padding: 2rem; }
        .nav-link { color: #495057; padding: 12px 20px; border-radius: 8px; margin: 2px 10px; transition: all 0.3s ease; }
        .nav-link:hover { background: #007bff; color: white; }
        .nav-link.active { background: #007bff; color: white; }
        .cash-status { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 15px; }
        .cash-closed { background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); }
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
                <li class="nav-item"><a class="nav-link active" href="cajas.php"><i class="fas fa-cash-register me-2"></i>Cajas</a></li>
                <li class="nav-item"><a class="nav-link" href="informes.php"><i class="fas fa-chart-bar me-2"></i>Informes</a></li>
                <?php if ($_SESSION['rol'] === 'jefe'): ?>
                <li class="nav-item"><a class="nav-link" href="empleados.php"><i class="fas fa-users me-2"></i>Empleados</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2><i class="fas fa-cash-register me-2 text-success"></i>Gestión de Cajas</h2>

        <?php if ($mensaje): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estado de la caja -->
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="card <?= $cajaAbierta ? 'cash-status' : 'cash-closed' ?>">
                    <div class="card-body text-center">
                        <i class="fas fa-cash-register fa-3x mb-3"></i>
                        <h3><?= $cajaAbierta ? 'Caja Abierta' : 'Caja Cerrada' ?></h3>
                        <?php if ($cajaAbierta): ?>
                            <p>Apertura: <?= date('d/m/Y H:i', strtotime($cajaAbierta['fecha_apertura'])) ?></p>
                            <p>Monto inicial: $<?= number_format($cajaAbierta['monto_apertura'], 2) ?></p>
                        <?php else: ?>
                            <p>No hay caja abierta actualmente</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <?php if (!$cajaAbierta): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-unlock me-2"></i>Abrir Caja</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Monto de Apertura</label>
                                    <input type="number" step="0.01" class="form-control" name="monto_apertura" required>
                                </div>
                                <button type="submit" name="abrir_caja" class="btn btn-success w-100">
                                    <i class="fas fa-unlock me-2"></i>Abrir Caja
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-lock me-2"></i>Cerrar Caja</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Monto de Cierre</label>
                                    <input type="number" step="0.01" class="form-control" name="monto_cierre" required>
                                </div>
                                <button type="submit" name="cerrar_caja" class="btn btn-danger w-100">
                                    <i class="fas fa-lock me-2"></i>Cerrar Caja
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historial -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Historial de Cajas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th><th>Apertura</th><th>Cierre</th><th>Monto Inicial</th><th>Monto Final</th><th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historialCajas as $caja): ?>
                            <tr>
                                <td>#<?= $caja['id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($caja['fecha_apertura'])) ?></td>
                                <td><?= $caja['fecha_cierre'] ? date('d/m/Y H:i', strtotime($caja['fecha_cierre'])) : '-' ?></td>
                                <td>$<?= number_format($caja['monto_apertura'], 2) ?></td>
                                <td><?= $caja['monto_cierre'] ? '$' . number_format($caja['monto_cierre'], 2) : '-' ?></td>
                                <td>
                                    <span class="badge <?= $caja['estado'] === 'abierta' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($caja['estado']) ?>
                                    </span>
                                </td>
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
