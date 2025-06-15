<?php

require_once 'config/database.php';

class Product {
    private $id;
    private $name;
    private $category;
    private $price;
    private $stock;
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getCategory() { return $this->category; }
    public function getPrice() { return $this->price; }
    public function getStock() { return $this->stock; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setName($name) { $this->name = $name; }
    public function setCategory($category) { $this->category = $category; }
    public function setPrice($price) { $this->price = $price; }
    public function setStock($stock) { $this->stock = $stock; }
    
    public function save() {
        if ($this->id) {
            return $this->update();
        } else {
            return $this->create();
        }
    }
    
    private function create() {
        $stmt = $this->db->prepare("INSERT INTO productos (nombre, categoria, precio, stock) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$this->name, $this->category, $this->price, $this->stock]);
        if ($result) {
            $this->id = $this->db->lastInsertId();
        }
        return $result;
    }
    
    private function update() {
        $stmt = $this->db->prepare("UPDATE productos SET nombre = ?, categoria = ?, precio = ?, stock = ? WHERE id = ?");
        return $stmt->execute([$this->name, $this->category, $this->price, $this->stock, $this->id]);
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        if ($data) {
            $this->id = $data['id'];
            $this->name = $data['nombre'];
            $this->category = $data['categoria'];
            $this->price = $data['precio'];
            $this->stock = $data['stock'];
            return true;
        }
        return false;
    }
    
    public function findAll() {
        $stmt = $this->db->query("SELECT * FROM productos ORDER BY nombre");
        return $stmt->fetchAll();
    }
    
    public function updateStock($quantity) {
        $this->stock += $quantity;
        return $this->update();
    }
    
    public function hasStock($quantity) {
        return $this->stock >= $quantity;
    }
}
