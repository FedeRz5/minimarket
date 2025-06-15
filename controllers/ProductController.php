<?php

require_once 'models/Product.php';
require_once 'controllers/AuthController.php';

class ProductController {
    private $authController;
    
    public function __construct() {
        $this->authController = new AuthController();
    }
    
    public function index() {
        $this->authController->requireAuth();
        
        if (!$this->authController->hasPermission('productos')) {
            header('Location: dashboard.php');
            exit;
        }
        
        $product = new Product();
        $products = $product->findAll();
        
        include 'views/products/index.php';
    }
    
    public function create($data) {
        $this->authController->requireAuth();
        
        // Validación de entrada
        $errors = $this->validateProductData($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $product = new Product();
        $product->setName($this->sanitizeInput($data['name']));
        $product->setCategory($this->sanitizeInput($data['category']));
        $product->setPrice($this->sanitizeNumeric($data['price']));
        $product->setStock($this->sanitizeNumeric($data['stock']));
        
        if ($product->save()) {
            return ['success' => true, 'message' => 'Producto creado exitosamente'];
        } else {
            return ['success' => false, 'message' => 'Error al crear producto'];
        }
    }
    
    private function validateProductData($data) {
        $errors = [];
        
        if (empty($data['name']) || !is_string($data['name'])) {
            $errors[] = 'El nombre es requerido y debe ser texto';
        }
        
        if (!is_numeric($data['price']) || $data['price'] <= 0) {
            $errors[] = 'El precio debe ser un número mayor a 0';
        }
        
        if (!is_numeric($data['stock']) || $data['stock'] < 0) {
            $errors[] = 'El stock debe ser un número mayor o igual a 0';
        }
        
        return $errors;
    }
    
    private function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    private function sanitizeNumeric($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
