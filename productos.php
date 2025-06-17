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

// Eliminar producto
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Establecer variable de usuario para triggers
        $pdo->exec("SET @user_id = " . $_SESSION['id_usuario']);
        
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        if ($stmt->execute([$_GET['delete']])) {
            header('Location: productos.php?msg=deleted');
            exit;
        }
    } catch (Exception $e) {
        error_log("Error al eliminar producto: " . $e->getMessage());
        header('Location: productos.php?msg=error_delete');
        exit;
    }
}

// Editar producto - CON MANEJO DE TRIGGERS
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_producto'])) {
    try {
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $categoria = trim($_POST['categoria']);
        $precio = floatval($_POST['precio']);
        $stock = intval($_POST['stock']);

        // Validaciones básicas
        if (empty($nombre)) {
            throw new Exception("El nombre del producto es requerido");
        }
        
        if ($precio <= 0) {
            throw new Exception("El precio debe ser mayor a 0");
        }
        
        if ($stock < 0) {
            throw new Exception("El stock no puede ser negativo");
        }

        // IMPORTANTE: Establecer variable de usuario para triggers
        $pdo->exec("SET @user_id = " . $_SESSION['id_usuario']);

        // Actualizar producto
        $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, categoria = ?, precio = ?, stock = ? WHERE id = ?");
        $result = $stmt->execute([$nombre, $categoria, $precio, $stock, $id]);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Error en la base de datos: " . $errorInfo[2]);
        }

        $mensaje = "Producto actualizado correctamente.";

    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        error_log("ERROR en productos.php (editar): " . $e->getMessage());
    }
}

// Agregar producto - CON MANEJO DE TRIGGERS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_producto'])) {
    try {
        $nombre = trim($_POST['nombre']);
        $categoria = trim($_POST['categoria']);
        $precio = floatval($_POST['precio']);
        $stock = intval($_POST['stock']);

        // Validaciones básicas
        if (empty($nombre)) {
            throw new Exception("El nombre del producto es requerido");
        }
        
        if ($precio <= 0) {
            throw new Exception("El precio debe ser mayor a 0");
        }
        
        if ($stock < 0) {
            throw new Exception("El stock no puede ser negativo");
        }

        // IMPORTANTE: Establecer variable de usuario para triggers
        $pdo->exec("SET @user_id = " . $_SESSION['id_usuario']);

        $stmt = $pdo->prepare("INSERT INTO productos (nombre, categoria, precio, stock) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$nombre, $categoria, $precio, $stock]);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Error en la base de datos: " . $errorInfo[2]);
        }

        $mensaje = "Producto agregado correctamente.";

    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        error_log("ERROR en productos.php (agregar): " . $e->getMessage());
    }
}

// Mensajes de URL
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'deleted':
            $mensaje = "Producto eliminado correctamente.";
            break;
        case 'error_delete':
            $mensaje = "Error: No se pudo eliminar el producto.";
            break;
    }
}

// Listar productos
$stmt = $pdo->query("SELECT * FROM productos ORDER BY nombre");
$productos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - MiniMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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

    <!-- Botón Hamburguesa para Mobile -->
    <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Abrir menú">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Overlay para cerrar menú en mobile -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="p-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="productos.php"><i class="fas fa-box me-2"></i>Productos</a></li>
                <li class="nav-item"><a class="nav-link" href="ventas.php"><i class="fas fa-shopping-cart me-2"></i>Ventas</a></li>
                <li class="nav-item"><a class="nav-link" href="cajas.php"><i class="fas fa-cash-register me-2"></i>Cajas</a></li>
                <li class="nav-item"><a class="nav-link" href="informes.php"><i class="fas fa-chart-bar me-2"></i>Informes</a></li>
                <?php if ($_SESSION['rol'] === 'jefe'): ?>
                <li class="nav-item"><a class="nav-link" href="empleados.php"><i class="fas fa-users me-2"></i>Empleados</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="fas fa-box me-2 text-primary"></i>Gestión de Productos</h2>
                    <p class="text-muted mb-0">Administra tu inventario de productos</p>
                </div>
                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-2"></i>Agregar Producto
                </button>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert <?= strpos($mensaje, 'Error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show" role="alert">
                <i class="fas <?= strpos($mensaje, 'Error') !== false ? 'fa-exclamation-triangle' : 'fa-check-circle' ?> me-2"></i><?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabla de productos -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><?= htmlspecialchars($p['nombre']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($p['categoria']) ?></span></td>
                                <td>$<?= number_format($p['precio'], 2) ?></td>
                                <td>
                                    <span class="badge <?= $p['stock'] > 10 ? 'bg-success' : ($p['stock'] > 0 ? 'bg-warning' : 'bg-danger') ?>">
                                        <?= $p['stock'] ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            data-id="<?= $p['id'] ?>"
                                            data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                            data-categoria="<?= htmlspecialchars($p['categoria']) ?>"
                                            data-precio="<?= $p['precio'] ?>"
                                            data-stock="<?= $p['stock'] ?>"
                                            onclick="editProduct(this)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Producto -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Agregar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="agregar_producto" value="1">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <input type="text" class="form-control" name="categoria">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="precio" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" min="0" class="form-control" name="stock" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agregar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Producto -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="editar_producto" value="1">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <input type="text" class="form-control" name="categoria" id="edit_categoria">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="precio" id="edit_precio" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" min="0" class="form-control" name="stock" id="edit_stock" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function editProduct(button) {
            const id = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const categoria = button.getAttribute('data-categoria');
            const precio = button.getAttribute('data-precio');
            const stock = button.getAttribute('data-stock');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_categoria').value = categoria;
            document.getElementById('edit_precio').value = precio;
            document.getElementById('edit_stock').value = stock;
            
            const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
            modal.show();
        }

        function deleteProduct(id, nombre) {
            if (confirm(`¿Estás seguro de que deseas eliminar el producto "${nombre}"?`)) {
                window.location.href = `productos.php?delete=${id}`;
            }
        }
    </script>
</body>
</html>
