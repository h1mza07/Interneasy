<?php
// offer_detail.php - Détails d'une offre (version entreprise)
session_start();

if (!isset($_SESSION['company_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer l'ID de l'offre
$offer_id = $_GET['id'] ?? 0;
$company_id = $_SESSION['company_id'];

// Récupérer les détails de l'offre
$query = "SELECT o.*, 
                 c.name as company_name,
                 c.email as company_email,
                 c.phone as company_phone,
                 c.address as company_address,
                 c.activity_sector,
                 COUNT(a.id) as application_count
          FROM offers o 
          JOIN companies c ON o.company_id = c.id
          LEFT JOIN applications a ON o.id = a.offer_id
          WHERE o.id = ? AND o.company_id = ?
          GROUP BY o.id";
          
$stmt = $db->prepare($query);
$stmt->execute([$offer_id, $company_id]);
$offer = $stmt->fetch();

if (!$offer) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Offre non trouvée ou vous n'avez pas accès.</div></div>";
    exit();
}

// Récupérer les candidatures pour cette offre
$applicationsQuery = "SELECT a.*, 
                             s.first_name, 
                             s.last_name, 
                             s.email, 
                             s.phone,
                             s.school,
                             s.field_of_study,
                             s.education_level,
                             cv.cv_name,
                             cv.file_path as cv_path
                      FROM applications a 
                      JOIN students s ON a.student_id = s.id
                      LEFT JOIN cvs cv ON a.cv_id = cv.id
                      WHERE a.offer_id = ? 
                      ORDER BY a.sent_at DESC";
$applicationsStmt = $db->prepare($applicationsQuery);
$applicationsStmt->execute([$offer_id]);
$applications = $applicationsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Offre • InternEasy</title>
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
        .application-card {
            border-left: 4px solid #0d6efd;
            transition: transform 0.2s;
        }
        .application-card:hover {
            transform: translateX(5px);
        }
        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-section">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">Détails de l'offre</h1>
                    <p class="mb-0"><?= htmlspecialchars($offer['title']) ?></p>
                </div>
                <div>
                    <a href="dashboard_company.php" class="btn btn-light">
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
                            <p><strong>Titre:</strong> <?= htmlspecialchars($offer['title']) ?></p>
                            <p><strong>Lieu:</strong> <?= htmlspecialchars($offer['city']) ?></p>
                            <p><strong>Domaine:</strong> <?= htmlspecialchars($offer['domain']) ?></p>
                            <p><strong>Type de contrat:</strong> <?= htmlspecialchars($offer['contract_type']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Durée:</strong> <?= htmlspecialchars($offer['duration']) ?> mois</p>
                            <p><strong>Salaire:</strong> <?= htmlspecialchars($offer['salary']) ?></p>
                            <p><strong>Postes disponibles:</strong> <?= htmlspecialchars($offer['positions']) ?></p>
                            <p><strong>Date de début:</strong> <?= !empty($offer['start_date']) ? date('d/m/Y', strtotime($offer['start_date'])) : 'Non spécifiée' ?></p>
                            <p><strong>Date limite:</strong> <?= !empty($offer['deadline']) ? date('d/m/Y', strtotime($offer['deadline'])) : 'Non spécifiée' ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($offer['description'])): ?>
                    <div class="mt-3">
                        <h5>Description du poste:</h5>
                        <div class="text-muted" style="white-space: pre-line;"><?= htmlspecialchars($offer['description']) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($offer['requirements'])): ?>
                    <div class="mt-3">
                        <h5>Exigences:</h5>
                        <div class="text-muted" style="white-space: pre-line;"><?= htmlspecialchars($offer['requirements']) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($offer['benefits'])): ?>
                    <div class="mt-3">
                        <h5>Avantages:</h5>
                        <div class="text-muted" style="white-space: pre-line;"><?= htmlspecialchars($offer['benefits']) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($offer['education_level'])): ?>
                    <div class="mt-3">
                        <h5>Niveau d'éducation requis:</h5>
                        <p class="text-muted"><?= htmlspecialchars($offer['education_level']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Statut de l'offre -->
                <div class="info-card">
                    <h4><i class="bi bi-info-circle me-2"></i>Statut de l'offre</h4>
                    <hr>
                    
                    <?php
                    $statusColor = match($offer['status']) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'closed' => 'secondary',
                        'expired' => 'danger',
                        default => 'secondary'
                    };
                    $statusText = match($offer['status']) {
                        'active' => 'Active',
                        'pending' => 'En attente',
                        'closed' => 'Fermée',
                        'expired' => 'Expirée',
                        default => $offer['status']
                    };
                    ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Statut:</strong><br>
                            <span class="badge bg-<?= $statusColor ?> status-badge"><?= $statusText ?></span></p>
                            
                            <p><strong>Date de création:</strong><br>
                            <?= date('d/m/Y', strtotime($offer['created_at'])) ?></p>
                            
                            <?php if (!empty($offer['latitude']) && !empty($offer['longitude'])): ?>
                            <p><strong>Localisation GPS:</strong><br>
                            <small class="text-muted">Lat: <?= $offer['latitude'] ?>, Long: <?= $offer['longitude'] ?></small></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Candidatures reçues:</strong><br>
                            <span class="fs-4 text-primary"><?= $offer['application_count'] ?></span></p>
                            
                            <?php if ($offer['status'] === 'active' && !empty($offer['deadline']) && strtotime($offer['deadline']) < time()): ?>
                            <div class="alert alert-warning mt-2">
                                <i class="bi bi-exclamation-triangle"></i>
                                Date limite dépassée
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Informations entreprise -->
                <div class="info-card">
                    <h4><i class="bi bi-building me-2"></i>Votre entreprise</h4>
                    <hr>
                    <p><strong>Nom:</strong> <?= htmlspecialchars($offer['company_name']) ?></p>
                    
                    <?php if (!empty($offer['company_email'])): ?>
                    <p><strong>Email:</strong> <?= htmlspecialchars($offer['company_email']) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($offer['company_phone'])): ?>
                    <p><strong>Téléphone:</strong> <?= htmlspecialchars($offer['company_phone']) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($offer['company_address'])): ?>
                    <p><strong>Adresse:</strong><br><?= htmlspecialchars($offer['company_address']) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($offer['activity_sector'])): ?>
                    <p><strong>Secteur d'activité:</strong> <?= htmlspecialchars($offer['activity_sector']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="info-card">
                    <h4><i class="bi bi-lightning me-2"></i>Actions</h4>
                    <hr>
                    <div class="d-grid gap-2">
                        <a href="post_offer.php?edit=<?= $offer['id'] ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Modifier l'offre
                        </a>
                        <a href="mes_offres.php" class="btn btn-outline-primary">
                            <i class="bi bi-list"></i> Mes offres
                        </a>
                        <a href="dashboard_company.php" class="btn btn-outline-secondary">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </div>
                </div>

                <!-- Statistiques rapides -->
                <div class="info-card">
                    <h4><i class="bi bi-graph-up me-2"></i>Statistiques</h4>
                    <hr>
                    <div class="text-center">
                        <div class="display-6 text-primary mb-2"><?= $offer['application_count'] ?></div>
                        <p class="text-muted">Candidatures reçues</p>
                        
                        <?php
                        // Calculer le taux de réponse
                        $respondedQuery = "SELECT COUNT(*) as count FROM applications WHERE offer_id = ? AND status IN ('accepted', 'rejected')";
                        $respondedStmt = $db->prepare($respondedQuery);
                        $respondedStmt->execute([$offer_id]);
                        $respondedCount = $respondedStmt->fetch()['count'];
                        
                        $responseRate = $offer['application_count'] > 0 ? round(($respondedCount / $offer['application_count']) * 100) : 0;
                        ?>
                        
                        <div class="mt-3">
                            <p><strong>Taux de réponse:</strong><br>
                            <span class="fs-5 text-success"><?= $responseRate ?>%</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Candidatures -->
        <div class="info-card mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="bi bi-people me-2"></i>Candidatures (<?= count($applications) ?>)</h4>
                <a href="gerer_candidatures.php?offer_id=<?= $offer['id'] ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-gear"></i> Gérer les candidatures
                </a>
            </div>
            
            <hr>
            
            <?php if (!empty($applications)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Candidat</th>
                                <th>École/Niveau</th>
                                <th>Domaine d'étude</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>CV</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                            <tr class="application-card">
                                <td>
                                    <strong><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($app['email']) ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($app['school'] ?? 'Non spécifié') ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($app['education_level'] ?? '') ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($app['field_of_study'] ?? 'Non spécifié') ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($app['sent_at'])) ?></td>
                                <td>
                                    <?php
                                    $appStatusColor = match($app['status']) {
                                        'pending' => 'warning',
                                        'reviewed' => 'info',
                                        'accepted' => 'success',
                                        'rejected' => 'danger',
                                        'contacted' => 'primary',
                                        default => 'secondary'
                                    };
                                    $appStatusText = match($app['status']) {
                                        'pending' => 'En attente',
                                        'reviewed' => 'En cours',
                                        'accepted' => 'Accepté',
                                        'rejected' => 'Rejeté',
                                        'contacted' => 'Contacté',
                                        default => $app['status']
                                    };
                                    ?>
                                    <span class="badge bg-<?= $appStatusColor ?>"><?= $appStatusText ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($app['cv_path'])): ?>
                                        <a href="<?= htmlspecialchars($app['cv_path']) ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> CV
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Aucun CV</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/interneasy/backend/pages/candidature_detail_company.php?id=<?= $app['id'] ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Voir
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-people display-1 text-muted"></i>
                    <h5 class="mt-3">Aucune candidature pour cette offre</h5>
                    <p class="text-muted">Les candidatures apparaîtront ici lorsqu'elles seront soumises.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Boutons de navigation -->
        <div class="d-flex justify-content-between mt-4 mb-5">
            <a href="dashboard_company.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour au tableau de bord
            </a>
            <div>
                <a href="post_offer.php?edit=<?= $offer['id'] ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
                <a href="mes_offres.php" class="btn btn-primary">
                    <i class="bi bi-list"></i> Mes offres
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirmation pour supprimer une offre
        function confirmDelete(offerId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette offre ? Cette action est irréversible.')) {
                window.location.href = 'delete_offer.php?id=' + offerId;
            }
        }
    </script>
</body>
</html>