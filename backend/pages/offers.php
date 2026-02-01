<?php
// offers.php - Page SIMPLE des offres AVEC RECHERCHE
session_start();

// Vérifier si connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: /interneasy/frontend/login.html');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer les paramètres de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$city = isset($_GET['city']) ? $_GET['city'] : '';
$domain = isset($_GET['domain']) ? $_GET['domain'] : '';
$duration = isset($_GET['duration']) ? $_GET['duration'] : '';

// Construire la requête SQL avec filtres
$query = "SELECT o.*, c.name as company_name 
          FROM offers o 
          JOIN companies c ON o.company_id = c.id 
          WHERE o.deadline >= CURDATE()";

$params = [];

// Ajouter les filtres de recherche
if (!empty($search)) {
    $query .= " AND (o.title LIKE ? OR o.description LIKE ? OR c.name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($city)) {
    $query .= " AND o.city = ?";
    $params[] = $city;
}

if (!empty($domain)) {
    $query .= " AND o.domain = ?";
    $params[] = $domain;
}

if (!empty($duration)) {
    if ($duration === '1-3') {
        $query .= " AND o.duration BETWEEN 1 AND 3";
    } elseif ($duration === '3-6') {
        $query .= " AND o.duration BETWEEN 3 AND 6";
    } elseif ($duration === '6+') {
        $query .= " AND o.duration >= 6";
    }
}

$query .= " ORDER BY o.created_at DESC";

// Exécuter la requête
$stmt = $db->prepare($query);
$stmt->execute($params);
$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier les candidatures de l'étudiant
$applications = [];
$appQuery = "SELECT offer_id FROM applications WHERE student_id = ?";
$appStmt = $db->prepare($appQuery);
$appStmt->execute([$_SESSION['user_id']]);
$applications = $appStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres de Stage • InternEasy</title>
    
    <!-- Même CSS que votre dashboard -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Mêmes couleurs et design que votre dashboard */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f8fafc;
            --border-radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        
        .header {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
            border-left: 5px solid var(--primary);
        }
        
        /* BARRE DE RECHERCHE */
        .search-bar {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }
        
        .search-btn {
            background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.2);
        }
        
        .reset-btn {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .reset-btn:hover {
            background: #e5e7eb;
            color: #374151;
        }
        
        .offer-card {
            background: white;
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .offer-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .offer-card-header {
            background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .offer-card-header h4 {
            margin: 0;
            font-weight: 600;
        }
        
        .company-badge {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
        }
        
        .badge-new {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-urgent {
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .btn-postuler {
            background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-postuler:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
            color: white;
        }
        
        .btn-postule {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: default;
        }
        
        .btn-back {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: var(--primary);
            color: white;
        }
        
        .offer-details {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .offer-details p {
            margin-bottom: 0.5rem;
            color: #4b5563;
        }
        
        .offer-details strong {
            color: #1f2937;
        }
        
        .no-offers {
            background: white;
            border-radius: var(--border-radius);
            padding: 3rem;
            text-align: center;
            box-shadow: var(--shadow-md);
        }
        
        .no-offers i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .search-info {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header {
                padding: 1.5rem;
            }
            
            .search-bar {
                padding: 1rem;
            }
            
            .offer-card-header {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête simple -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">📋 Offres de Stage</h1>
                    <p class="text-muted mb-0">Trouvez le stage qui correspond à votre profil</p>
                </div>
                <a href="dashboard_student.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i>
                    Retour au tableau de bord
                </a>
            </div>
        </div>
        
        <!-- BARRE DE RECHERCHE -->
        <div class="search-bar">
            <h5 class="mb-4">🔍 Rechercher un stage</h5>
            <form method="GET" action="" class="search-form">
                <div class="row g-3">
                    <!-- Recherche texte -->
                    <div class="col-lg-3 col-md-6">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Métier, compétence ou entreprise..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <!-- Ville -->
                    <div class="col-lg-2 col-md-6">
                        <select name="city" class="form-control">
                            <option value="">Toutes les villes</option>
                            <option value="Casablanca" <?php echo $city === 'Casablanca' ? 'selected' : ''; ?>>Casablanca</option>
                            <option value="Rabat" <?php echo $city === 'Rabat' ? 'selected' : ''; ?>>Rabat</option>
                            <option value="Marrakech" <?php echo $city === 'Marrakech' ? 'selected' : ''; ?>>Marrakech</option>
                            <option value="Agadir" <?php echo $city === 'Agadir' ? 'selected' : ''; ?>>Agadir</option>
                            <option value="Tanger" <?php echo $city === 'Tanger' ? 'selected' : ''; ?>>Tanger</option>
                            <option value="Fès" <?php echo $city === 'Fès' ? 'selected' : ''; ?>>Fès</option>
                            <option value="Meknès" <?php echo $city === 'Meknès' ? 'selected' : ''; ?>>Meknès</option>
                            <option value="Oujda" <?php echo $city === 'Oujda' ? 'selected' : ''; ?>>Oujda</option>
                            <option value="Beni Mellal" <?php echo $city === 'Beni Mellal' ? 'selected' : ''; ?>>Beni Mellal</option>
                            <option value="Tétouan" <?php echo $city === 'Tétouan' ? 'selected' : ''; ?>>Tétouan</option>
                        </select>
                    </div>
                    
                    <!-- Domaine -->
                    <div class="col-lg-2 col-md-6">
                        <select name="domain" class="form-control">
                            <option value="">Tous les domaines</option>
                            <option value="Tech & IT" <?php echo $domain === 'Tech & IT' ? 'selected' : ''; ?>>Tech & IT</option>
                            <option value="Marketing" <?php echo $domain === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                            <option value="Finance" <?php echo $domain === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                            <option value="Design" <?php echo $domain === 'Design' ? 'selected' : ''; ?>>Design</option>
                            <option value="Commerce" <?php echo $domain === 'Commerce' ? 'selected' : ''; ?>>Commerce</option>
                            <option value="Ingénierie" <?php echo $domain === 'Ingénierie' ? 'selected' : ''; ?>>Ingénierie</option>
                            <option value="Santé" <?php echo $domain === 'Santé' ? 'selected' : ''; ?>>Santé</option>
                            <option value="Éducation" <?php echo $domain === 'Éducation' ? 'selected' : ''; ?>>Éducation</option>
                            <option value="Logistique" <?php echo $domain === 'Logistique' ? 'selected' : ''; ?>>Logistique</option>
                            <option value="Tourisme" <?php echo $domain === 'Tourisme' ? 'selected' : ''; ?>>Tourisme</option>
                        </select>
                    </div>
                    
                    <!-- Durée -->
                    <div class="col-lg-2 col-md-6">
                        <select name="duration" class="form-control">
                            <option value="">Toutes durées</option>
                            <option value="1-3" <?php echo $duration === '1-3' ? 'selected' : ''; ?>>1-3 mois</option>
                            <option value="3-6" <?php echo $duration === '3-6' ? 'selected' : ''; ?>>3-6 mois</option>
                            <option value="6+" <?php echo $duration === '6+' ? 'selected' : ''; ?>>6+ mois</option>
                        </select>
                    </div>
                    
                    <!-- Boutons -->
                    <div class="col-lg-3 col-md-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="search-btn flex-grow-1">
                                <i class="bi bi-search me-2"></i>
                                Rechercher
                            </button>
                            <?php if (!empty($search) || !empty($city) || !empty($domain) || !empty($duration)): ?>
                            <a href="offers.php" class="reset-btn">
                                <i class="bi bi-x-circle"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Info sur la recherche actuelle -->
            <?php if (!empty($search) || !empty($city) || !empty($domain) || !empty($duration)): ?>
            <div class="search-info mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-primary">
                            <i class="bi bi-funnel me-1"></i>
                            Filtres actifs : 
                            <?php 
                            $filters = [];
                            if (!empty($search)) $filters[] = "Recherche: \"$search\"";
                            if (!empty($city)) $filters[] = "Ville: $city";
                            if (!empty($domain)) $filters[] = "Domaine: $domain";
                            if (!empty($duration)) $filters[] = "Durée: " . ($duration === '1-3' ? '1-3 mois' : ($duration === '3-6' ? '3-6 mois' : '6+ mois'));
                            echo implode(' • ', $filters);
                            ?>
                        </small>
                    </div>
                    <a href="offers.php" class="btn btn-sm btn-outline-primary">
                        Réinitialiser
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Statistique simple -->
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle me-2"></i>
                <span>
                    <strong><?php echo count($offers); ?> offres</strong> trouvées • 
                    Vous avez postulé à <strong><?php echo count($applications); ?> offres</strong>
                    <?php if (!empty($search) || !empty($city) || !empty($domain) || !empty($duration)): ?>
                     • <em>Filtres appliqués</em>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <!-- Liste des offres -->
        <?php if (empty($offers)): ?>
            <div class="no-offers">
                <i class="bi bi-search"></i>
                <h3 class="mb-3">Aucune offre trouvée</h3>
                <p class="text-muted mb-4">
                    <?php if (!empty($search) || !empty($city) || !empty($domain) || !empty($duration)): ?>
                        Aucune offre ne correspond à vos critères de recherche.
                        <br>
                        <a href="offers.php" class="text-primary">Afficher toutes les offres</a>
                    <?php else: ?>
                        Il n'y a actuellement aucune offre de stage disponible.
                    <?php endif; ?>
                </p>
                <a href="dashboard_student.php" class="btn-postuler">
                    Retour au tableau de bord
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($offers as $offer): 
                    $hasApplied = in_array($offer['id'], $applications);
                    $isNew = (strtotime($offer['created_at']) > strtotime('-7 days'));
                    $isUrgent = (strtotime($offer['deadline']) < strtotime('+7 days'));
                ?>
                <div class="col-lg-6 mb-4">
                    <div class="offer-card">
                        <div class="offer-card-header">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <?php if ($isNew): ?>
                                        <span class="badge-new me-2">NOUVEAU</span>
                                    <?php endif; ?>
                                    <?php if ($isUrgent): ?>
                                        <span class="badge-urgent">URGENT</span>
                                    <?php endif; ?>
                                </div>
                                <i class="bi bi-bookmark fs-4 text-white-50"></i>
                            </div>
                            <h4 class="text-white"><?php echo htmlspecialchars($offer['title']); ?></h4>
                            <p class="text-white-50 mb-0 mt-2">
                                <span class="company-badge"><?php echo htmlspecialchars($offer['company_name']); ?></span>
                                • <?php echo htmlspecialchars($offer['city']); ?>
                            </p>
                        </div>
                        
                        <div class="card-body">
                            <div class="offer-details">
                                <p><strong>📍 Lieu :</strong> <?php echo htmlspecialchars($offer['city']); ?></p>
                                <p><strong>🏢 Entreprise :</strong> <?php echo htmlspecialchars($offer['company_name']); ?></p>
                                <p><strong>🎯 Domaine :</strong> <?php echo htmlspecialchars($offer['domain']); ?></p>
                                <p><strong>⏱️ Durée :</strong> <?php echo htmlspecialchars($offer['duration']); ?> mois</p>
                                <p><strong>💰 Salaire :</strong> <?php echo htmlspecialchars($offer['salary']); ?> MAD</p>
                                <p><strong>📝 Description :</strong> <?php echo nl2br(htmlspecialchars(substr($offer['description'], 0, 200))); ?>...</p>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">
                                    Publiée le <?php echo date('d/m/Y', strtotime($offer['created_at'])); ?><br>
                                    Date limite : <?php echo date('d/m/Y', strtotime($offer['deadline'])); ?>
                                </small>
                                
                                <div>
                                    <?php if ($hasApplied): ?>
                                        <button class="btn-postule" disabled>
                                            <i class="bi bi-check-circle me-2"></i>
                                            Déjà postulé
                                        </button>
                                    <?php else: ?>
                                        <a href="apply_offer.php?offer_id=<?php echo $offer['id']; ?>" 
                                           class="btn-postuler">
                                            <i class="bi bi-send me-2"></i>
                                            Postuler
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Pied de page simple -->
        <div class="text-center mt-5 pt-4 border-top">
            <p class="text-muted">
                <i class="bi bi-shield-check text-primary me-1"></i>
                InternEasy • Plateforme de stages • 
                <a href="dashboard_student.php" class="text-decoration-none text-primary">Tableau de bord</a> • 
                <a href="logout.php" class="text-decoration-none text-danger">Déconnexion</a>
            </p>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Effet de survol sur les cartes
        document.querySelectorAll('.offer-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
        
        // Confirmation avant de postuler
        document.querySelectorAll('.btn-postuler').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir postuler à cette offre ?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Auto-submit quand on change les select (optionnel)
        document.querySelectorAll('select[name="city"], select[name="domain"], select[name="duration"]').forEach(select => {
            select.addEventListener('change', function() {
                // Si recherche vide et on change un filtre, soumettre
                if (document.querySelector('input[name="search"]').value === '') {
                    this.form.submit();
                }
            });
        });
        
        // Mettre le focus sur le champ recherche
        document.querySelector('input[name="search"]')?.focus();
    </script>
</body>
</html>