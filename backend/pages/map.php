<?php
// map.php - Carte interactive des entreprises
session_start();

// Vérifier si utilisateur connecté (étudiant OU entreprise)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['company_id'])) {
    header('Location: ../login_ultimate.php');
    exit();
}

// Déterminer le type d'utilisateur
if (isset($_SESSION['company_id'])) {
    $_SESSION['user_id'] = $_SESSION['company_id'];
    $_SESSION['user_type'] = 'company';
}
include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer toutes les entreprises avec localisation
$query = "SELECT id, name, email, phone, address, city, 
                 activity_sector, company_size, website,
                 latitude, longitude
          FROM companies 
          WHERE latitude IS NOT NULL 
          AND longitude IS NOT NULL
          ORDER BY name";
$stmt = $db->query($query);
$companies = $stmt->fetchAll();

// Récupérer les IDs des entreprises avec offres actives (UNIQUEMENT pour les étudiants)
$companiesWithActiveOffers = [];
if ($_SESSION['user_type'] === 'student') {
    $activeOffersQuery = "SELECT DISTINCT c.id 
                          FROM companies c 
                          JOIN offers o ON c.id = o.company_id 
                          WHERE o.status = 'active' 
                          AND o.deadline >= CURDATE()
                          AND c.latitude IS NOT NULL 
                          AND c.longitude IS NOT NULL";
    $activeStmt = $db->query($activeOffersQuery);
    $companiesWithActiveOffers = $activeStmt->fetchAll(PDO::FETCH_COLUMN);
}

// Convertir en JSON pour JavaScript
$companiesJson = json_encode($companies);
$activeOffersJson = json_encode($companiesWithActiveOffers);

// Déterminer le centre de la carte (Maroc par défaut)
$defaultLat = 31.7917;  // Latitude Casablanca
$defaultLng = -7.0926;  // Longitude Casablanca

// Si l'utilisateur est une entreprise avec localisation, centrer sur sa position
if ($_SESSION['user_type'] === 'company') {
    $userCompanyQuery = "SELECT latitude, longitude FROM companies WHERE id = ?";
    $userCompanyStmt = $db->prepare($userCompanyQuery);
    $userCompanyStmt->execute([$_SESSION['user_id']]);
    $userCompany = $userCompanyStmt->fetch();
    
    if ($userCompany && $userCompany['latitude'] && $userCompany['longitude']) {
        $defaultLat = $userCompany['latitude'];
        $defaultLng = $userCompany['longitude'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte des Entreprises • InternEasy</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        #map {
            height: 600px;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            margin-bottom: 2rem;
        }
        .map-container {
            position: relative;
        }
        .map-sidebar {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 600px;
            overflow-y: auto;
        }
        .company-card {
            border-left: 4px solid #0d6efd;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .company-card:hover {
            background: #e3f2fd;
            transform: translateX(5px);
        }
        .company-card.active {
            background: #0d6efd;
            color: white;
        }
        .sector-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .legend {
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            margin-top: 10px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <?php if ($_SESSION['user_type'] === 'student'): ?>
                                <a href="dashboard_student.php">Dashboard</a>
                            <?php else: ?>
                                <a href="dashboard_company.php">Dashboard</a>
                            <?php endif; ?>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Carte des entreprises</li>
                    </ol>
                </nav>
                
                <h1 class="mb-3">
                    <i class="bi bi-geo-alt"></i> Carte des entreprises
                </h1>
                <p class="lead text-muted">
                    <?php if ($_SESSION['user_type'] === 'student'): ?>
                        Trouvez des entreprises près de chez vous pour vos stages
                    <?php else: ?>
                        Visualisez la localisation des autres entreprises
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="row">
            <!-- Carte -->
            <div class="col-lg-8 mb-4">
                <div class="map-container">
                    <div id="map"></div>
                    
                    <!-- Contrôles de la carte -->
                    <div class="map-controls">
                        <div class="btn-group-vertical">
                            <button class="btn btn-sm btn-outline-primary" id="zoomIn">
                                <i class="bi bi-plus"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" id="zoomOut">
                                <i class="bi bi-dash"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" id="locateMe">
                                <i class="bi bi-geo"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" id="resetView">
                                <i class="bi bi-compass"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Légende -->
                    <div class="legend">
                        <h6><i class="bi bi-key"></i> Légende</h6>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #0d6efd;"></div>
                            <span>Entreprises</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #198754;"></div>
                            <span>Votre position</span>
                        </div>
                        <?php if ($_SESSION['user_type'] === 'student'): ?>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #ffc107;"></div>
                            <span>Entreprises avec offres actives</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar avec liste des entreprises -->
            <div class="col-lg-4">
                <div class="map-sidebar">
                    <h5 class="mb-3">
                        <i class="bi bi-building"></i> 
                        Entreprises (<?= count($companies) ?>)
                    </h5>
                    
                    <!-- Filtres -->
                    <div class="mb-3">
                        <input type="text" id="searchCompany" class="form-control" 
                               placeholder="Rechercher une entreprise...">
                    </div>
                    
                    <div class="mb-3">
                        <select id="filterSector" class="form-select">
                            <option value="">Tous les secteurs</option>
                            <option value="Informatique / Tech">Informatique / Tech</option>
                            <option value="Finance / Banque">Finance / Banque</option>
                            <option value="Marketing / Communication">Marketing / Communication</option>
                            <option value="Commerce / Distribution">Commerce / Distribution</option>
                            <option value="Industrie">Industrie</option>
                            <option value="Santé">Santé</option>
                            <option value="Éducation">Éducation</option>
                            <option value="Tourisme">Tourisme</option>
                        </select>
                    </div>
                    
                    <!-- Liste des entreprises -->
                    <div id="companiesList">
                        <?php if (empty($companies)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                Aucune entreprise n'a encore renseigné sa localisation.
                                <?php if ($_SESSION['user_type'] === 'company'): ?>
                                    <a href="mon_profil_entreprise.php" class="alert-link">
                                        Ajoutez votre position dans votre profil
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($companies as $company): ?>
                            <div class="company-card" 
                                 data-id="<?= $company['id'] ?>"
                                 data-lat="<?= $company['latitude'] ?>"
                                 data-lng="<?= $company['longitude'] ?>"
                                 data-sector="<?= htmlspecialchars($company['activity_sector'] ?? '') ?>">
                                <h6 class="mb-1"><?= htmlspecialchars($company['name']) ?></h6>
                                <?php if (!empty($company['activity_sector'])): ?>
                                    <span class="sector-badge mb-2 d-inline-block">
                                        <?= htmlspecialchars($company['activity_sector']) ?>
                                    </span>
                                <?php endif; ?>
                                <p class="mb-1 text-muted">
                                    <i class="bi bi-geo-alt"></i> 
                                    <?= htmlspecialchars($company['city'] ?? 'Non spécifié') ?>
                                </p>
                                <p class="mb-1 text-muted">
                                    <i class="bi bi-people"></i> 
                                    <?= htmlspecialchars($company['company_size'] ?? 'Taille non précisée') ?>
                                </p>
                                <?php if ($_SESSION['user_type'] === 'student'): ?>
                                <a href="offers.php?company=<?= $company['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary mt-2">
                                    Voir les offres
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-building"></i> Entreprises localisées
                        </h5>
                        <h2 class="text-primary"><?= count($companies) ?></h2>
                        <p class="card-text">sur la carte</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-geo"></i> Couverture
                        </h5>
                        <h2 class="text-success">
                            <?php 
                            // Compter les villes uniques
                            $cities = array_unique(array_column($companies, 'city'));
                            echo count($cities);
                            ?>
                        </h2>
                        <p class="card-text">villes différentes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-briefcase"></i> Secteurs
                        </h5>
                        <h2 class="text-warning">
                            <?php 
                            // Compter les secteurs uniques
                            $sectors = array_filter(array_unique(array_column($companies, 'activity_sector')));
                            echo count($sectors);
                            ?>
                        </h2>
                        <p class="card-text">secteurs représentés</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Données PHP vers JavaScript
        const companies = <?= $companiesJson ?>;
        const companiesWithActiveOffers = <?= $activeOffersJson ?>;
        const defaultLat = <?= $defaultLat ?>;
        const defaultLng = <?= $defaultLng ?>;
        const isStudent = <?= $_SESSION['user_type'] === 'student' ? 'true' : 'false' ?>;
        const isCompany = <?= $_SESSION['user_type'] === 'company' ? 'true' : 'false' ?>;
        const currentUserId = <?= $_SESSION['user_id'] ?>;
        
        // Initialiser la carte
        const map = L.map('map').setView([defaultLat, defaultLng], 10);
        
        // Ajouter les tuiles OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        
        // Marqueurs et popups
        const markers = [];
        const popups = [];
        
        // Fonction pour créer un marqueur coloré
        function getMarkerColor(companyId) {
            if (isCompany && companyId === currentUserId) {
                return '#198754'; // Vert pour votre entreprise
            } else if (isStudent && companiesWithActiveOffers.includes(companyId)) {
                return '#ffc107'; // Jaune pour entreprises avec offres actives
            }
            return '#0d6efd'; // Bleu par défaut
        }
        
        // Fonction pour créer une icône personnalisée
        function createCustomIcon(color) {
            return L.divIcon({
                html: `<div style="
                    background-color: ${color};
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    border: 3px solid white;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: bold;
                "></div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                popupAnchor: [0, -15]
            });
        }
        
        // Ajouter les marqueurs pour chaque entreprise
        companies.forEach((company, index) => {
            if (company.latitude && company.longitude) {
                const markerColor = getMarkerColor(company.id);
                const icon = createCustomIcon(markerColor);
                
                const marker = L.marker([company.latitude, company.longitude], { icon: icon })
                    .addTo(map);
                
                // Popup avec informations
                const popupContent = `
                    <div style="min-width: 200px;">
                        <h5 style="margin: 0 0 10px 0;">${company.name}</h5>
                        ${company.activity_sector ? `<p><strong>Secteur:</strong> ${company.activity_sector}</p>` : ''}
                        ${company.city ? `<p><strong>Ville:</strong> ${company.city}</p>` : ''}
                        ${company.company_size ? `<p><strong>Taille:</strong> ${company.company_size}</p>` : ''}
                        ${company.website ? `<p><strong>Site:</strong> <a href="${company.website}" target="_blank">${company.website}</a></p>` : ''}
                        <div style="margin-top: 10px;">
                            ${isStudent ? `<a href="offers.php?company=${company.id}" class="btn btn-sm btn-primary">Voir les offres</a>` : ''}
                            ${isCompany && company.id === currentUserId ? `<a href="mon_profil_entreprise.php" class="btn btn-sm btn-warning">Modifier ma position</a>` : ''}
                        </div>
                    </div>
                `;
                
                marker.bindPopup(popupContent);
                
                // Stocker les références
                markers.push(marker);
                popups.push(marker.getPopup());
                
                // Événement clic sur le marqueur
                marker.on('click', function() {
                    // Mettre en surbrillance la carte correspondante
                    document.querySelectorAll('.company-card').forEach(card => {
                        card.classList.remove('active');
                        if (parseInt(card.dataset.id) === company.id) {
                            card.classList.add('active');
                            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });
                });
                
                // Événement clic sur la carte dans la sidebar
                document.querySelectorAll('.company-card').forEach(card => {
                    if (parseInt(card.dataset.id) === company.id) {
                        card.addEventListener('click', function() {
                            // Centrer la carte sur cette entreprise
                            map.setView([company.latitude, company.longitude], 15);
                            
                            // Ouvrir le popup
                            marker.openPopup();
                            
                            // Mettre en surbrillance
                            document.querySelectorAll('.company-card').forEach(c => {
                                c.classList.remove('active');
                            });
                            this.classList.add('active');
                        });
                    }
                });
            }
        });
        
        // Ajouter un marqueur pour la position actuelle (si autorisée)
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                
                L.marker([userLat, userLng], {
                    icon: L.divIcon({
                        html: '<div style="background-color: #198754; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white;"></div>',
                        iconSize: [20, 20]
                    })
                })
                .addTo(map)
                .bindPopup('<b>Votre position actuelle</b>')
                .openPopup();
            });
        }
        
        // Contrôles de la carte
        document.getElementById('zoomIn').addEventListener('click', () => {
            map.zoomIn();
        });
        
        document.getElementById('zoomOut').addEventListener('click', () => {
            map.zoomOut();
        });
        
        document.getElementById('locateMe').addEventListener('click', () => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    map.setView([position.coords.latitude, position.coords.longitude], 15);
                });
            } else {
                alert("La géolocalisation n'est pas supportée par votre navigateur.");
            }
        });
        
        document.getElementById('resetView').addEventListener('click', () => {
            map.setView([defaultLat, defaultLng], 10);
        });
        
        // Filtrage des entreprises
        document.getElementById('searchCompany').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            document.querySelectorAll('.company-card').forEach(card => {
                const companyName = card.querySelector('h6').textContent.toLowerCase();
                const shouldShow = companyName.includes(searchTerm);
                card.style.display = shouldShow ? 'block' : 'none';
                
                // Cacher/montrer les marqueurs correspondants
                const companyId = parseInt(card.dataset.id);
                markers.forEach((marker, index) => {
                    if (companies[index].id === companyId) {
                        if (shouldShow) {
                            map.addLayer(marker);
                        } else {
                            map.removeLayer(marker);
                        }
                    }
                });
            });
        });
        
        document.getElementById('filterSector').addEventListener('change', function(e) {
            const selectedSector = e.target.value;
            
            document.querySelectorAll('.company-card').forEach(card => {
                const companySector = card.dataset.sector;
                const shouldShow = !selectedSector || companySector === selectedSector;
                card.style.display = shouldShow ? 'block' : 'none';
                
                // Cacher/montrer les marqueurs correspondants
                const companyId = parseInt(card.dataset.id);
                markers.forEach((marker, index) => {
                    if (companies[index].id === companyId) {
                        if (shouldShow) {
                            map.addLayer(marker);
                        } else {
                            map.removeLayer(marker);
                        }
                    }
                });
            });
        });
        
        // Clusterisation optionnelle (pour beaucoup de marqueurs)
        // Pour l'activer, incluez: <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
        
        console.log('Carte initialisée avec', companies.length, 'entreprises');
        console.log('Entreprises avec offres actives:', companiesWithActiveOffers);
    </script>
</body>
</html>