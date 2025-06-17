<?php

require_once 'config/database.php';
require_once 'models/Product.php';

// Composición: Sale contiene SaleItems
class SaleItem {
    private $id;
    private $saleId;
    private $productId;
    private $quantity;
    private $unitPrice;
    private $product; // Composición con Product
    
    public function __construct($productId = null, $quantity = 0, $unitPrice = 0) {
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        
        if ($productId) {
            $this->product = new Product();
            $this->product->findById($productId);
        }
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getSaleId() { return $this->saleId; }
    public function getProductId() { return $this->productId; }
    public function getQuantity() { return $this->quantity; }
    public function getUnitPrice() { return $this->unitPrice; }
    public function getProduct() { return $this->product; }
    
    // Setters
    public function setSaleId($saleId) { $this->saleId = $saleId; }
    
    public function getSubtotal() {
        return $this->quantity * $this->unitPrice;
    }
}

class Sale {
    private $id;
    private $userId;
    private $clientId;
    private $date;
    private $total;
    private $items; // Array de SaleItems - Composición
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->items = [];
        $this->date = date('Y-m-d H:i:s');
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getClientId() { return $this->clientId; }
    public function getDate() { return $this->date; }
    public function getTotal() { return $this->total; }
    public function getItems() { return $this->items; }
    
    // Setters
    public function setUserId($userId) { $this->userId = $userId; }
    public function setClientId($clientId) { $this->clientId = $clientId; }
    
    public function addItem(SaleItem $item) {
        $this->items[] = $item;
        $this->calculateTotal();
    }

    public function findById($id) {
        try {
            // Buscar la venta principal
            $sql = "SELECT id, id_usuario, id_cliente, fecha, total FROM ventas WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch();

            if ($result) {
                $this->id = $result['id'];
                $this->userId = $result['id_usuario'];
                $this->clientId = $result['id_cliente'];
                $this->date = $result['fecha'];
                $this->total = $result['total'];
                
                // Cargar los items
                $this->items = $this->fetchItems();
                return true;
            }

            return false;
        } catch (Exception $e) {
            error_log("Error en Sale::findById: " . $e->getMessage());
            return false;
        }
    }

    public function fetchItems() {
        try {
            $sql = "SELECT id, id_venta, id_producto, cantidad, precio_unitario
                    FROM ventas_items 
                    WHERE id_venta = ?
                    ORDER BY id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->id]);
            $items = $stmt->fetchAll();

            $saleItems = array();
            foreach ($items as $item) {
                $saleItem = new SaleItem($item['id_producto'], $item['cantidad'], $item['precio_unitario']);
                $saleItem->setSaleId($item['id_venta']);
                $saleItems[] = $saleItem;
            }

            return $saleItems;
        } catch (Exception $e) {
            error_log("Error en Sale::fetchItems: " . $e->getMessage());
            return array();
        }
    }
    
    public function removeItem($index) {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items); // Reindexar
            $this->calculateTotal();
        }
    }
    
    private function calculateTotal() {
        $this->total = 0;
        foreach ($this->items as $item) {
            $this->total += $item->getSubtotal();
        }
    }
    
    public function save() {
        try {
            $this->db->beginTransaction();
            
            // Establecer variable de usuario para triggers
            if (isset($_SESSION['id_usuario'])) {
                $this->db->exec("SET @user_id = " . $_SESSION['id_usuario']);
            }
            
            // Guardar la venta
            $stmt = $this->db->prepare("INSERT INTO ventas (id_usuario, id_cliente, fecha, total) VALUES (?, ?, ?, ?)");
            $stmt->execute([$this->userId, $this->clientId, $this->date, $this->total]);
            $this->id = $this->db->lastInsertId();
            
            // Guardar los items
            foreach ($this->items as $item) {
                $item->setSaleId($this->id);
                $stmt = $this->db->prepare("INSERT INTO ventas_items (id_venta, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                $stmt->execute([$this->id, $item->getProductId(), $item->getQuantity(), $item->getUnitPrice()]);
                
                // Actualizar stock del producto
                $product = $item->getProduct();
                if ($product) {
                    $product->updateStock(-$item->getQuantity());
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en Sale::save: " . $e->getMessage());
            return false;
        }
    }
    
    public function findAll() {
        $stmt = $this->db->query("
            SELECT v.*, u.nombre as vendedor, c.nombre as cliente 
            FROM ventas v 
            LEFT JOIN usuarios u ON v.id_usuario = u.id 
            LEFT JOIN clientes c ON v.id_cliente = c.id 
            ORDER BY v.fecha DESC
        ");
        return $stmt->fetchAll();
    }
}
?>
