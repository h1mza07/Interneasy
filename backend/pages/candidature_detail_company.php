<?php
// candidature_detail_company.php - Détails d'une candidature (version entreprise)
session_start();

if (!isset($_SESSION['company_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer l'ID de la candidature
$application_id = $_GET['id'] ?? 0;
$company_id = $_SESSION['company_id'];

// Récupérer les détails de la candidature
$query = "SELECT a.*, 
                 s.first_name, 
                 s.last_name, 
                 s.email, 
                 s.phone,
                 s.address,
                 s.date_of_birth,
                 s.school,
                 s.education_level,
                 s.field_of_study,
                 s.graduation_date,
                 s.profile_score,
                 o.title as offer_title,
                 o.city as offer_city,
                 o.domain as offer_domain,
                 o.duration as offer_duration,
                 o.salary as offer_salary,
                 o.contract_type as offer_contract_type,
                 cv.cv_name,
                 cv.file_path as cv_path,
                 a.message,
                 a.sent_at,
                 a.status,
                 a.interview_date
          FROM applications a 
          JOIN students s ON a.student_id = s.id 
          JOIN offers o ON a.offer_id = o.id 
          LEFT JOIN cvs cv ON a.cv_id = cv.id
          WHERE a.id = ? AND o.company_id = ?";
          
$stmt = $db->prepare($query);
$stmt->execute([$application_id, $company_id]);
$candidature = $stmt->fetch();

if (!$candidature) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Candidature non trouvée ou vous n'avez pas accès.</div></div>";
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
        .btn-action {
            min-width: 120px;
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
                    <p class="mb-0">Candidat: <?= htmlspecialchars($candidature['first_name'] . ' ' . $candidature['last_name']) ?></p>
                    <p class="mb-0">Poste: <?= htmlspecialchars($candidature['offer_title']) ?></p>
                </div>
                <div>
                    <a href="gerer_candidatures.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Informations du candidat -->
                <div class="info-card">
                    <h4><i class="bi bi-person me-2"></i>Informations du candidat</h4>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nom complet:</strong> <?= htmlspecialchars($candidature['first_name'] . ' ' . $candidature['last_name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($candidature['email']) ?></p>
                            <p><strong>Téléphone:</strong> <?= htmlspecialchars($candidature['phone'] ?? 'Non spécifié') ?></p>
                            <?php if (!empty($candidature['date_of_birth'])): ?>
                            <p><strong>Date de naissance:</strong> <?= date('d/m/Y', strtotime($candidature['date_of_birth'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>École/Université:</strong> <?= htmlspecialchars($candidature['school'] ?? 'Non spécifié') ?></p>
                            <p><strong>Niveau d'éducation:</strong> <?= htmlspecialchars($candidature['education_level'] ?? 'Non spécifié') ?></p>
                            <p><strong>Domaine d'étude:</strong> <?= htmlspecialchars($candidature['field_of_study'] ?? 'Non spécifié') ?></p>
                            <?php if (!empty($candidature['graduation_date'])): ?>
                            <p><strong>Date de graduation:</strong> <?= date('d/m/Y', strtotime($candidature['graduation_date'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($candidature['address'])): ?>
                    <div class="mt-3">
                        <p><strong>Adresse:</strong><br>
                        <?= nl2br(htmlspecialchars($candidature['address'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($candidature['profile_score'])): ?>
                    <div class="mt-3">
                        <p><strong>Score de profil:</strong> 
                        <span class="badge bg-info"><?= $candidature['profile_score'] ?>/100</span></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Informations sur l'offre -->
                <div class="info-card">
                    <h4><i class="bi bi-briefcase me-2"></i>Informations sur l'offre</h4>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Poste:</strong> <?= htmlspecialchars($candidature['offer_title']) ?></p>
                            <p><strong>Lieu:</strong> <?= htmlspecialchars($candidature['offer_city']) ?></p>
                            <p><strong>Domaine:</strong> <?= htmlspecialchars($candidature['offer_domain']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Durée:</strong> <?= htmlspecialchars($candidature['offer_duration']) ?> mois</p>
                            <p><strong>Salaire:</strong> <?= htmlspecialchars($candidature['offer_salary']) ?></p>
                            <p><strong>Type de contrat:</strong> <?= htmlspecialchars($candidature['offer_contract_type']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Message de motivation -->
                <?php if (!empty($candidature['message'])): ?>
                <div class="info-card">
                    <h4><i class="bi bi-chat-text me-2"></i>Message de motivation</h4>
                    <hr>
                    <div style="white-space: pre-line;"><?= htmlspecialchars($candidature['message']) ?></div>
                </div>
                <?php endif; ?>

                <!-- CV -->
                <?php if (!empty($candidature['cv_path']) && file_exists($candidature['cv_path'])): ?>
                <div class="info-card">
                    <h4><i class="bi bi-file-earmark-pdf me-2"></i>CV du candidat</h4>
                    <hr>
                    <p><strong>Fichier:</strong> <?= htmlspecialchars($candidature['cv_name'] ?? 'CV') ?></p>
                    <a href="<?= htmlspecialchars($candidature['cv_path']) ?>" 
                       target="_blank" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-download"></i> Télécharger le CV
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <!-- Statut et actions -->
                <div class="info-card">
                    <h4><i class="bi bi-info-circle me-2"></i>Statut de la candidature</h4>
                    <hr>
                    
                    <?php
                    $statusColor = match($candidature['status']) {
                        'pending' => 'warning',
                        'reviewed' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'contacted' => 'primary',
                        default => 'secondary'
                    };
                    $statusText = match($candidature['status']) {
                        'pending' => 'En attente',
                        'reviewed' => 'En cours',
                        'accepted' => 'Acceptée',
                        'rejected' => 'Refusée',
                        'contacted' => 'Contactée',
                        default => $candidature['status']
                    };
                    ?>
                    
                    <div class="text-center mb-4">
                        <span class="badge bg-<?= $statusColor ?> status-badge fs-6">
                            <?= $statusText ?>
                        </span>
                    </div>
                    
                    <p><strong>Date d'envoi:</strong><br>
                    <?= date('d/m/Y à H:i', strtotime($candidature['sent_at'])) ?></p>
                    
                    <?php if (!empty($candidature['interview_date'])): ?>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-calendar-check"></i>
                        <strong>Entretien programmé:</strong><br>
                        <?= date('d/m/Y', strtotime($candidature['interview_date'])) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Actions rapides -->
                <div class="info-card">
                    <h4><i class="bi bi-lightning me-2"></i>Actions rapides</h4>
                    <hr>
                    <div class="d-grid gap-2">
                        <!-- Bouton Contacter -->
                        <button class="btn btn-success btn-action" onclick="contactCandidate(<?= $candidature['student_id'] ?>)">
                            <i class="bi bi-envelope"></i> Contacter
                        </button>
                        
                        <!-- Bouton Programmer entretien -->
                        <button class="btn btn-info btn-action" onclick="scheduleInterview(<?= $candidature['id'] ?>)">
                            <i class="bi bi-calendar-plus"></i> Entretien
                        </button>
                        
                        <!-- Formulaire Accepter -->
                        <form method="POST" action="update_application.php" class="d-grid">
                            <input type="hidden" name="application_id" value="<?= $candidature['id'] ?>">
                            <input type="hidden" name="status" value="accepted">
                            <button type="submit" class="btn btn-success btn-action">
                                <i class="bi bi-check-circle"></i> Accepter
                            </button>
                        </form>
                        
                        <!-- Formulaire Rejeter -->
                        <form method="POST" action="update_application.php" class="d-grid">
                            <input type="hidden" name="application_id" value="<?= $candidature['id'] ?>">
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="btn btn-danger btn-action">
                                <i class="bi bi-x-circle"></i> Rejeter
                            </button>
                        </form>
                        
                        <!-- Bouton Mettre en attente -->
                        <form method="POST" action="update_application.php" class="d-grid">
                            <input type="hidden" name="application_id" value="<?= $candidature['id'] ?>">
                            <input type="hidden" name="status" value="reviewed">
                            <button type="submit" class="btn btn-warning btn-action">
                                <i class="bi bi-clock"></i> En cours
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="info-card">
                    <h4><i class="bi bi-compass me-2"></i>Navigation</h4>
                    <hr>
                    <div class="d-grid gap-2">
                        <a href="gerer_candidatures.php" class="btn btn-outline-primary">
                            <i class="bi bi-list"></i> Toutes les candidatures
                        </a>
                        <a href="offer_detail.php?id=<?= $candidature['offer_id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-eye"></i> Voir l'offre
                        </a>
                        <a href="dashboard_company.php" class="btn btn-outline-secondary">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function contactCandidate(studentId) {
            alert('Fonctionnalité de contact à implémenter pour l\'étudiant ID: ' + studentId);
            // Ici vous pourriez ouvrir un modal ou rediriger vers une page de messagerie
        }
        
        function scheduleInterview(applicationId) {
            alert('Fonctionnalité de programmation d\'entretien à implémenter pour la candidature ID: ' + applicationId);
            // Ici vous pourriez ouvrir un modal pour choisir une date
        }
    </script>
</body>
</html>