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

// Procesar nueva venta
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_venta'])) {
    $id_cliente = !empty($_POST['id_cliente']) ? intval($_POST['id_cliente']) : null;
    $productos = $_POST['productos'] ?? [];
    $cantidades = $_POST['cantidades'] ?? [];
    
    if (!empty($productos) && !empty($cantidades)) {
        try {
            $pdo->beginTransaction();
            
            // Calcular total
            $total = 0;
            $items_validos = [];
            
            for ($i = 0; $i < count($productos); $i++) {
                if (!empty($productos[$i]) && !empty($cantidades[$i]) && $cantidades[$i] > 0) {
                    $stmt = $pdo->prepare("SELECT precio, stock FROM productos WHERE id = ?");
                    $stmt->execute([$productos[$i]]);
                    $producto = $stmt->fetch();
                    
                    if ($producto && $producto['stock'] >= $cantidades[$i]) {
                        $subtotal = $producto['precio'] * $cantidades[$i];
                        $total += $subtotal;
                        $items_validos[] = [
                            'id_producto' => $productos[$i],
                            'cantidad' => $cantidades[$i],
                            'precio_unitario' => $producto['precio']
                        ];
                    }
                }
            }
            
            if (!empty($items_validos)) {
                // Insertar venta
                $stmt = $pdo->prepare("INSERT INTO ventas (id_usuario, id_cliente, total) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['id_usuario'], $id_cliente, $total]);
                $id_venta = $pdo->lastInsertId();
                
                // Insertar items y actualizar stock
                foreach ($items_validos as $item) {
                    $stmt = $pdo->prepare("INSERT INTO ventas_items (id_venta, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id_venta, $item['id_producto'], $item['cantidad'], $item['precio_unitario']]);
                    
                    // Actualizar stock
                    $stmt = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['cantidad'], $item['id_producto']]);
                }
                
                $pdo->commit();
                $mensaje = "Venta registrada correctamente. Total: $" . number_format($total, 2);
            } else {
                $pdo->rollBack();
                $mensaje = "Error: No hay productos válidos en la venta.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "Error al procesar la venta: " . $e->getMessage();
        }
    } else {
        $mensaje = "Error: Debe agregar al menos un producto.";
    }
}

// Obtener productos para el select
$stmt = $pdo->query("SELECT id, nombre, precio, stock FROM productos WHERE stock > 0 ORDER BY nombre");
$productos_disponibles = $stmt->fetchAll();

// Obtener clientes para el select
$stmt = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre");
$clientes = $stmt->fetchAll();

// Listar ventas
$stmt = $pdo->query("SELECT v.id, v.fecha, v.total, u.nombre AS vendedor, c.nombre AS cliente
    FROM ventas v
    LEFT JOIN usuarios u ON v.id_usuario = u.id
    LEFT JOIN clientes c ON v.id_cliente = c.id
    ORDER BY v.fecha DESC LIMIT 50");

$ventas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - MiniMarket</title>
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
                <li class="nav-item"><a class="nav-link" href="productos.php"><i class="fas fa-box me-2"></i>Productos</a></li>
                <li class="nav-item"><a class="nav-link active" href="ventas.php"><i class="fas fa-shopping-cart me-2"></i>Ventas</a></li>
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
                    <h2 class="mb-1"><i class="fas fa-shopping-cart me-2 text-success"></i>Gestión de Ventas</h2>
                    <p class="text-muted mb-0">Registra nuevas ventas y consulta el historial</p>
                </div>
                <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#newSaleModal">
                    <i class="fas fa-plus me-2"></i>Nueva Venta
                </button>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert <?= strpos($mensaje, 'Error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show" role="alert">
                <i class="fas <?= strpos($mensaje, 'Error') !== false ? 'fa-exclamation-triangle' : 'fa-check-circle' ?> me-2"></i><?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabla de ventas -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Historial de Ventas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th><th>Fecha</th><th>Vendedor</th><th>Cliente</th><th>Total</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td>#<?= $v['id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                                <td><?= htmlspecialchars($v['vendedor']) ?></td>
                                <td><?= htmlspecialchars($v['cliente'] ?? 'Sin cliente') ?></td>
                                <td><strong class="text-success">$<?= number_format($v['total'], 2) ?></strong></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info me-1" onclick="viewSale(<?= $v['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="printSale(<?= $v['id'] ?>)">
                                        <i class="fas fa-print"></i>
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

    <!-- Modal Nueva Venta -->
    <div class="modal fade" id="newSaleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nueva Venta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" id="saleForm">
                    <div class="modal-body">
                        <input type="hidden" name="nueva_venta" value="1">
                        
                        <!-- Cliente -->
                        <div class="mb-3">
                            <label class="form-label">Cliente (Opcional)</label>
                            <select class="form-select" name="id_cliente">
                                <option value="">Sin cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Productos -->
                        <div class="mb-3">
                            <label class="form-label">Productos</label>
                            <div id="productos-container">
                                <div class="product-row">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <select class="form-select" name="productos[]" onchange="updatePrice(this)">
                                                <option value="">Seleccionar producto</option>
                                                <?php foreach ($productos_disponibles as $prod): ?>
                                                <option value="<?= $prod['id'] ?>" data-precio="<?= $prod['precio'] ?>" data-stock="<?= $prod['stock'] ?>">
                                                    <?= htmlspecialchars($prod['nombre']) ?> - Stock: <?= $prod['stock'] ?> - $<?= number_format($prod['precio'], 2) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="number" class="form-control" name="cantidades[]" placeholder="Cantidad" min="1" onchange="calculateTotal()">
                                        </div>
                                        <div class="col-md-2">
                                            <span class="form-control-plaintext subtotal">$0.00</span>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeProduct(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addProduct()">
                                <i class="fas fa-plus me-1"></i>Agregar Producto
                            </button>
                        </div>

                        <!-- Total -->
                        <div class="total-section">
                            <h4>Total: $<span id="total-amount">0.00</span></h4>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-shopping-cart me-2"></i>Registrar Venta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function addProduct() {
            const container = document.getElementById('productos-container');
            const productRow = document.createElement('div');
            productRow.className = 'product-row';
            productRow.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <select class="form-select" name="productos[]" onchange="updatePrice(this)">
                            <option value="">Seleccionar producto</option>
                            <?php foreach ($productos_disponibles as $prod): ?>
                            <option value="<?= $prod['id'] ?>" data-precio="<?= $prod['precio'] ?>" data-stock="<?= $prod['stock'] ?>">
                                <?= htmlspecialchars($prod['nombre']) ?> - Stock: <?= $prod['stock'] ?> - $<?= number_format($prod['precio'], 2) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control" name="cantidades[]" placeholder="Cantidad" min="1" onchange="calculateTotal()">
                    </div>
                    <div class="col-md-2">
                        <span class="form-control-plaintext subtotal">$0.00</span>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeProduct(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(productRow);
        }

        function removeProduct(button) {
            button.closest('.product-row').remove();
            calculateTotal();
        }

        function updatePrice(select) {
            const row = select.closest('.product-row');
            const cantidadInput = row.querySelector('input[name="cantidades[]"]');
            const subtotalSpan = row.querySelector('.subtotal');
            
            if (select.value && cantidadInput.value) {
                const precio = parseFloat(select.selectedOptions[0].dataset.precio);
                const cantidad = parseInt(cantidadInput.value);
                const subtotal = precio * cantidad;
                subtotalSpan.textContent = '$' + subtotal.toFixed(2);
            } else {
                subtotalSpan.textContent = '$0.00';
            }
            
            calculateTotal();
        }

        function calculateTotal() {
            let total = 0;
            const rows = document.querySelectorAll('.product-row');
            
            rows.forEach(row => {
                const select = row.querySelector('select[name="productos[]"]');
                const cantidadInput = row.querySelector('input[name="cantidades[]"]');
                const subtotalSpan = row.querySelector('.subtotal');
                
                if (select.value && cantidadInput.value) {
                    const precio = parseFloat(select.selectedOptions[0].dataset.precio);
                    const cantidad = parseInt(cantidadInput.value);
                    const subtotal = precio * cantidad;
                    subtotalSpan.textContent = '$' + subtotal.toFixed(2);
                    total += subtotal;
                } else {
                    subtotalSpan.textContent = '$0.00';
                }
            });
            
            document.getElementById('total-amount').textContent = total.toFixed(2);
        }

        function viewSale(id) {
            alert(`Ver detalles de la venta #${id}`);
        }

        function printSale(id) {
            alert(`Imprimir venta #${id}`);
        }

        // Validar stock antes de enviar
        document.getElementById('saleForm').addEventListener('submit', function(e) {
            const rows = document.querySelectorAll('.product-row');
            let valid = true;
            
            rows.forEach(row => {
                const select = row.querySelector('select[name="productos[]"]');
                const cantidadInput = row.querySelector('input[name="cantidades[]"]');
                
                if (select.value && cantidadInput.value) {
                    const stock = parseInt(select.selectedOptions[0].dataset.stock);
                    const cantidad = parseInt(cantidadInput.value);
                    
                    if (cantidad > stock) {
                        alert(`Error: La cantidad solicitada (${cantidad}) excede el stock disponible (${stock}) para ${select.selectedOptions[0].text}`);
                        valid = false;
                    }
                }
            });
            
            if (!valid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
