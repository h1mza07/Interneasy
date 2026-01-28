<?php
// app/controllers/AuthController.php

require_once __DIR__ . '/../core/Session.php';

class AuthController {
    
    public function __construct() {
        Session::start();
        require_once __DIR__ . '/../models/Etudiant.php';
        require_once __DIR__ . '/../models/Entreprise.php';
        require_once __DIR__ . '/../models/Admin.php';
    }

    // AFFICHAGE FORMULAIRE INSCRIPTION ÉTUDIANT
    public function registerEtudiant() {
        // Rediriger si déjà connecté
        if (Session::isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once __DIR__ . '/../../static_pages/register.html';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $prenom = trim($_POST['prenom'] ?? '');
            $nom = trim($_POST['nom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['motDePasse'] ?? '';
            $confirmPassword = $_POST['confirmMotDePasse'] ?? '';

            // Validation
            if (empty($prenom) || empty($nom) || empty($email) || empty($password)) {
                $this->redirectWithError('Tous les champs sont obligatoires', 'registerEtudiant');
                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirectWithError('Email invalide', 'registerEtudiant');
                return;
            }

            if ($password !== $confirmPassword) {
                $this->redirectWithError('Les mots de passe ne correspondent pas', 'registerEtudiant');
                return;
            }

            if (strlen($password) < 6) {
                $this->redirectWithError('Le mot de passe doit faire au moins 6 caractères', 'registerEtudiant');
                return;
            }

            // Vérifier si email existe déjà
            if (Etudiant::emailExists($email)) {
                $this->redirectWithError('Cet email est déjà utilisé', 'registerEtudiant');
                return;
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $result = Etudiant::create($prenom, $nom, $email, $hashedPassword);

            if ($result) {
                $_SESSION['success_message'] = 'Inscription étudiante réussie ! Vous pouvez maintenant vous connecter.';
                header("Location: index.php?controller=Auth&action=login&success=1");
                exit();
            } else {
                $this->redirectWithError('Erreur lors de l\'inscription', 'registerEtudiant');
            }
        }
    }

    // INSCRIPTION ENTREPRISE
    public function registerEntreprise() {
        // Rediriger si déjà connecté
        if (Session::isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderRegisterEntrepriseForm();
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['motDePasse'] ?? '';
            $confirmPassword = $_POST['confirmMotDePasse'] ?? '';

            if (empty($nom) || empty($email) || empty($password)) {
                $this->redirectWithError('Tous les champs sont obligatoires', 'registerEntreprise');
                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirectWithError('Email invalide', 'registerEntreprise');
                return;
            }

            if ($password !== $confirmPassword) {
                $this->redirectWithError('Les mots de passe ne correspondent pas', 'registerEntreprise');
                return;
            }

            if (strlen($password) < 6) {
                $this->redirectWithError('Le mot de passe doit faire au moins 6 caractères', 'registerEntreprise');
                return;
            }

            // Vérifier si email existe déjà
            if (Entreprise::emailExists($email)) {
                $this->redirectWithError('Cet email est déjà utilisé', 'registerEntreprise');
                return;
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $result = Entreprise::create($nom, $email, $hashedPassword);

            if ($result) {
                $_SESSION['success_message'] = 'Inscription entreprise réussie ! Votre compte sera validé par un administrateur dans les plus brefs délais.';
                header("Location: index.php?controller=Auth&action=login&success=1");
                exit();
            } else {
                $this->redirectWithError('Erreur lors de l\'inscription', 'registerEntreprise');
            }
        }
    }

    // CONNEXION
    public function login() {
        // Rediriger si déjà connecté
        if (Session::isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }
        
        // Si GET, afficher le formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once __DIR__ . '/../../static_pages/login.html';
            return;
        }

        // Si POST, traiter la connexion
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['motDePasse'] ?? '';

        if (empty($email) || empty($password)) {
            $this->redirectWithError('Email et mot de passe requis', 'login', $email);
            return;
        }

        // 1. Vérifier ADMIN
        $admin = Admin::findByEmail($email);
        if ($admin && password_verify($password, $admin['motDePasse'])) {
            $this->loginUser($admin, 'admin', 'Administrateur');
            header("Location: dashboard_admin.php");
            exit();
        }

        // 2. Vérifier ENTREPRISE (validée)
        $entreprise = Entreprise::findByEmail($email);
        if ($entreprise && password_verify($password, $entreprise['motDePasse'])) {
            if ($entreprise['estValide'] == 0) {
                $this->redirectWithError('notvalidated', 'login', $email);
                return;
            }
            
            $this->loginUser($entreprise, 'entreprise', $entreprise['nom']);
            header("Location: dashboard_entreprise.php");
            exit();
        }

        // 3. Vérifier ÉTUDIANT
        $etudiant = Etudiant::findByEmail($email);
        if ($etudiant && password_verify($password, $etudiant['motDePasse'])) {
            $this->loginUser($etudiant, 'etudiant', $etudiant['prenom'] . ' ' . $etudiant['nom']);
            header("Location: dashboard_etudiant.php");
            exit();
        }

        // Si aucun compte ne correspond
        $this->redirectWithError('error', 'login', $email);
    }

    // DÉCONNEXION
    public function logout() {
        Session::destroy();
        header("Location: index.php?controller=Auth&action=login&logout=1");
        exit();
    }

    // PAGE D'ACCUEIL
    public function home() {
        require_once __DIR__ . '/../../static_pages/index.html';
    }

    // =========================
    // MÉTHODES PRIVÉES UTILITAIRES
    // =========================
    
    private function loginUser($userData, $role, $name) {
        Session::set('id', $userData['id']);
        Session::set('role', $role);
        Session::set('email', $userData['email']);
        Session::set('nom', $name);
        Session::set('user_data', $userData);
    }
    
    private function redirectToDashboard() {
        $role = Session::getUserRole();
        switch($role) {
            case 'admin':
                header("Location: dashboard_admin.php");
                break;
            case 'entreprise':
                header("Location: dashboard_entreprise.php");
                break;
            case 'etudiant':
                header("Location: dashboard_etudiant.php");
                break;
        }
        exit();
    }
    
    private function redirectWithError($errorType, $action, $email = '') {
        $url = "index.php?controller=Auth&action={$action}";
        if ($errorType) {
            $url .= "&{$errorType}=1";
        }
        if ($email) {
            $url .= "&email=" . urlencode($email);
        }
        header("Location: $url");
        exit();
    }
    
    private function renderRegisterEntrepriseForm() {
        // Récupérer les valeurs précédentes
        $nom = isset($_GET['nom']) ? htmlspecialchars($_GET['nom']) : '';
        $email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
        
        // Messages d'erreur/succès
        $errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
        $successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
        
        // Nettoyer les messages après les avoir récupérés
        unset($_SESSION['error_message']);
        unset($_SESSION['success_message']);
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Inscription Entreprise - InternEasy</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="/interneasy/static_pages/css/style.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0">Inscription Entreprise</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Nom de l'entreprise *</label>
                                        <input type="text" name="nom" class="form-control" required 
                                               value="<?php echo $nom; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control" required 
                                               value="<?php echo $email; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Mot de passe *</label>
                                        <input type="password" name="motDePasse" class="form-control" required>
                                        <small class="text-muted">Minimum 6 caractères</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirmer le mot de passe *</label>
                                        <input type="password" name="confirmMotDePasse" class="form-control" required>
                                    </div>
                                    
                                    <?php if ($errorMessage): ?>
                                        <div class='alert alert-danger'><?php echo $errorMessage; ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if ($successMessage): ?>
                                        <div class='alert alert-success'><?php echo $successMessage; ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">S'inscrire</button>
                                        <a href="index.php?controller=Auth&action=login" class="btn btn-link">
                                            Déjà un compte ? Se connecter
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <p class="text-muted">
                                <small>
                                    * Votre compte devra être validé par un administrateur avant de pouvoir vous connecter.
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}
?>