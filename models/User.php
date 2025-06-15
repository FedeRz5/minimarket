<?php

require_once 'config/database.php';

// Clase base Usuario
abstract class User {
    protected $id;
    protected $username;
    protected $name;
    protected $role;
    protected $salary;
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getName() { return $this->name; }
    public function getRole() { return $this->role; }
    public function getSalary() { return $this->salary; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setUsername($username) { $this->username = $username; }
    public function setName($name) { $this->name = $name; }
    public function setRole($role) { $this->role = $role; }
    public function setSalary($salary) { $this->salary = $salary; }
    
    // Métodos abstractos que deben implementar las clases hijas
    abstract public function getPermissions();
    abstract public function canAccessModule($module);
    
    // Método común para todos los usuarios
    public function authenticate($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $this->id = $user['id'];
            $this->username = $user['usuario'];
            $this->name = $user['nombre'];
            $this->role = $user['rol'];
            $this->salary = $user['salario'];
            return true;
        }
        return false;
    }
    
    public function save() {
        if ($this->id) {
            return $this->update();
        } else {
            return $this->create();
        }
    }
    
    protected function create() {
        $stmt = $this->db->prepare("INSERT INTO usuarios (usuario, password, nombre, rol, salario) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$this->username, password_hash($this->password, PASSWORD_DEFAULT), $this->name, $this->role, $this->salary]);
    }
    
    protected function update() {
        $stmt = $this->db->prepare("UPDATE usuarios SET usuario = ?, nombre = ?, rol = ?, salario = ? WHERE id = ?");
        return $stmt->execute([$this->username, $this->name, $this->role, $this->salary, $this->id]);
    }
}

// Herencia: Empleado hereda de User
class Employee extends User {
    public function __construct() {
        parent::__construct();
        $this->role = ROLE_EMPLOYEE;
    }
    
    public function getPermissions() {
        return ['dashboard', 'productos', 'ventas', 'cajas'];
    }
    
    public function canAccessModule($module) {
        return in_array($module, $this->getPermissions());
    }
}

// Herencia: Manager hereda de User
class Manager extends User {
    public function __construct() {
        parent::__construct();
        $this->role = ROLE_MANAGER;
    }
    
    public function getPermissions() {
        return ['dashboard', 'productos', 'ventas', 'cajas', 'informes', 'empleados'];
    }
    
    public function canAccessModule($module) {
        return in_array($module, $this->getPermissions());
    }
    
    public function manageEmployee($employeeData) {
        // Funcionalidad específica del manager
        $employee = new Employee();
        $employee->setUsername($employeeData['username']);
        $employee->setName($employeeData['name']);
        $employee->setSalary($employeeData['salary']);
        return $employee->save();
    }
}
