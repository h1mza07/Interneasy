<?php
require_once __DIR__ . '/../core/Database.php';

class Company {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function register($data) {
    try {
        // Vérifier email
        $check = $this->db->prepare("SELECT id FROM companies WHERE email = :email");
        $check->execute([':email' => $data['email']]);
        
        if ($check->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email déjà utilisé'];
        }
        
        // Hasher mot de passe
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // REQUÊTE SIMPLE
        $sql = "INSERT INTO companies (name, email, password, activity_sector, company_size) 
                VALUES (:name, :email, :password, :activity_sector, :company_size)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password' => $hashedPassword,
            ':activity_sector' => $data['activity_sector'],
            ':company_size' => $data['company_size']
        ]);
        
        return [
            'success' => true, 
            'message' => 'Inscription entreprise réussie !',
            'company_id' => $this->db->lastInsertId()
        ];
        
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}
    
    public function login($email, $password) {
        try {
            // Récupérer l'entreprise avec le mot de passe
            $sql = "SELECT id, name, email, password 
                    FROM companies WHERE email = :email LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($company && isset($company['password'])) {
            if (password_verify($password, $company['password'])) {
                unset($company['password']);
                return [
                    'success' => true,
                    'company' => $company,
                    'message' => 'Connexion réussie'
                ];
            }
        }
        
        return [
            'success' => false, 
            'message' => 'Email ou mot de passe incorrect'
        ];
        
    } catch(PDOException $e) {
        error_log("Company login error: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Erreur technique: ' . $e->getMessage()
        ];
    }
}
    
    public function emailExists($email) {
        try {
            $sql = "SELECT COUNT(*) as count FROM companies WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch(PDOException $e) {
            error_log("Erreur vérification email entreprise: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        try {
            $sql = "SELECT id, name, email, activity_sector, company_size, created_at 
                    FROM companies 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Erreur récupération entreprise: " . $e->getMessage());
            return false;
        }
    }
}
?>