<?php
// ============================================
// CONFIGURATION INTERNEASY - BACKEND
// ============================================

// ====================
// 1. AFFICHAGE DES ERREURS (À DÉSACTIVER EN PRODUCTION)
// ====================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ====================
// 2. CONFIGURATION DE LA BASE DE DONNÉES
// ====================
define('DB_HOST', 'localhost');
define('DB_NAME', 'interneasy_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ====================
// 3. URLS DE L'APPLICATION
// ====================
define('SITE_URL', 'http://localhost/interneasy/');
define('BASE_URL', 'http://localhost/interneasy/backend/pages/');
define('FRONTEND_URL', 'http://localhost/interneasy/frontend/');
define('ASSETS_URL', 'http://localhost/interneasy/frontend/');

// ====================
// 4. CHEMINS ABSOLUS SUR LE SERVEUR
// ====================
define('ROOT_PATH', dirname(__DIR__)); // Chemin vers /backend
define('PROJECT_ROOT', dirname(dirname(__DIR__))); // Chemin vers /interneasy
define('FRONTEND_PATH', PROJECT_ROOT . '/frontend/');
define('BACKEND_PATH', PROJECT_ROOT . '/backend/');
define('UPLOAD_PATH', PROJECT_ROOT . '/uploads/');
define('LOG_PATH', BACKEND_PATH . '/logs/');

// ====================
// 5. CONFIGURATION DE L'APPLICATION
// ====================
define('APP_NAME', 'InternEasy');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // development | production

// Configuration des uploads
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 Mo
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);
define('MAX_CV_SIZE', 2 * 1024 * 1024); // 2 Mo pour les CV

// Configuration des sessions
define('SESSION_LIFETIME', 86400); // 24 heures en secondes
define('SESSION_NAME', 'interneasy_session');

// ====================
// 6. SÉCURITÉ
// ====================
define('ENCRYPTION_KEY', 'internEasy2024!SecureKey#123');
define('CSRF_TOKEN_NAME', 'csrf_token');

// ====================
// 7. DÉMARRAGE DE LA SESSION
// ====================
if (session_status() === PHP_SESSION_NONE) {
    // Configuration de la session
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Mettre à true en production avec HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    session_start();
    
    // Régénérer l'ID de session périodiquement pour la sécurité
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// ====================
// 8. FONCTIONS UTILITAIRES
// ====================

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['company_id']);
}

/**
 * Récupère le rôle de l'utilisateur connecté
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Redirige vers une URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Échappe les caractères HTML pour la sécurité
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un token CSRF
 */
function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Vérifie un token CSRF
 */
function verifyCsrfToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && 
           hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Affiche un message flash
 */
function flash($name, $message = '') {
    if ($message !== '') {
        // Stocker le message
        $_SESSION['flash_' . $name] = $message;
    } else if (isset($_SESSION['flash_' . $name])) {
        // Récupérer et supprimer le message
        $message = $_SESSION['flash_' . $name];
        unset($_SESSION['flash_' . $name]);
        return $message;
    }
    return '';
}

/**
 * Valide un email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Crée le dossier uploads s'il n'existe pas
 */
function ensureUploadsDir() {
    if (!file_exists(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
}

// ====================
// 9. CONNEXION À LA BASE DE DONNÉES (Fonction)
// ====================

/**
 * Retourne une instance de PDO
 */
function getDatabaseConnection() {
    static $db = null;
    
    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $db = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            // En mode développement, afficher l'erreur
            if (APP_ENV === 'development') {
                die("Erreur de connexion à la base de données : " . $e->getMessage());
            } else {
                // En production, logger l'erreur et afficher un message générique
                error_log("Database connection error: " . $e->getMessage());
                die("Une erreur est survenue. Veuillez réessayer plus tard.");
            }
        }
    }
    
    return $db;
}

// ====================
// 10. AUTOLOADER SIMPLIFIÉ
// ====================

spl_autoload_register(function($className) {
    $directories = [
        BACKEND_PATH . '/app/core/',
        BACKEND_PATH . '/app/models/',
        BACKEND_PATH . '/app/controllers/',
        BACKEND_PATH . '/app/helpers/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Si la classe n'est pas trouvée
    if (APP_ENV === 'development') {
        die("Classe '$className' non trouvée. Vérifiez le chemin: " . $file);
    }
});

// ====================
// 11. INITIALISATION
// ====================

// Créer le dossier uploads s'il n'existe pas
ensureUploadsDir();

// Initialiser les logs si nécessaire
if (APP_ENV === 'development' && !file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Message de debug en mode développement
if (APP_ENV === 'development') {
    error_log("[" . date('Y-m-d H:i:s') . "] InternEasy backend démarré");
}

// ====================
// 12. GESTION DES ERREURS PERSONNALISÉE
// ====================

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (APP_ENV === 'development') {
        echo "<div style='background:#f8d7da;color:#721c24;padding:15px;margin:10px;border:1px solid #f5c6cb;border-radius:5px;'>";
        echo "<strong>Erreur PHP:</strong> $errstr<br>";
        echo "<strong>Fichier:</strong> $errfile<br>";
        echo "<strong>Ligne:</strong> $errline<br>";
        echo "</div>";
    } else {
        error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    }
    
    // Ne pas exécuter le gestionnaire d'erreurs interne de PHP
    return true;
});

// Gestionnaire d'exceptions
set_exception_handler(function($exception) {
    if (APP_ENV === 'development') {
        echo "<div style='background:#f8d7da;color:#721c24;padding:15px;margin:10px;border:1px solid #f5c6cb;border-radius:5px;'>";
        echo "<strong>Exception:</strong> " . $exception->getMessage() . "<br>";
        echo "<strong>Fichier:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Ligne:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Trace:</strong><pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        echo "Une erreur est survenue. Veuillez réessayer plus tard.";
    }
});
?>