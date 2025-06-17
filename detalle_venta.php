<?php
session_start();
header('Content-Type: application/json');

// Verificamos que venga el ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID de venta inválido']);
    exit;
}

$idVenta = intval($_GET['id']);

// Conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = ''; // tu contraseña
$dbname = 'minimarket'; // nombre de tu base

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Error al conectar a la base de datos']);
    exit;
}

// Traer datos generales de la venta
$sqlVenta = "SELECT id, fecha, total FROM ventas WHERE id = ?";
$stmtVenta = $conn->prepare($sqlVenta);
$stmtVenta->bind_param('i', $idVenta);
$stmtVenta->execute();
$resultVenta = $stmtVenta->get_result();
$venta = $resultVenta->fetch_assoc();

if (!$venta) {
    echo json_encode(['error' => 'Venta no encontrada']);
    exit;
}

// Traer detalles (productos vendidos)
$sqlItems = "
    SELECT p.nombre, vi.cantidad, vi.precio_unitario, (vi.cantidad * vi.precio_unitario) AS total_item
    FROM ventas_items vi
    JOIN productos p ON vi.id_producto = p.id
    WHERE vi.id_venta = ?
";
$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param('i', $idVenta);
$stmtItems->execute();
$resultItems = $stmtItems->get_result();

$items = [];
while ($row = $resultItems->fetch_assoc()) {
    $items[] = $row;
}

// Armar respuesta
$data = [
    'id' => $venta['id'],
    'fecha' => $venta['fecha'],
    'total' => $venta['total'],
    'items' => $items
];

echo json_encode($data);
exit;
