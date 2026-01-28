<?php
// app/core/Auth.php

require_once __DIR__ . '/Session.php';

class Auth {
    
    public static function login($userData, $role) {
        Session::start();
        Session::set('id', $userData['id']);
        Session::set('role', $role);
        Session::set('email', $userData['email']);
        
        if ($role === 'etudiant') {
            Session::set('nom', $userData['prenom'] . ' ' . $userData['nom']);
        } elseif ($role === 'entreprise') {
            Session::set('nom', $userData['nom']);
        } elseif ($role === 'admin') {
            Session::set('nom', 'Administrateur');
        }
    }
    
    public static function logout() {
        Session::destroy();
    }
    
    public static function checkPassword($password, $hashedPassword) {
        return password_verify($password, $hashedPassword);
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
?>