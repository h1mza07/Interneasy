<?php
require_once __DIR__ . '/../core/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function register($data) {
    try {
        // Vérifier email
        $check = $this->db->prepare("SELECT id FROM students WHERE email = :email");
        $check->execute([':email' => $data['email']]);
        
        if ($check->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email déjà utilisé'];
        }
        
        // Hasher mot de passe
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // REQUÊTE SIMPLE
        $sql = "INSERT INTO students (first_name, last_name, email, password, school, education_level, field_of_study) 
                VALUES (:first_name, :last_name, :email, :password, :school, :education_level, :field_of_study)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':password' => $hashedPassword,
            ':school' => $data['school'],
            ':education_level' => $data['education_level'],
            ':field_of_study' => $data['field_of_study']
        ]);
        
        return [
            'success' => true, 
            'message' => 'Inscription réussie !',
            'user_id' => $this->db->lastInsertId()
        ];
        
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}
    public function login($email, $password) {
    try {
        
        // Récupérer l'utilisateur avec le mot de passe
        $sql = "SELECT id, first_name, last_name, email, password 
                FROM students WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
         
        if ($user && isset($user['password'])) {
            // DEBUG : Voir ce qu'on a
            error_log("Login attempt: " . $email . ", Hash: " . substr($user['password'], 0, 20) . "...");
            
            if (password_verify($password, $user['password'])) {
                // Retourner les données SANS le mot de passe
                unset($user['password']);
                return [
                    'success' => true,
                    'user' => $user,
                    'message' => 'Connexion réussie'
                ];
            } else {
                error_log("Password verification FAILED for: " . $email);
            }
        }
        
        return [
            'success' => false, 
            'message' => 'Email ou mot de passe incorrect'
        ];
        
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Erreur technique: ' . $e->getMessage()
        ];
    }

}

    
    public function emailExists($email) {
        try {
            $sql = "SELECT COUNT(*) as count FROM students WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch(PDOException $e) {
            error_log("Erreur vérification email: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        try {
            $sql = "SELECT id, first_name, last_name, email, school, education_level, field_of_study, created_at 
                    FROM students 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Erreur récupération étudiant: " . $e->getMessage());
            return false;
        }
    }
}
?>