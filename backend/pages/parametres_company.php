<?php
session_start();

if (!isset($_SESSION['company_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

$companyId = $_SESSION['company_id'];

// Récupérer info entreprise
$companyQuery = "SELECT * FROM companies WHERE id = ?";
$companyStmt = $db->prepare($companyQuery);
$companyStmt->execute([$companyId]);
$company = $companyStmt->fetch();

$message = '';
$success = false;

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mise à jour infos de base
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Format d'email invalide";
    } else {
        $updateQuery = "UPDATE companies SET name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        
        if ($updateStmt->execute([$name, $email, $phone, $companyId])) {
            $message = "✅ Paramètres mis à jour avec succès";
            $success = true;
            $_SESSION['company_name'] = $name;
            $companyStmt->execute([$companyId]);
            $company = $companyStmt->fetch();
        } else {
            $message = "❌ Erreur lors de la mise à jour";
        }
    }
    
    // Notification settings (si cochées)
    if (isset($_POST['notifications'])) {
        // À implémenter si vous avez une table settings
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres Entreprise • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .form-label { font-weight: 600; }
        .section-title {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
            color: #1e3c72;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><i class="bi bi-gear"></i> Paramètres Entreprise</h1>
                <p class="text-muted">Gérez vos préférences et informations</p>
            </div>
            <a href="dashboard_company.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $success ? 'success' : 'danger' ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Informations générales -->
            <div class="col-lg-8">
                <div class="settings-card">
                    <h3 class="section-title">Informations générales</h3>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom de l'entreprise *</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($company['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($company['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($company['phone'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Enregistrer les modifications
                        </button>
                    </form>
                </div>

                <!-- Notifications -->
                <div class="settings-card">
                    <h3 class="section-title">Notifications</h3>
                    <form method="POST">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="notifications[]" value="new_application" id="newApp" checked>
                            <label class="form-check-label" for="newApp">
                                Nouvelles candidatures
                            </label>
                            <small class="d-block text-muted">Recevoir une notification pour chaque nouvelle candidature</small>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="notifications[]" value="messages" id="messages" checked>
                            <label class="form-check-label" for="messages">
                                Messages des étudiants
                            </label>
                            <small class="d-block text-muted">Recevoir des notifications pour les messages</small>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="notifications[]" value="newsletter" id="newsletter">
                            <label class="form-check-label" for="newsletter">
                                Newsletter InternEasy
                            </label>
                            <small class="d-block text-muted">Recevoir des conseils et actualités</small>
                        </div>
                        
                        <button type="submit" class="btn btn-outline-primary">
                            Mettre à jour les préférences
                        </button>
                    </form>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="col-lg-4">
                <div class="settings-card">
                    <h3 class="section-title">Actions</h3>
                    
                    <div class="d-grid gap-2">
                        <a href="mon_profil_entreprise.php" class="btn btn-outline-primary">
                            <i class="bi bi-person"></i> Voir mon profil public
                        </a>
                        
                        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#passwordModal">
                            <i class="bi bi-key"></i> Changer le mot de passe
                        </button>
                        
                        <a href="logout.php" class="btn btn-outline-danger">
                            <i class="bi bi-box-arrow-right"></i> Se déconnecter
                        </a>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle"></i>
                        <strong>Besoin d'aide ?</strong><br>
                        <small>Contactez notre support à support@interneasy.ma</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal changement mot de passe -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Changer le mot de passe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Cette fonctionnalité sera bientôt disponible.</p>
                    <p class="text-muted">Pour changer votre mot de passe, utilisez la page "Mon profil".</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <a href="mon_profil_entreprise.php" class="btn btn-primary">
                        Aller au profil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


