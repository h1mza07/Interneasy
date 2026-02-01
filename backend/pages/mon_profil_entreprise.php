<?php
// mon_profil_entreprise.php - VERSION CORRIGÉE POUR VOTRE TABLE + CARTE
session_start();

if (!isset($_SESSION['company_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: ../login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

$message = '';
$success = false;

// Récupérer les informations actuelles de l'entreprise
$companyQuery = "SELECT * FROM companies WHERE id = ?";
$companyStmt = $db->prepare($companyQuery);
$companyStmt->execute([$_SESSION['company_id']]);
$company = $companyStmt->fetch();

if (!$company) {
    header('Location: ../login_ultimate.php');
    exit();
}

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Mise à jour des informations de base
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $sector = trim($_POST['sector'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $size = trim($_POST['size'] ?? '');
        $foundedYear = $_POST['founded_year'] ?? '';
        $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
        $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
        
        // Validation email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "❌ Format d'email invalide";
        } else {
            // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
            $emailCheckQuery = "SELECT id FROM companies WHERE email = ? AND id != ?";
            $emailCheckStmt = $db->prepare($emailCheckQuery);
            $emailCheckStmt->execute([$email, $_SESSION['company_id']]);
            
            if ($emailCheckStmt->fetch()) {
                $message = "❌ Cet email est déjà utilisé par une autre entreprise";
            } else {
                // Mettre à jour le profil - AVEC LATITUDE/LONGITUDE
                $updateQuery = "UPDATE companies SET 
                                name = ?, 
                                email = ?, 
                                phone = ?, 
                                address = ?, 
                                city = ?, 
                                website = ?, 
                                activity_sector = ?, 
                                description = ?, 
                                company_size = ?, 
                                founded_year = ?,
                                latitude = ?,      // NOUVEAU
                                longitude = ?,     // NOUVEAU
                                updated_at = CURRENT_TIMESTAMP
                                WHERE id = ?";
                
                $updateStmt = $db->prepare($updateQuery);
                
                if ($updateStmt->execute([
                    $name, $email, $phone, $address, $city, 
                    $website, $sector, $description, $size, $foundedYear,
                    $latitude, $longitude, // NOUVEAU
                    $_SESSION['company_id']
                ])) {
                    $message = "✅ Profil mis à jour avec succès !";
                    $success = true;
                    
                    // Mettre à jour la session
                    $_SESSION['company_name'] = $name;
                    $_SESSION['email'] = $email;
                    
                    // Recharger les données
                    $companyStmt->execute([$_SESSION['company_id']]);
                    $company = $companyStmt->fetch();
                } else {
                    $message = "❌ Erreur lors de la mise à jour: " . implode(', ', $updateStmt->errorInfo());
                }
            }
        }
    }
    
    // Changement de mot de passe (inchangé)
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Vérifier le mot de passe actuel
        if (!password_verify($currentPassword, $company['password'])) {
            $message = "❌ Mot de passe actuel incorrect";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "❌ Les nouveaux mots de passe ne correspondent pas";
        } elseif (strlen($newPassword) < 6) {
            $message = "❌ Le mot de passe doit contenir au moins 6 caractères";
        } else {
            // Hasher le nouveau mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $passwordQuery = "UPDATE companies SET password = ? WHERE id = ?";
            $passwordStmt = $db->prepare($passwordQuery);
            
            if ($passwordStmt->execute([$hashedPassword, $_SESSION['company_id']])) {
                $message = "✅ Mot de passe changé avec succès !";
                $success = true;
            } else {
                $message = "❌ Erreur lors du changement de mot de passe";
            }
        }
    }
}

// Statistiques de l'entreprise
$offersCountQuery = "SELECT COUNT(*) as count FROM offers WHERE company_id = ?";
$offersStmt = $db->prepare($offersCountQuery);
$offersStmt->execute([$_SESSION['company_id']]);
$offersCount = $offersStmt->fetch()['count'];

$applicationsCountQuery = "SELECT COUNT(*) as count FROM applications a 
                          JOIN offers o ON a.offer_id = o.id 
                          WHERE o.company_id = ?";
$applicationsStmt = $db->prepare($applicationsCountQuery);
$applicationsStmt->execute([$_SESSION['company_id']]);
$applicationsCount = $applicationsStmt->fetch()['count'];

$pendingCountQuery = "SELECT COUNT(*) as count FROM applications a 
                     JOIN offers o ON a.offer_id = o.id 
                     WHERE o.company_id = ? AND a.status = 'pending'";
$pendingStmt = $db->prepare($pendingCountQuery);
$pendingStmt->execute([$_SESSION['company_id']]);
$pendingCount = $pendingStmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil Entreprise • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .avatar-large {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto;
            border: 5px solid white;
        }
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px 8px 0 0;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            background: white;
            border-bottom: 3px solid #0d6efd;
        }
        .tab-content {
            background: white;
            border-radius: 0 8px 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .form-label {
            font-weight: 600;
            color: #212529;
        }
        .sector-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .coords-help {
            font-size: 0.85rem;
            color: #6c757d;
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
                        <?= strtoupper(substr($company['name'], 0, 2)) ?>
                    </div>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-2"><?= htmlspecialchars($company['name']) ?></h1>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <?php if (!empty($company['activity_sector'])): ?>
                            <span class="sector-badge">
                                <i class="bi bi-briefcase"></i> <?= htmlspecialchars($company['activity_sector']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($company['company_size'])): ?>
                            <span class="sector-badge">
                                <i class="bi bi-people"></i> <?= htmlspecialchars($company['company_size']) ?> employés
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="mb-1">
                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($company['email']) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-primary"><?= $offersCount ?></h3>
                    <p class="text-muted mb-0">Offres publiées</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-success"><?= $applicationsCount ?></h3>
                    <p class="text-muted mb-0">Candidatures reçues</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-warning"><?= $pendingCount ?></h3>
                    <p class="text-muted mb-0">En attente</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-info">
                        <?php if (!empty($company['created_at'])): ?>
                            <?= date('d/m/Y', strtotime($company['created_at'])) ?>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
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
                    <i class="bi bi-building"></i> Informations
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#password">
                    <i class="bi bi-shield-lock"></i> Sécurité
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
                            <label class="form-label">Nom de l'entreprise *</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?= htmlspecialchars($company['name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($company['email']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?= htmlspecialchars($company['phone'] ?? '') ?>"
                                   placeholder="+212 5 XX XX XX XX">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Site web</label>
                            <input type="url" name="website" class="form-control" 
                                   value="<?= htmlspecialchars($company['website'] ?? '') ?>"
                                   placeholder="https://www.example.com">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="address" class="form-control" 
                                   value="<?= htmlspecialchars($company['address'] ?? '') ?>"
                                   placeholder="Adresse complète">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ville</label>
                            <input type="text" name="city" class="form-control" 
                                   value="<?= htmlspecialchars($company['city'] ?? '') ?>"
                                   placeholder="Casablanca, Rabat, Marrakech...">
                        </div>
                    </div>
                    
                    <!-- NOUVEAU : Coordonnées géographiques pour la carte -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Latitude (pour la carte)</label>
                            <input type="number" name="latitude" class="form-control" 
                                   value="<?= htmlspecialchars($company['latitude'] ?? '') ?>"
                                   step="0.00000001" min="-90" max="90"
                                   placeholder="Ex: 33.573110">
                            <div class="coords-help">
                                <i class="bi bi-info-circle"></i> Optionnel - Pour apparaître sur la carte
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Longitude (pour la carte)</label>
                            <input type="number" name="longitude" class="form-control" 
                                   value="<?= htmlspecialchars($company['longitude'] ?? '') ?>"
                                   step="0.00000001" min="-180" max="180"
                                   placeholder="Ex: -7.589843">
                            <div class="coords-help">
                                <i class="bi bi-info-circle"></i> Optionnel - Pour apparaître sur la carte
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Secteur d'activité</label>
                            <select name="sector" class="form-select">
                                <option value="">Sélectionnez</option>
                                <option value="Informatique / Tech" <?= ($company['activity_sector'] ?? '') == 'Informatique / Tech' ? 'selected' : '' ?>>Informatique / Tech</option>
                                <option value="Finance / Banque" <?= ($company['activity_sector'] ?? '') == 'Finance / Banque' ? 'selected' : '' ?>>Finance / Banque</option>
                                <option value="Marketing / Communication" <?= ($company['activity_sector'] ?? '') == 'Marketing / Communication' ? 'selected' : '' ?>>Marketing / Communication</option>
                                <option value="Commerce / Distribution" <?= ($company['activity_sector'] ?? '') == 'Commerce / Distribution' ? 'selected' : '' ?>>Commerce / Distribution</option>
                                <option value="Industrie" <?= ($company['activity_sector'] ?? '') == 'Industrie' ? 'selected' : '' ?>>Industrie</option>
                                <option value="Santé" <?= ($company['activity_sector'] ?? '') == 'Santé' ? 'selected' : '' ?>>Santé</option>
                                <option value="Éducation" <?= ($company['activity_sector'] ?? '') == 'Éducation' ? 'selected' : '' ?>>Éducation</option>
                                <option value="Tourisme" <?= ($company['activity_sector'] ?? '') == 'Tourisme' ? 'selected' : '' ?>>Tourisme</option>
                                <option value="Autre" <?= ($company['activity_sector'] ?? '') == 'Autre' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Taille de l'entreprise</label>
                            <select name="size" class="form-select">
                                <option value="">Sélectionnez</option>
                                <option value="1-10 employés" <?= ($company['company_size'] ?? '') == '1-10 employés' ? 'selected' : '' ?>>1-10 employés</option>
                                <option value="11-50 employés" <?= ($company['company_size'] ?? '') == '11-50 employés' ? 'selected' : '' ?>>11-50 employés</option>
                                <option value="51-200 employés" <?= ($company['company_size'] ?? '') == '51-200 employés' ? 'selected' : '' ?>>51-200 employés</option>
                                <option value="201-500 employés" <?= ($company['company_size'] ?? '') == '201-500 employés' ? 'selected' : '' ?>>201-500 employés</option>
                                <option value="501-1000 employés" <?= ($company['company_size'] ?? '') == '501-1000 employés' ? 'selected' : '' ?>>501-1000 employés</option>
                                <option value="1000+ employés" <?= ($company['company_size'] ?? '') == '1000+ employés' ? 'selected' : '' ?>>1000+ employés</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Année de création</label>
                            <input type="number" name="founded_year" class="form-control" 
                                   value="<?= htmlspecialchars($company['founded_year'] ?? '') ?>"
                                   min="1900" max="<?= date('Y') ?>"
                                   placeholder="<?= date('Y') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description de l'entreprise</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Décrivez votre entreprise, ses activités, sa mission..."><?= htmlspecialchars($company['description'] ?? '') ?></textarea>
                        <small class="text-muted">Cette description sera visible sur vos offres.</small>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="dashboard_company.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Retour au dashboard
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>

            <!-- Onglet 2 : Sécurité (inchangé) -->
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
                        <a href="dashboard_company.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key"></i> Changer le mot de passe
                        </button>
                    </div>
                </form>
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
        
        // Fonction pour géocoder automatiquement l'adresse (OPTIONNEL)
        function geocodeAddress() {
            const address = document.querySelector('input[name="address"]').value;
            const city = document.querySelector('input[name="city"]').value;
            
            if (!address || !city) {
                alert('Veuillez d\'abord remplir l\'adresse et la ville');
                return;
            }
            
            const fullAddress = encodeURIComponent(address + ', ' + city + ', Maroc');
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${fullAddress}&limit=1`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        document.querySelector('input[name="latitude"]').value = data[0].lat;
                        document.querySelector('input[name="longitude"]').value = data[0].lon;
                        alert('Coordonnées automatiquement détectées !');
                    } else {
                        alert('Impossible de trouver les coordonnées pour cette adresse');
                    }
                })
                .catch(error => {
                    console.error('Erreur de géocodage:', error);
                    alert('Erreur lors de la détection des coordonnées');
                });
        }
        
        // Ajouter un bouton pour géocoder automatiquement
        document.addEventListener('DOMContentLoaded', function() {
            const cityField = document.querySelector('input[name="city"]');
            if (cityField) {
                const geocodeButton = document.createElement('button');
                geocodeButton.type = 'button';
                geocodeButton.className = 'btn btn-sm btn-outline-info mt-2';
                geocodeButton.innerHTML = '<i class="bi bi-geo-alt"></i> Détecter automatiquement';
                geocodeButton.onclick = geocodeAddress;
                
                cityField.parentNode.appendChild(geocodeButton);
            }
        });
    </script>
</body>
</html>