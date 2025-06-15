<?php

class Database {
    private static $instance = null;
    private $connection;
    
    private $host = 'localhost';
    private $database = 'minimarket';
    private $username = 'root';        // ← Usuario correcto
    private $password = '';            // ← Contraseña vacía (sin comillas dentro)
    
    // Patrón Singleton
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage() . "<br><br>
                <strong>💡 Información de conexión:</strong><br>
                Host: {$this->host}<br>
                Database: {$this->database}<br>
                Username: {$this->username}<br>
                Password: " . (empty($this->password) ? '(vacía)' : '(configurada)') . "<br><br>
                <strong>🔧 Verifica que:</strong><br>
                1. Hayas importado los scripts SQL en PHPMyAdmin<br>
                2. La base de datos 'minimarket' exista<br>
                3. MySQL esté ejecutándose");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
