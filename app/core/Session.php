<?php
// app/core/Session.php

class Session {
    
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key) {
        return $_SESSION[$key] ?? null;
    }
    
    public static function delete($key) {
        unset($_SESSION[$key]);
    }
    
    public static function destroy() {
        session_destroy();
        $_SESSION = [];
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['id']) && isset($_SESSION['role']);
    }
    
    public static function getUserRole() {
        return $_SESSION['role'] ?? null;
    }
    
    public static function getUserId() {
        return $_SESSION['id'] ?? null;
    }
    
    public static function getUserEmail() {
        return $_SESSION['email'] ?? null;
    }
    
    public static function getUserName() {
        return $_SESSION['nom'] ?? null;
    }
    
    public static function isAdmin() {
        return self::getUserRole() === 'admin';
    }
    
    public static function isEntreprise() {
        return self::getUserRole() === 'entreprise';
    }
    
    public static function isEtudiant() {
        return self::getUserRole() === 'etudiant';
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: index.php?controller=Auth&action=login');
            exit();
        }
    }
    
    public static function requireRole($role) {
        self::requireLogin();
        if (self::getUserRole() !== $role) {
            header('Location: index.php?controller=Auth&action=login');
            exit();
        }
    }
    
    public static function redirectIfLoggedIn() {
        if (self::isLoggedIn()) {
            $role = self::getUserRole();
            switch($role) {
                case 'admin':
                    header('Location: dashboard_admin.php');
                    break;
                case 'entreprise':
                    header('Location: dashboard_entreprise.php');
                    break;
                case 'etudiant':
                    header('Location: dashboard_etudiant.php');
                    break;
            }
            exit();
        }
    }
}
?>