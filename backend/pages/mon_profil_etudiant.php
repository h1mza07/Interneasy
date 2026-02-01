<?php
// mon_profil_etudiant.php - Profil étudiant
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

$message = '';
$success = false;

// Récupérer les informations actuelles de l'étudiant
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $db->prepare($studentQuery);
$studentStmt->execute([$_SESSION['user_id']]);
$student = $studentStmt->fetch();

if (!$student) {
    header('Location: ../login_ultimate.php');
    exit();
}

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Mise à jour des informations de base
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $dateOfBirth = $_POST['date_of_birth'] ?? '';
        $fieldOfStudy = trim($_POST['field_of_study'] ?? '');
        $educationLevel = trim($_POST['education_level'] ?? '');
        $university = trim($_POST['university'] ?? '');
        
        // Validation email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "❌ Format d'email invalide";
        } else {
            // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
            $emailCheckQuery = "SELECT id FROM students WHERE email = ? AND id != ?";
            $emailCheckStmt = $db->prepare($emailCheckQuery);
            $emailCheckStmt->execute([$email, $_SESSION['user_id']]);
            
            if ($emailCheckStmt->fetch()) {
                $message = "❌ Cet email est déjà utilisé par un autre compte";
            } else {
                // Mettre à jour le profil
                $updateQuery = "UPDATE students SET 
                                first_name = ?, 
                                last_name = ?, 
                                email = ?, 
                                phone = ?, 
                                address = ?, 
                                date_of_birth = ?, 
                                field_of_study = ?, 
                                education_level = ?, 
                                school = ?,
                                updated_at = CURRENT_TIMESTAMP
                                WHERE id = ?";
                
                $updateStmt = $db->prepare($updateQuery);
                
                if ($updateStmt->execute([
                    $firstName, $lastName, $email, $phone, $address, 
                    $dateOfBirth, $fieldOfStudy, $educationLevel, $university,
                    $_SESSION['user_id']
                ])) {
                    $message = "✅ Profil mis à jour avec succès !";
                    $success = true;
                    
                    // Mettre à jour la session
                    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                    $_SESSION['email'] = $email;
                    
                    // Recharger les données
                    $studentStmt->execute([$_SESSION['user_id']]);
                    $student = $studentStmt->fetch();
                } else {
                    $message = "❌ Erreur lors de la mise à jour";
                }
            }
        }
    }
    
    // Changement de mot de passe
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Vérifier le mot de passe actuel
        if (!password_verify($currentPassword, $student['password'])) {
            $message = "❌ Mot de passe actuel incorrect";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "❌ Les nouveaux mots de passe ne correspondent pas";
        } elseif (strlen($newPassword) < 6) {
            $message = "❌ Le mot de passe doit contenir au moins 6 caractères";
        } else {
            // Hasher le nouveau mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $passwordQuery = "UPDATE students SET password = ? WHERE id = ?";
            $passwordStmt = $db->prepare($passwordQuery);
            
            if ($passwordStmt->execute([$hashedPassword, $_SESSION['user_id']])) {
                $message = "✅ Mot de passe changé avec succès !";
                $success = true;
            } else {
                $message = "❌ Erreur lors du changement de mot de passe";
            }
        }
    }
}

// Compter les CVs
$cvsCountQuery = "SELECT COUNT(*) as count FROM cvs WHERE student_id = ?";
$cvsStmt = $db->prepare($cvsCountQuery);
$cvsStmt->execute([$_SESSION['user_id']]);
$cvsCount = $cvsStmt->fetch()['count'];

// Compter les candidatures
$appsCountQuery = "SELECT COUNT(*) as count FROM applications WHERE student_id = ?";
$appsStmt = $db->prepare($appsCountQuery);
$appsStmt->execute([$_SESSION['user_id']]);
$appsCount = $appsStmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .avatar-large {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto;
        }
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .nav-tabs .nav-link {
            border: none;
            color: var(--gray);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        .nav-tabs .nav-link.active {
            color: var(--primary);
            background: white;
            border-bottom: 3px solid var(--primary);
        }
        .tab-content {
            background: white;
            border-radius: 0 var(--border-radius) var(--border-radius) var(--border-radius);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .form-label {
            font-weight: 600;
            color: var(--dark);
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <div class="avatar-large mb-3">
                        <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                    </div>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-2"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h1>
                    <p class="mb-2">
                        <i class="bi bi-person-badge"></i> Étudiant • 
                        <i class="bi bi-mortarboard"></i> <?= htmlspecialchars($student['education_level'] ?? 'Non spécifié') ?>
                    </p>
                    <p class="mb-0">
                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($student['email']) ?> • 
                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($student['phone'] ?? 'Non renseigné') ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h3 class="text-primary"><?= $appsCount ?></h3>
                    <p class="text-muted mb-0">Candidatures</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3 class="text-success"><?= $cvsCount ?></h3>
                    <p class="text-muted mb-0">CVs téléchargés</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3 class="text-warning">
                        <?= date('d/m/Y', strtotime($student['created_at'])) ?>
                    </h3>
                    <p class="text-muted mb-0">Membre depuis</p>
                </div>
            </div>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
        <div class="alert alert-<?= $success ? 'success' : 'danger' ?> alert-dismissible fade show mb-4">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Onglets -->
        <ul class="nav nav-tabs" id="profileTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#informations">
                    <i class="bi bi-person"></i> Informations
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#password">
                    <i class="bi bi-shield-lock"></i> Sécurité
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#activity">
                    <i class="bi bi-activity"></i> Activité
                </button>
            </li>
        </ul>

        <!-- Contenu des onglets -->
        <div class="tab-content">
            <!-- Onglet 1 : Informations -->
            <div class="tab-pane fade show active" id="informations">
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom *</label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?= htmlspecialchars($student['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?= htmlspecialchars($student['last_name']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($student['email']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?= htmlspecialchars($student['phone'] ?? '') ?>"
                                   placeholder="+212 6 XX XX XX XX">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <textarea name="address" class="form-control" rows="2"
                                  placeholder="Adresse complète"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date de naissance</label>
                            <input type="date" name="date_of_birth" class="form-control" 
                                   value="<?= htmlspecialchars($student['date_of_birth'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Niveau d'études</label>
                            <select name="education_level" class="form-select">
                                <option value="">Sélectionnez</option>
                                <option value="BAC+2" <?= ($student['education_level'] ?? '') == 'BAC+2' ? 'selected' : '' ?>>BAC+2</option>
                                <option value="BAC+3" <?= ($student['education_level'] ?? '') == 'BAC+3' ? 'selected' : '' ?>>BAC+3</option>
                                <option value="BAC+4" <?= ($student['education_level'] ?? '') == 'BAC+4' ? 'selected' : '' ?>>BAC+4</option>
                                <option value="BAC+5" <?= ($student['education_level'] ?? '') == 'BAC+5' ? 'selected' : '' ?>>BAC+5</option>
                                <option value="BAC+6 et plus" <?= ($student['education_level'] ?? '') == 'BAC+6 et plus' ? 'selected' : '' ?>>BAC+6 et plus</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Domaine d'études</label>
                            <input type="text" name="field_of_study" class="form-control" 
                                   value="<?= htmlspecialchars($student['field_of_study'] ?? '') ?>"
                                   placeholder="Ex: Informatique, Marketing, Finance...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Université/École</label>
                            <input type="text" name="university" class="form-control" 
                                   value="<?= htmlspecialchars($student['university'] ?? '') ?>"
                                   placeholder="Ex: Université Hassan II, ENSA...">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="dashboard_student.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>

            <!-- Onglet 2 : Sécurité -->
            <div class="tab-pane fade" id="password">
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mot de passe actuel *</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nouveau mot de passe *</label>
                            <input type="password" name="new_password" class="form-control" required
                                   minlength="6">
                            <small class="text-muted">Minimum 6 caractères</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirmer le nouveau mot de passe *</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Conseil de sécurité :</strong> Utilisez un mot de passe fort contenant des lettres, chiffres et caractères spéciaux.
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="dashboard_student.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key"></i> Changer le mot de passe
                        </button>
                    </div>
                </form>
            </div>

            <!-- Onglet 3 : Activité -->
            <div class="tab-pane fade" id="activity">
                <h5>Dernières activités</h5>
                
                <?php
                // Récupérer les dernières candidatures
                $recentActivityQuery = "SELECT a.*, o.title, c.name as company_name, a.sent_at
                                        FROM applications a 
                                        JOIN offers o ON a.offer_id = o.id 
                                        JOIN companies c ON o.company_id = c.id 
                                        WHERE a.student_id = ? 
                                        ORDER BY a.sent_at DESC 
                                        LIMIT 5";
                $recentStmt = $db->prepare($recentActivityQuery);
                $recentStmt->execute([$_SESSION['user_id']]);
                $recentActivities = $recentStmt->fetchAll();
                ?>
                
                <?php if (empty($recentActivities)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-activity display-1 text-muted"></i>
                        <p class="mt-3">Aucune activité récente</p>
                        <a href="offers.php" class="btn btn-primary">Consulter les offres</a>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?= htmlspecialchars($activity['title']) ?></h6>
                                <small><?= date('d/m/Y H:i', strtotime($activity['sent_at'])) ?></small>
                            </div>
                            <p class="mb-1">
                                <i class="bi bi-building"></i> <?= htmlspecialchars($activity['company_name']) ?>
                            </p>
                            <small>
                                Statut: 
                                <span class="badge bg-<?= 
                                    $activity['status'] == 'pending' ? 'warning' : 
                                    ($activity['status'] == 'accepted' ? 'success' : 'danger')
                                ?>">
                                    <?= $activity['status'] == 'pending' ? 'En attente' : 
                                       ($activity['status'] == 'accepted' ? 'Acceptée' : 'Refusée') ?>
                                </span>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="mes_candidatures.php" class="btn btn-outline-primary">
                        <i class="bi bi-clock-history"></i> Voir tout l'historique
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activer les onglets Bootstrap
        const triggerTabList = document.querySelectorAll('#profileTabs button');
        triggerTabList.forEach(triggerEl => {
            const tabTrigger = new bootstrap.Tab(triggerEl);
            triggerEl.addEventListener('click', event => {
                event.preventDefault();
                tabTrigger.show();
            });
        });
    </script>
</body>
</html>