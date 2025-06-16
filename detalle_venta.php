<?php
// detalle_venta.php
require_once 'models/Sale.php';
session_start();
header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID de venta inválido']);
    exit;
}

$idVenta = intval($_GET['id']);

$sale = new Sale();
$sale->findById($idVenta);

$data = array(
    'id' => $sale->getId(),
    'fecha' => $sale->getDate(),
    'total' => $sale->getTotal(),
);

$ventasItems = $sale->getItems();

foreach ($ventasItems as $item) {
    $producto = new Product();
    $producto->findById($item->getProductId());

    $data['items'][] = array(
        'id' => $producto->getId(),
        'nombre' => $producto->getName(),
        'precio_unitario' => $item->getUnitPrice(),
        'total_item' => $item->getSubtotal(),
        'categoria' => $producto->getCategory(),
        'cantidad' => $item->getQuantity()
    );
}

echo json_encode($data);
exit;