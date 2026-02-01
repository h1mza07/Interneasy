<?php
// register_ultimate.php - VERSION FONCTIONNELLE AVEC CARTE
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Chemin absolu
define('BASE_PATH', dirname(__DIR__, 2));

// Inclure seulement Database (pas les modèles qui n'existent pas)
require_once BASE_PATH . '/backend/app/core/Database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userType = $_POST['userType'] ?? '';
    
    if ($userType === 'student') {
        // Inscription étudiant
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $school = trim($_POST['school'] ?? '');
        $educationLevel = trim($_POST['educationLevel'] ?? '');
        $fieldOfStudy = trim($_POST['fieldOfStudy'] ?? '');
        
        // Validation
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            $message = "❌ Tous les champs obligatoires doivent être remplis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "❌ Format d'email invalide.";
        } elseif (strlen($password) < 6) {
            $message = "❌ Le mot de passe doit contenir au moins 6 caractères.";
        } else {
            $database = Database::getInstance();
            $db = $database->getConnection();
            
            // Vérifier si email existe déjà
            $checkQuery = "SELECT id FROM students WHERE email = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch()) {
                $message = "❌ Cet email est déjà utilisé par un étudiant.";
            } else {
                // Hasher le mot de passe
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insérer étudiant
                $sql = "INSERT INTO students (first_name, last_name, email, school, education_level, field_of_study, password, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $db->prepare($sql);
                
                if ($stmt->execute([$firstName, $lastName, $email, $school, $educationLevel, $fieldOfStudy, $hashedPassword])) {
                    echo "<script>
                        alert('✅ Inscription étudiante réussie !');
                        window.location.href = '../../frontend/login.html';
                    </script>";
                    exit();
                } else {
                    $message = "❌ Erreur lors de l'inscription étudiante.";
                }
            }
        }
        
    } elseif ($userType === 'company') {
        // Inscription entreprise - CORRIGÉ AVEC LATITUDE/LONGITUDE
        $companyName = trim($_POST['companyName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $activitySector = trim($_POST['activitySector'] ?? '');
        $companySize = trim($_POST['companySize'] ?? '');
        $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
        $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
        
        // Validation
        if (empty($companyName) || empty($email) || empty($password)) {
            $message = "❌ Tous les champs obligatoires doivent être remplis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "❌ Format d'email invalide.";
        } elseif (strlen($password) < 6) {
            $message = "❌ Le mot de passe doit contenir au moins 6 caractères.";
        } else {
            $database = Database::getInstance();
            $db = $database->getConnection();
            
            // Vérifier si email existe déjà
            $checkQuery = "SELECT id FROM companies WHERE email = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch()) {
                $message = "❌ Cet email est déjà utilisé par une entreprise.";
            } else {
                // Hasher le mot de passe
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // DEBUG : Afficher les valeurs
                error_log("DEBUG - Latitude: $latitude, Longitude: $longitude");
                
                // Insérer entreprise AVEC LATITUDE/LONGITUDE
                $sql = "INSERT INTO companies (name, email, activity_sector, company_size, password, newsletter, latitude, longitude, created_at) 
                        VALUES (?, ?, ?, ?, ?, 0, ?, ?, NOW())";
                $stmt = $db->prepare($sql);
                
                if ($stmt->execute([$companyName, $email, $activitySector, $companySize, $hashedPassword, $latitude, $longitude])) {
                    echo "<script>
                        alert('✅ Inscription entreprise réussie !');
                        window.location.href = '../../frontend/login.html';
                    </script>";
                    exit();
                } else {
                    $errorInfo = $stmt->errorInfo();
                    $message = "❌ Erreur lors de l'inscription entreprise: " . $errorInfo[2];
                    error_log("Erreur SQL: " . print_r($errorInfo, true));
                }
            }
        }
        
    } elseif ($userType === 'admin') {
        // AJOUT: Inscription administrateur
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $adminRole = trim($_POST['adminRole'] ?? 'super_admin');
        $hasFullAccess = isset($_POST['hasFullAccess']) ? 1 : 0;
        
        // Validation
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            $message = "❌ Tous les champs obligatoires doivent être remplis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "❌ Format d'email invalide.";
        } elseif (strlen($password) < 6) {
            $message = "❌ Le mot de passe doit contenir au moins 6 caractères.";
        } else {
            $database = Database::getInstance();
            $db = $database->getConnection();
            
            // Vérifier si email existe déjà dans admins
            $checkQuery = "SELECT id FROM admins WHERE email = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch()) {
                $message = "❌ Cet email est déjà utilisé par un administrateur.";
            } else {
                // Hasher le mot de passe
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insérer administrateur
                $sql = "INSERT INTO admins (first_name, last_name, email, password, role, has_full_access, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $db->prepare($sql);
                
                if ($stmt->execute([$firstName, $lastName, $email, $hashedPassword, $adminRole, $hasFullAccess])) {
                    echo "<script>
                        alert('✅ Inscription administrateur réussie !');
                        window.location.href = '../../frontend/login.html';
                    </script>";
                    exit();
                } else {
                    $errorInfo = $stmt->errorInfo();
                    $message = "❌ Erreur lors de l'inscription administrateur: " . $errorInfo[2];
                    error_log("Erreur SQL admin: " . print_r($errorInfo, true));
                }
            }
        }
    }
}

// Lire HTML
$html = file_get_contents(BASE_PATH . '/frontend/register.html');

// Ajouter les champs latitude/longitude au formulaire entreprise
$html = preg_replace_callback(
    '/(<div class="mb-3">.*?<select[^>]*name="companySize"[^>]*>.*?<\/select>.*?<\/div>)(\s*)(<div class="form-check mb-3">)/s',
    function($matches) {
        return $matches[1] . "\n" . 
            '<!-- NOUVEAU : Champs pour la carte -->' . "\n" .
            '<div class="row">' . "\n" .
            '    <div class="col-md-6 mb-3">' . "\n" .
            '        <label class="form-label">Latitude (optionnel)</label>' . "\n" .
            '        <input type="number" name="latitude" class="form-control" ' . "\n" .
            '               step="0.00000001" min="-90" max="90"' . "\n" .
            '               placeholder="Ex: 33.573110">' . "\n" .
            '        <small class="text-muted">Pour apparaître sur la carte interactive</small>' . "\n" .
            '    </div>' . "\n" .
            '    <div class="col-md-6 mb-3">' . "\n" .
            '        <label class="form-label">Longitude (optionnel)</label>' . "\n" .
            '        <input type="number" name="longitude" class="form-control" ' . "\n" .
            '               step="0.00000001" min="-180" max="180"' . "\n" .
            '               placeholder="Ex: -7.589843">' . "\n" .
            '        <small class="text-muted">Pour apparaître sur la carte interactive</small>' . "\n" .
            '    </div>' . "\n" .
            '</div>' . "\n" .
            $matches[2] . $matches[3];
    },
    $html
);

// Corriger les chemins CSS/JS
$html = str_replace('href="css/', 'href="../frontend/css/', $html);
$html = str_replace('src="js/', 'src="../frontend/js/', $html);

// Action du formulaire
$html = str_replace(
    'action="../backend/pages/register.php"',
    'action="register_ultimate.php"',
    $html
);

// Message d'erreur
if ($message) {
    $html = str_replace(
        '<div class="register-container">',
        '<div class="alert alert-danger m-3">' . $message . '</div><div class="register-container">',
        $html
    );
}

echo $html;
?>