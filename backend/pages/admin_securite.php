<?php
// admin_securite.php - Sécurité administrateur
session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer les logs de connexion récents
$logsQuery = "SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 20";
try {
    $logsStmt = $db->prepare($logsQuery);
    $logsStmt->execute();
    $logs = $logsStmt->fetchAll();
} catch (Exception $e) {
    $logs = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sécurité • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(30, 41, 59, 0.2);
        }
        .security-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc3545;
        }
        .alert-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #ffc107;
        }
        .log-item {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .log-item:last-child {
            border-bottom: none;
        }
        .log-success {
            color: #198754;
        }
        .log-failed {
            color: #dc3545;
        }
        .security-score {
            font-size: 3rem;
            font-weight: 700;
            text-align: center;
            margin: 1rem 0;
        }
        .score-excellent { color: #198754; }
        .score-good { color: #0dcaf0; }
        .score-fair { color: #ffc107; }
        .score-poor { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="bi bi-shield-check"></i> Sécurité & Surveillance</h1>
                    <p class="mb-0">Surveillance de la sécurité de la plateforme</p>
                </div>
                <div>
                    <a href="dashboard_admin.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Score de sécurité -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="security-card">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <div class="security-score score-excellent">95%</div>
                            <div class="text-muted">Score de sécurité</div>
                        </div>
                        <div class="col-md-9">
                            <h5 class="mb-3">État du système</h5>
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: 95%"></div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Excellent</small>
                                <small class="text-muted">Aucune menace détectée</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Journal d'activité -->
                <div class="security-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0"><i class="bi bi-clock-history"></i> Journal des connexions</h4>
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i> Vider les logs
                        </button>
                    </div>
                    
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                            <div class="log-item">
                                <div class="d-flex justify-content-between">
                                    <span>
                                        <strong><?= htmlspecialchars($log['username'] ?? 'Utilisateur') ?></strong>
                                        - <?= htmlspecialchars($log['ip_address'] ?? 'IP inconnue') ?>
                                    </span>
                                    <span class="<?= ($log['success'] ?? 0) ? 'log-success' : 'log-failed' ?>">
                                        <?= ($log['success'] ?? 0) ? '✓ Succès' : '✗ Échec' ?>
                                    </span>
                                </div>
                                <div class="text-muted small">
                                    <?= isset($log['login_time']) ? date('d/m/Y H:i:s', strtotime($log['login_time'])) : 'Date inconnue' ?>
                                    <?php if (!empty($log['user_agent'])): ?>
                                        • <?= htmlspecialchars(substr($log['user_agent'], 0, 50)) ?>...
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-shield fs-1"></i>
                                <p class="mt-3">Aucun log de connexion disponible</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Alertes sécurité -->
                <div class="alert-card">
                    <h4 class="mb-4"><i class="bi bi-exclamation-triangle"></i> Alertes de sécurité</h4>
                    
                    <div class="alert alert-warning">
                        <div class="d-flex">
                            <i class="bi bi-shield-exclamation fs-4 me-3"></i>
                            <div>
                                <strong>Vérification recommandée</strong>
                                <p class="mb-0">Aucune alerte de sécurité active. Le système est sécurisé.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <i class="bi bi-info-circle fs-4 me-3"></i>
                            <div>
                                <strong>Dernière vérification</strong>
                                <p class="mb-0">Analyse de sécurité effectuée le <?= date('d/m/Y à H:i') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Actions rapides -->
                <div class="security-card">
                    <h4 class="mb-4"><i class="bi bi-lightning"></i> Actions de sécurité</h4>
                    
                    <div class="d-grid gap-2 mb-4">
                        <button class="btn btn-danger">
                            <i class="bi bi-shield-check"></i> Analyser le système
                        </button>
                        <button class="btn btn-warning">
                            <i class="bi bi-arrow-clockwise"></i> Régénérer les tokens
                        </button>
                        <button class="btn btn-secondary">
                            <i class="bi bi-key"></i> Changer clé API
                        </button>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="enable2FA" checked>
                        <label class="form-check-label" for="enable2FA">
                            Activer l'authentification à 2 facteurs
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="enableIPCheck" checked>
                        <label class="form-check-label" for="enableIPCheck">
                            Vérification d'IP
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enableBruteForce">
                        <label class="form-check-label" for="enableBruteForce">
                            Protection brute force
                        </label>
                    </div>
                </div>
                
                <!-- Informations système -->
                <div class="security-card">
                    <h4 class="mb-4"><i class="bi bi-info-circle"></i> Informations système</h4>
                    
                    <div class="mb-3">
                        <label class="form-label">Dernière mise à jour</label>
                        <input type="text" class="form-control" value="<?= date('d/m/Y H:i:s') ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Version système</label>
                        <input type="text" class="form-control" value="InternEasy v1.0.0" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dernière sauvegarde</label>
                        <input type="text" class="form-control" value="Hier à 02:00" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Prochaine analyse</label>
                        <input type="text" class="form-control" value="<?= date('d/m/Y', strtotime('+1 day')) ?> 02:00" readonly>
                    </div>
                </div>
                
                <!-- Rapports -->
                <div class="security-card">
                    <h4 class="mb-4"><i class="bi bi-file-earmark-text"></i> Rapports</h4>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-danger">
                            <i class="bi bi-download"></i> Télécharger rapport complet
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="bi bi-printer"></i> Imprimer le rapport
                        </button>
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-envelope"></i> Envoyer par email
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh des logs toutes les 30 secondes
        setInterval(() => {
            console.log('Actualisation des logs de sécurité...');
        }, 30000);
        
        // Confirmation pour actions critiques
        document.querySelectorAll('.btn-danger').forEach(button => {
            if (button.textContent.includes('Analyser') || button.textContent.includes('Vider')) {
                button.addEventListener('click', function(e) {
                    if (!confirm('Êtes-vous sûr de vouloir effectuer cette action ?')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>