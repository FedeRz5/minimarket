<?php

// Patrón Strategy para diferentes tipos de reportes
interface ReportStrategy {
    public function generateReport();
}

class SalesReportStrategy implements ReportStrategy {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generateReport() {
        $stmt = $this->db->query("
            SELECT DATE(fecha) as fecha, COUNT(*) as ventas, SUM(total) as ingresos
            FROM ventas 
            GROUP BY DATE(fecha) 
            ORDER BY fecha DESC 
            LIMIT 30
        ");
        return $stmt->fetchAll();
    }
}

class ProductReportStrategy implements ReportStrategy {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generateReport() {
        $stmt = $this->db->query("
            SELECT p.nombre, SUM(vi.cantidad) as total_vendido, SUM(vi.cantidad * vi.precio_unitario) as ingresos
            FROM ventas_items vi
            JOIN productos p ON vi.id_producto = p.id
            GROUP BY p.id
            ORDER BY total_vendido DESC
        ");
        return $stmt->fetchAll();
    }
}

class EmployeeReportStrategy implements ReportStrategy {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generateReport() {
        $stmt = $this->db->query("
            SELECT u.nombre, COUNT(v.id) as ventas_realizadas, SUM(v.total) as ingresos_generados
            FROM ventas v
            JOIN usuarios u ON v.id_usuario = u.id
            GROUP BY u.id
            ORDER BY ventas_realizadas DESC
        ");
        return $stmt->fetchAll();
    }
}

class ReportContext {
    private $strategy;
    
    public function setStrategy(ReportStrategy $strategy) {
        $this->strategy = $strategy;
    }
    
    public function generateReport() {
        return $this->strategy->generateReport();
    }
}
