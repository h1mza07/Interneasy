<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

$studentId = $_SESSION['user_id'];

// Récupérer info étudiant
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $db->prepare($studentQuery);
$studentStmt->execute([$studentId]);
$student = $studentStmt->fetch();

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $notifications = $_POST['notifications'] ?? [];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Format d'email invalide";
    } else {
        $updateQuery = "UPDATE students SET email = ?, phone = ?, updated_at = NOW() WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        
        if ($updateStmt->execute([$email, $phone, $studentId])) {
            $message = "✅ Paramètres mis à jour avec succès";
            $success = true;
            $_SESSION['email'] = $email;
            $studentStmt->execute([$studentId]);
            $student = $studentStmt->fetch();
        } else {
            $message = "❌ Erreur lors de la mise à jour";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres Étudiant • InternEasy</title>
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
                <h1><i class="bi bi-gear"></i> Paramètres</h1>
                <p class="text-muted">Gérez vos préférences</p>
            </div>
            <a href="dashboard_student.php" class="btn btn-outline-primary">
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
            <div class="col-lg-8">
                <!-- Informations de contact -->
                <div class="settings-card">
                    <h3 class="section-title">Informations de contact</h3>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($student['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Enregistrer
                        </button>
                    </form>
                </div>

                <!-- Notifications -->
                <div class="settings-card">
                    <h3 class="section-title">Préférences de notifications</h3>
                    <form method="POST">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="notifications[]" value="application_updates" id="appUpdates" checked>
                            <label class="form-check-label" for="appUpdates">
                                Mises à jour des candidatures
                            </label>
                            <small class="d-block text-muted">Recevoir des notifications quand le statut de vos candidatures change</small>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="notifications[]" value="new_offers" id="newOffers" checked>
                            <label class="form-check-label" for="newOffers">
                                Nouvelles offres correspondantes
                            </label>
                            <small class="d-block text-muted">Recevoir des alertes pour les nouvelles offres dans votre domaine</small>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="notifications[]" value="newsletter" id="newsletter">
                            <label class="form-check-label" for="newsletter">
                                Newsletter InternEasy
                            </label>
                            <small class="d-block text-muted">Conseils, astuces et actualités sur les stages</small>
                        </div>
                        
                        <button type="submit" class="btn btn-outline-primary">
                            Mettre à jour les préférences
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Actions rapides -->
                <div class="settings-card">
                    <h3 class="section-title">Actions</h3>
                    
                    <div class="d-grid gap-2">
                        <a href="mon_profil_etudiant.php" class="btn btn-outline-primary">
                            <i class="bi bi-person"></i> Voir mon profil
                        </a>
                        
                        <a href="upload_cv.php" class="btn btn-outline-success">
                            <i class="bi bi-file-earmark-pdf"></i> Gérer mes CVs
                        </a>
                        
                        <a href="logout.php" class="btn btn-outline-danger">
                            <i class="bi bi-box-arrow-right"></i> Se déconnecter
                        </a>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle"></i>
                        <strong>Support technique</strong><br>
                        <small>Contactez-nous à support@interneasy.ma</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>