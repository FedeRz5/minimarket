<?php

require_once 'models/User.php';
require_once 'config/constants.php';

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($username, $password) {
        // Validación de entrada
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Complete todos los campos'];
        }
        
        // Verificar intentos de login
        if ($this->hasExceededLoginAttempts($username)) {
            return ['success' => false, 'message' => 'Demasiados intentos fallidos. Intente más tarde.'];
        }
        
        // Crear usuario según el rol
        $user = $this->createUserByRole($username);
        
        if ($user && $user->authenticate($username, $password)) {
            $this->createSession($user);
            $this->clearLoginAttempts($username);
            return ['success' => true, 'redirect' => 'dashboard'];
        } else {
            $this->recordLoginAttempt($username);
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        }
    }
    
    private function createUserByRole($username) {
        // Obtener rol del usuario
        $stmt = $this->db->prepare("SELECT rol FROM usuarios WHERE usuario = ?");
        $stmt->execute([$username]);
        $userData = $stmt->fetch();
        
        if (!$userData) return null;
        
        // Factory pattern para crear usuario según rol
        switch ($userData['rol']) {
            case ROLE_MANAGER:
                return new Manager();
            case ROLE_EMPLOYEE:
                return new Employee();
            default:
                return null;
        }
    }
    
    private function createSession($user) {
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['name'] = $user->getName();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['login_time'] = time();
    }
    
    public function logout() {
        session_destroy();
        header('Location: index.php');
        exit;
    }
    
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && $this->isSessionValid();
    }
    
    private function isSessionValid() {
        if (!isset($_SESSION['login_time'])) return false;
        return (time() - $_SESSION['login_time']) < SESSION_TIMEOUT;
    }
    
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            header('Location: index.php');
            exit;
        }
    }
    
    public function hasPermission($module) {
        if (!$this->isAuthenticated()) return false;
        
        $user = $this->createUserByRole($_SESSION['username']);
        return $user && $user->canAccessModule($module);
    }
    
    private function hasExceededLoginAttempts($username) {
        // Implementación simple - en producción usar base de datos
        $attempts = $_SESSION['login_attempts'][$username] ?? 0;
        return $attempts >= MAX_LOGIN_ATTEMPTS;
    }
    
    private function recordLoginAttempt($username) {
        $_SESSION['login_attempts'][$username] = ($_SESSION['login_attempts'][$username] ?? 0) + 1;
    }
    
    private function clearLoginAttempts($username) {
        unset($_SESSION['login_attempts'][$username]);
    }
}
