<?php
// admin_parametres.php - Paramètres administrateur
session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_ultimate.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres Admin • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
        }
        .settings-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .settings-section {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .form-switch .form-check-input:checked {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="bi bi-gear"></i> Paramètres Administrateur</h1>
                    <p class="mb-0">Configuration de la plateforme InternEasy</p>
                </div>
                <div>
                    <a href="dashboard_admin.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Paramètres généraux -->
                <div class="settings-card">
                    <h4 class="mb-4"><i class="bi bi-sliders"></i> Paramètres généraux</h4>
                    
                    <div class="settings-section">
                        <h5>Configuration de la plateforme</h5>
                        <div class="mb-3">
                            <label class="form-label">Nom de la plateforme</label>
                            <input type="text" class="form-control" value="InternEasy" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email de contact</label>
                            <input type="email" class="form-control" value="contact@interneasy.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone support</label>
                            <input type="text" class="form-control" value="+212 5 XX XX XX XX">
                        </div>
                    </div>
                    
                    <div class="settings-section">
                        <h5>Fonctionnalités</h5>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="enableRegistration" checked>
                            <label class="form-check-label" for="enableRegistration">
                                Activer les nouvelles inscriptions
                            </label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="enableOffers" checked>
                            <label class="form-check-label" for="enableOffers">
                                Activer la publication d'offres
                            </label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="enableApplications" checked>
                            <label class="form-check-label" for="enableApplications">
                                Activer les candidatures
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="maintenanceMode">
                            <label class="form-check-label" for="maintenanceMode">
                                Mode maintenance
                            </label>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button class="btn btn-danger">
                            <i class="bi bi-save"></i> Enregistrer les modifications
                        </button>
                    </div>
                </div>
                
                <!-- Paramètres sécurité -->
                <div class="settings-card">
                    <h4 class="mb-4"><i class="bi bi-shield-check"></i> Paramètres de sécurité</h4>
                    
                    <div class="mb-3">
                        <label class="form-label">Force minimale des mots de passe</label>
                        <select class="form-select">
                            <option>Faible (6 caractères)</option>
                            <option selected>Moyenne (8 caractères)</option>
                            <option>Forte (12 caractères)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Durée de session (minutes)</label>
                        <input type="number" class="form-control" value="30" min="5" max="480">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tentatives de connexion max</label>
                        <input type="number" class="form-control" value="5" min="1" max="10">
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Compte administrateur -->
                <div class="settings-card">
                    <h4 class="mb-4"><i class="bi bi-person-badge"></i> Mon compte admin</h4>
                    
                    <div class="text-center mb-4">
                        <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                            <i class="bi bi-shield-check text-white fs-3"></i>
                        </div>
                        <h5 class="mt-3 mb-1"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur') ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($_SESSION['admin_email'] ?? 'admin@interneasy.com') ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Modifier l'email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_email'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" placeholder="Laisser vide pour ne pas changer">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" placeholder="Confirmer le nouveau mot de passe">
                    </div>
                    
                    <button class="btn btn-danger w-100">
                        <i class="bi bi-key"></i> Mettre à jour le compte
                    </button>
                </div>
                
                <!-- Actions système -->
                <div class="settings-card">
                    <h4 class="mb-4"><i class="bi bi-tools"></i> Actions système</h4>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-danger">
                            <i class="bi bi-database"></i> Sauvegarder la base de données
                        </button>
                        <button class="btn btn-outline-warning">
                            <i class="bi bi-arrow-clockwise"></i> Vider le cache
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="bi bi-file-text"></i> Voir les logs système
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>