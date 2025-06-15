<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'jefe') {
    header('Location: login.php');
    exit;
}

// Obtener conexión usando el patrón Singleton
$db = Database::getInstance();
$pdo = $db->getConnection();

// Eliminar empleado
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND id != ?");
    if ($stmt->execute([$_GET['delete'], $_SESSION['id_usuario']])) {
        header('Location: empleados.php?msg=deleted');
        exit;
    }
}

// Editar empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_empleado'])) {
    $id = intval($_POST['id']);
    $usuario = trim($_POST['usuario']);
    $nombre = trim($_POST['nombre']);
    $rol = $_POST['rol'] === 'jefe' ? 'jefe' : 'empleado';
    $salario = floatval($_POST['salario']);

    if ($usuario && $nombre && $salario >= 0) {
        // Verificar si el usuario ya existe (excluyendo el actual)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
        $stmt->execute([$usuario, $id]);
        
        if ($stmt->fetch()) {
            $mensaje = "Error: El nombre de usuario ya existe.";
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET usuario = ?, nombre = ?, rol = ?, salario = ? WHERE id = ?");
            if ($stmt->execute([$usuario, $nombre, $rol, $salario, $id])) {
                $mensaje = "Empleado actualizado correctamente.";
            } else {
                $mensaje = "Error al actualizar empleado.";
            }
        }
    } else {
        $mensaje = "Complete todos los campos correctamente.";
    }
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_empleado'])) {
    $usuario = trim($_POST['usuario']);
    $nombre = trim($_POST['nombre']);
    $password = $_POST['password'];
    $rol = $_POST['rol'] === 'jefe' ? 'jefe' : 'empleado';
    $salario = floatval($_POST['salario']);

    if ($usuario && $nombre && $password && $salario >= 0) {
        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        
        if ($stmt->fetch()) {
            $mensaje = "Error: El nombre de usuario ya existe. Por favor, elija otro.";
        } else {
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, nombre, rol, salario) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$usuario, $passHash, $nombre, $rol, $salario])) {
                $mensaje = "Empleado agregado correctamente.";
            } else {
                $mensaje = "Error al agregar empleado.";
            }
        }
    } else {
        $mensaje = "Complete todos los campos correctamente.";
    }
}

// Mensaje de eliminación
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $mensaje = "Empleado eliminado correctamente.";
}

// Listar empleados
$stmt = $pdo->query("SELECT id, usuario, nombre, rol, salario FROM usuarios ORDER BY usuario");
$empleados = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados - MiniMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: #f8f9fa; 
            padding-top: 56px; /* Espacio para navbar fijo */
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
        }
        .nav-link.active { 
            background: #007bff; 
            color: white; 
        }
        .page-header {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
                <li class="nav-item"><a class="nav-link" href="informes.php"><i class="fas fa-chart-bar me-2"></i>Informes</a></li>
                <li class="nav-item"><a class="nav-link active" href="empleados.php"><i class="fas fa-users me-2"></i>Empleados</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="fas fa-users me-2 text-info"></i>Gestión de Empleados</h2>
                    <p class="text-muted mb-0">Administra el personal de tu minimarket</p>
                </div>
                <button class="btn btn-info btn-lg" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fas fa-user-plus me-2"></i>Agregar Empleado
                </button>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert <?= strpos($mensaje, 'Error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show" role="alert">
                <i class="fas <?= strpos($mensaje, 'Error') !== false ? 'fa-exclamation-triangle' : 'fa-check-circle' ?> me-2"></i><?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabla de empleados -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Salario</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empleados as $e): ?>
                            <tr>
                                <td><?= $e['id'] ?></td>
                                <td><?= htmlspecialchars($e['usuario']) ?></td>
                                <td><?= htmlspecialchars($e['nombre']) ?></td>
                                <td>
                                    <span class="badge <?= $e['rol'] === 'jefe' ? 'bg-warning' : 'bg-primary' ?>">
                                        <?= ucfirst($e['rol']) ?>
                                    </span>
                                </td>
                                <td>$<?= number_format($e['salario'], 2) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editEmployee(<?= $e['id'] ?>, '<?= htmlspecialchars($e['usuario']) ?>', '<?= htmlspecialchars($e['nombre']) ?>', '<?= $e['rol'] ?>', <?= $e['salario'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($e['id'] != $_SESSION['id_usuario']): ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteEmployee(<?= $e['id'] ?>, '<?= htmlspecialchars($e['nombre']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Empleado -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Agregar Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="agregar_empleado" value="1">
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" class="form-control" name="usuario" required>
                            <div class="form-text">El nombre de usuario debe ser único</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select class="form-select" name="rol">
                                <option value="empleado">Empleado</option>
                                <option value="jefe">Jefe</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Salario</label>
                            <input type="number" step="0.01" class="form-control" name="salario" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info">Agregar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Empleado -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="editar_empleado" value="1">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" class="form-control" name="usuario" id="edit_usuario" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select class="form-select" name="rol" id="edit_rol">
                                <option value="empleado">Empleado</option>
                                <option value="jefe">Jefe</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Salario</label>
                            <input type="number" step="0.01" class="form-control" name="salario" id="edit_salario" required>
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
    <script>
        function editEmployee(id, usuario, nombre, rol, salario) {
            // Llenar el modal con los datos del empleado
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_usuario').value = usuario;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_rol').value = rol;
            document.getElementById('edit_salario').value = salario;
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
            modal.show();
        }

        function deleteEmployee(id, nombre) {
            if (confirm(`¿Estás seguro de que deseas eliminar al empleado "${nombre}"?`)) {
                window.location.href = `empleados.php?delete=${id}`;
            }
        }

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
