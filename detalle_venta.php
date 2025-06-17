<?php
// detalle_venta.php - Versión mejorada usando modelos OOP
session_start();
require_once 'models/Sale.php';
require_once 'models/Product.php';

// Establecer header JSON
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar parámetro ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de venta inválido']);
    exit;
}

try {
    $idVenta = intval($_GET['id']);

    // Crear instancia de Sale y buscar por ID
    $sale = new Sale();
    $found = $sale->findById($idVenta);
    
    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Venta no encontrada']);
        exit;
    }

    // Preparar datos de respuesta
    $data = array(
        'success' => true,
        'id' => $sale->getId(),
        'fecha' => $sale->getDate(),
        'total' => $sale->getTotal(),
        'items' => array()
    );

    // Obtener items de la venta
    $ventasItems = $sale->getItems();

    if (empty($ventasItems)) {
        echo json_encode(['error' => 'No se encontraron items para esta venta']);
        exit;
    }

    // Procesar cada item
    foreach ($ventasItems as $item) {
        $producto = new Product();
        $productoFound = $producto->findById($item->getProductId());
        
        if ($productoFound) {
            $data['items'][] = array(
                'id_producto' => $producto->getId(),
                'nombre' => $producto->getName(),
                'categoria' => $producto->getCategory(),
                'cantidad' => $item->getQuantity(),
                'precio_unitario' => number_format($item->getUnitPrice(), 2),
                'total_item' => number_format($item->getSubtotal(), 2)
            );
        } else {
            // Si el producto no existe, mostrar info básica
            $data['items'][] = array(
                'id_producto' => $item->getProductId(),
                'nombre' => 'Producto no encontrado',
                'categoria' => 'N/A',
                'cantidad' => $item->getQuantity(),
                'precio_unitario' => number_format($item->getUnitPrice(), 2),
                'total_item' => number_format($item->getSubtotal(), 2)
            );
        }
    }

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
    error_log("Error en detalle_venta.php: " . $e->getMessage());
}
?>
