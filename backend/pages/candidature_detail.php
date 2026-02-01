<?php
// candidature_detail.php - Détails d'une candidature
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer l'ID de la candidature
$candidature_id = $_GET['id'] ?? 0;

// Récupérer les détails de la candidature
$query = "SELECT a.*, 
                 o.title, 
                 o.description, 
                 o.city, 
                 o.domain, 
                 o.duration,
                 o.salary,
                 o.contract_type,
                 o.start_date,
                 o.deadline,
                 c.name as company_name,
                 c.email as company_email,
                 c.phone as company_phone,
                 c.address as company_address,
                 cv.cv_name,
                 cv.file_path as cv_path,
                 a.message,
                 a.sent_at,
                 a.status
          FROM applications a 
          JOIN offers o ON a.offer_id = o.id 
          JOIN companies c ON o.company_id = c.id
          LEFT JOIN cvs cv ON a.cv_id = cv.id
          WHERE a.id = ? AND a.student_id = ?";
          
$stmt = $db->prepare($query);
$stmt->execute([$candidature_id, $_SESSION['user_id']]);
$candidature = $stmt->fetch();

if (!$candidature) {
    header('Location: mes_candidatures.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Candidature • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .header-section {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .info-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-section">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">Détails de la candidature</h1>
                    <p class="mb-0">Poste: <?= htmlspecialchars($candidature['title']) ?></p>
                </div>
                <div>
                    <a href="mes_candidatures.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Informations de l'offre -->
                <div class="info-card">
                    <h4><i class="bi bi-briefcase me-2"></i>Informations sur l'offre</h4>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Poste:</strong> <?= htmlspecialchars($candidature['title']) ?></p>
                            <p><strong>Entreprise:</strong> <?= htmlspecialchars($candidature['company_name']) ?></p>
                            <p><strong>Lieu:</strong> <?= htmlspecialchars($candidature['city']) ?></p>
                            <p><strong>Domaine:</strong> <?= htmlspecialchars($candidature['domain']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Durée:</strong> <?= htmlspecialchars($candidature['duration']) ?> mois</p>
                            <p><strong>Salaire:</strong> <?= htmlspecialchars($candidature['salary']) ?> MAD</p>
                            <p><strong>Type de contrat:</strong> <?= htmlspecialchars($candidature['contract_type']) ?></p>
                            <p><strong>Date limite:</strong> <?= date('d/m/Y', strtotime($candidature['deadline'])) ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($candidature['description'])): ?>
                    <div class="mt-3">
                        <h5>Description du poste:</h5>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($candidature['description'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Votre message -->
                <?php if (!empty($candidature['message'])): ?>
                <div class="info-card">
                    <h4><i class="bi bi-chat-text me-2"></i>Votre message de motivation</h4>
                    <hr>
                    <p><?= nl2br(htmlspecialchars($candidature['message'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- CV envoyé -->
                <?php if (!empty($candidature['cv_path']) && file_exists($candidature['cv_path'])): ?>
                <div class="info-card">
                    <h4><i class="bi bi-file-earmark-pdf me-2"></i>CV envoyé</h4>
                    <hr>
                    <p><strong>Fichier:</strong> <?= htmlspecialchars($candidature['cv_name']) ?></p>
                    <a href="<?= htmlspecialchars($candidature['cv_path']) ?>" 
                       target="_blank" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-download"></i> Télécharger le CV
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <!-- Statut et informations -->
                <div class="info-card">
                    <h4><i class="bi bi-info-circle me-2"></i>Statut de la candidature</h4>
                    <hr>
                    
                    <?php
                    $statusColor = match($candidature['status']) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'secondary'
                    };
                    $statusText = match($candidature['status']) {
                        'pending' => 'En attente',
                        'accepted' => 'Acceptée',
                        'rejected' => 'Refusée',
                        default => $candidature['status']
                    };
                    ?>
                    
                    <div class="text-center mb-3">
                        <span class="badge bg-<?= $statusColor ?> status-badge fs-6">
                            <?= $statusText ?>
                        </span>
                    </div>
                    
                    <p><strong>Date d'envoi:</strong><br>
                    <?= date('d/m/Y à H:i', strtotime($candidature['sent_at'])) ?></p>
                    
                    <?php if ($candidature['status'] === 'accepted'): ?>
                    <div class="alert alert-success mt-3">
                        <i class="bi bi-check-circle"></i>
                        <strong>Félicitations !</strong> Votre candidature a été acceptée.
                    </div>
                    <?php elseif ($candidature['status'] === 'rejected'): ?>
                    <div class="alert alert-danger mt-3">
                        <i class="bi bi-x-circle"></i>
                        <strong>Candidature refusée.</strong> Continuez vos recherches !
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Coordonnées entreprise -->
                <div class="info-card">
                    <h4><i class="bi bi-building me-2"></i>Coordonnées entreprise</h4>
                    <hr>
                    <p><strong>Nom:</strong> <?= htmlspecialchars($candidature['company_name']) ?></p>
                    
                    <?php if (!empty($candidature['company_email'])): ?>
                    <p><strong>Email:</strong> <?= htmlspecialchars($candidature['company_email']) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($candidature['company_phone'])): ?>
                    <p><strong>Téléphone:</strong> <?= htmlspecialchars($candidature['company_phone']) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($candidature['company_address'])): ?>
                    <p><strong>Adresse:</strong><br><?= htmlspecialchars($candidature['company_address']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="info-card">
                    <h4><i class="bi bi-lightning me-2"></i>Actions</h4>
                    <hr>
                    <div class="d-grid gap-2">
                        <a href="offers.php?id=<?= $candidature['offer_id'] ?>" class="btn btn-primary">
                            <i class="bi bi-eye"></i> Voir l'offre
                        </a>
                        <a href="mes_candidatures.php" class="btn btn-outline-secondary">
                            <i class="bi bi-list"></i> Mes candidatures
                        </a>
                        <a href="offers.php" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i> Chercher d'autres offres
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>