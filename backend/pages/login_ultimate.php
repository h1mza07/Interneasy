<?php
// login_ultimate.php - VERSION CORRIGÉE AVEC ADMIN
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Chemin absolu
define('BASE_PATH', dirname(__DIR__, 2));

// Inclure modèles
require_once BASE_PATH . '/backend/app/core/Database.php';
require_once BASE_PATH . '/backend/app/models/User.php';
require_once BASE_PATH . '/backend/app/models/Company.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userType = $_POST['userType'] ?? 'student';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($userType === 'student') {
        $user = new User();
        $result = $user->login($email, $password);
        
        if ($result['success']) {
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['user_type'] = 'student';
            $_SESSION['user_name'] = $result['user']['first_name'] . ' ' . $result['user']['last_name'];
            
            echo "<script>
                alert('✅ Connexion réussie !');
                window.location.href = 'dashboard_student.php';
            </script>";
            exit();
        } else {
            $message = '❌ ' . $result['message'];
        }
        
    } elseif ($userType === 'company') {
        $company = new Company();
        $result = $company->login($email, $password);
        
        if ($result['success']) {
            $_SESSION['company_id'] = $result['company']['id'];
            $_SESSION['user_type'] = 'company';
            $_SESSION['company_name'] = $result['company']['name'];
            
            echo "<script>
                alert('✅ Connexion entreprise réussie !');
                window.location.href = 'dashboard_company.php';
            </script>";
            exit();
        } else {
            $message = '❌ ' . $result['message'];
        }
        
    } elseif ($userType === 'admin') {
        // === AJOUT DU CODE ADMIN ===
        try {
            $database = Database::getInstance();
            $db = $database->getConnection();
            
            // Vérifier dans la table admins
            $query = "SELECT * FROM admins WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['admin_name'] = ($admin['first_name'] ?? 'Admin') . ' ' . ($admin['last_name'] ?? '');
                $_SESSION['admin_email'] = $admin['email'];
                
                echo "<script>
                    alert('✅ Connexion administrateur réussie !');
                    window.location.href = 'dashboard_admin.php';
                </script>";
                exit();
            } else {
                $message = '❌ Identifiants administrateur incorrects';
            }
        } catch (Exception $e) {
            $message = '❌ Erreur système administrateur';
        }
    }
}

// Lire HTML
$html = file_get_contents(BASE_PATH . '/frontend/login.html');

// Corriger chemins
$html = str_replace('href="css/', 'href="../frontend/css/', $html);
$html = str_replace('src="js/', 'src="../frontend/js/', $html);

// Action formulaire
$html = str_replace(
    'action="../backend/pages/login.php"',
    'action="login_ultimate.php"',
    $html
);

// Champ hidden userType
if (strpos($html, 'name="userType"') === false) {
    $html = str_replace(
        '<form id="loginForm"',
        '<form id="loginForm" method="POST" action="login_ultimate.php">
        <input type="hidden" name="userType" id="userTypeInput" value="student">',
        $html
    );
}

// Message erreur
if ($message) {
    $html = str_replace(
        '<div class="login-container">',
        '<div class="alert alert-danger m-3">' . $message . '</div><div class="login-container">',
        $html
    );
}

echo $html;
?>