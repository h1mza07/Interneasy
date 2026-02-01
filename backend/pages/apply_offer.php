<?php
// apply_offer.php - VERSION FINALE CORRIGÉE
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

// Vérifier si étudiant connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: /interneasy/frontend/login.html');
    exit();
}

$offer_id = isset($_GET['offer_id']) ? intval($_GET['offer_id']) : 0;

if (!$offer_id) {
    header('Location: offers.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Vérifier si l'offre existe
$offerQuery = "SELECT o.*, c.name as company_name FROM offers o 
               JOIN companies c ON o.company_id = c.id 
               WHERE o.id = ?";
$offerStmt = $db->prepare($offerQuery);
$offerStmt->execute([$offer_id]);
$offer = $offerStmt->fetch();

if (!$offer) {
    header('Location: offers.php?error=offer_not_found');
    exit();
}

// Vérifier si déjà postulé
$checkAppQuery = "SELECT id FROM applications WHERE student_id = ? AND offer_id = ?";
$checkAppStmt = $db->prepare($checkAppQuery);
$checkAppStmt->execute([$_SESSION['user_id'], $offer_id]);

if ($checkAppStmt->fetch()) {
    header('Location: offers.php?error=already_applied');
    exit();
}

// RÉCUPÉRER LES CVs DE L'ÉTUDIANT
$cvs = [];
$hasCVs = false;

try {
    $cvsQuery = "SELECT id, file_name, file_path FROM cvs WHERE student_id = ? ORDER BY created_at DESC";
    $cvsStmt = $db->prepare($cvsQuery);
    $cvsStmt->execute([$_SESSION['user_id']]);
    $cvs = $cvsStmt->fetchAll();
    $hasCVs = !empty($cvs);
} catch (Exception $e) {
    // Table cvs peut ne pas exister ou autre erreur
    $hasCVs = false;
}

// Traitement du formulaire
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $cv_id = isset($_POST['cv_id']) ? $_POST['cv_id'] : null;
    
    // Validation
    if (empty($message) || strlen($message) < 10) {
        $error = "Veuillez rédiger un message de motivation (minimum 10 caractères)";
    } else {
        try {
            // ADAPTER LA REQUÊTE SELON SI UN CV EST SÉLECTIONNÉ
            if ($cv_id && $cv_id !== 'none' && $cv_id !== 'new') {
                // Vérifier que le CV appartient à l'étudiant
                $cvCheckQuery = "SELECT file_path FROM cvs WHERE id = ? AND student_id = ?";
                $cvCheckStmt = $db->prepare($cvCheckQuery);
                $cvCheckStmt->execute([$cv_id, $_SESSION['user_id']]);
                $selectedCV = $cvCheckStmt->fetch();
                
                if ($selectedCV) {
                    // Avec CV
                    $insertQuery = "INSERT INTO applications (student_id, offer_id, message, cv_id, cv_url, sent_at, status) 
                                    VALUES (?, ?, ?, ?, ?, NOW(), 'pending')";
                    $insertStmt = $db->prepare($insertQuery);
                    $success = $insertStmt->execute([
                        $_SESSION['user_id'], 
                        $offer_id, 
                        $message,
                        $cv_id,
                        $selectedCV['file_path']
                    ]);
                } else {
                    $error = "CV sélectionné non valide";
                }
            } else {
                // Sans CV
                $insertQuery = "INSERT INTO applications (student_id, offer_id, message, sent_at, status) 
                                VALUES (?, ?, ?, NOW(), 'pending')";
                $insertStmt = $db->prepare($insertQuery);
                $success = $insertStmt->execute([
                    $_SESSION['user_id'], 
                    $offer_id, 
                    $message
                ]);
            }
            
            if ($success) {
                // Rediriger vers confirmation
                header('Location: offers.php?success=applied&app_id=' . $db->lastInsertId());
                exit();
            } else {
                $error = "Erreur lors de l'envoi de la candidature";
            }
            
        } catch (PDOException $e) {
            $error = "Erreur base de données: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postuler • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .apply-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .apply-header {
            background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 2rem;
        }
        .apply-body {
            padding: 2rem;
        }
        .cv-option {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .cv-option:hover {
            border-color: #2563eb;
            background: #f8fafc;
        }
        .cv-option.selected {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .btn-postuler {
            background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-postuler:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
    </style>
</head>
<body>
    <div class="apply-container">
        <div class="apply-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2">Postuler à l'offre</h2>
                    <p class="mb-0 opacity-90">Remplissez le formulaire pour envoyer votre candidature</p>
                </div>
                <a href="offers.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>
        
        <div class="apply-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Détails de l'offre -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?php echo htmlspecialchars($offer['title']); ?></h5>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p class="mb-2"><i class="bi bi-building me-2 text-muted"></i>
                                <strong>Entreprise:</strong> <?php echo htmlspecialchars($offer['company_name']); ?>
                            </p>
                            <p class="mb-2"><i class="bi bi-geo-alt me-2 text-muted"></i>
                                <strong>Lieu:</strong> <?php echo htmlspecialchars($offer['city']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><i class="bi bi-tags me-2 text-muted"></i>
                                <strong>Domaine:</strong> <?php echo htmlspecialchars($offer['domain']); ?>
                            </p>
                            <p class="mb-2"><i class="bi bi-calendar me-2 text-muted"></i>
                                <strong>Durée:</strong> <?php echo htmlspecialchars($offer['duration']); ?> mois
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulaire -->
            <form method="POST" id="applyForm">
                
                <!-- Sélection CV -->
                <?php if ($hasCVs): ?>
                <div class="mb-4">
                    <label class="form-label fw-bold mb-3">📄 Sélectionnez un CV (optionnel)</label>
                    <div id="cvSelection">
                        <div class="cv-option" onclick="selectCV('none')">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cv_id" 
                                       id="cvNone" value="none" checked>
                                <label class="form-check-label" for="cvNone">
                                    <strong>Postuler sans CV</strong><br>
                                    <small class="text-muted">Utiliser uniquement le message de motivation</small>
                                </label>
                            </div>
                        </div>
                        
                        <?php foreach ($cvs as $cv): ?>
                        <div class="cv-option" onclick="selectCV(<?php echo $cv['id']; ?>)">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cv_id" 
                                       id="cv<?php echo $cv['id']; ?>" value="<?php echo $cv['id']; ?>">
                                <label class="form-check-label" for="cv<?php echo $cv['id']; ?>">
                                    <strong><?php echo htmlspecialchars($cv['file_name'] ?: 'CV'); ?></strong><br>
                                    <small class="text-muted">
                                        <a href="<?php echo htmlspecialchars($cv['file_path']); ?>" 
                                           target="_blank" class="text-decoration-none">
                                            <i class="bi bi-eye me-1"></i>Prévisualiser
                                        </a>
                                    </small>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="cv-option" onclick="selectCV('new')">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cv_id" 
                                       id="cvNew" value="new">
                                <label class="form-check-label" for="cvNew">
                                    <strong><i class="bi bi-plus-circle me-2"></i>Télécharger un nouveau CV</strong><br>
                                    <small class="text-muted">Ajouter un CV à votre collection</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle fs-4 me-3"></i>
                        <div>
                            <h6 class="mb-1">Vous n'avez pas encore de CV</h6>
                            <p class="mb-0">Vous pouvez postuler avec un message de motivation uniquement, ou 
                                <a href="upload_cv.php" class="alert-link">télécharger un CV</a> d'abord.
                            </p>
                        </div>
                    </div>
                    <input type="hidden" name="cv_id" value="none">
                </div>
                <?php endif; ?>
                
                <!-- Message de motivation -->
                <div class="mb-4">
                    <label class="form-label fw-bold mb-3">💌 Message de motivation *</label>
                    <textarea name="message" class="form-control" rows="8" 
                              placeholder="Présentez-vous brièvement et expliquez pourquoi vous êtes intéressé par ce stage. 
Décrivez vos compétences pertinentes et ce que vous espérez apprendre.

Exemple: 'Je suis étudiant en... et je suis particulièrement intéressé par cette offre car...'" 
                              required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    <div class="mt-2 text-end">
                        <small class="text-muted" id="charCount">0 caractères (minimum 10)</small>
                    </div>
                </div>
                
                <!-- Boutons -->
                <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                    <a href="offers.php" class="btn btn-outline-secondary">
                        Annuler
                    </a>
                    <button type="submit" class="btn-postuler" id="submitBtn">
                        <i class="bi bi-send me-2"></i>Envoyer ma candidature
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Compteur de caractères
        const textarea = document.querySelector('textarea[name="message"]');
        const charCount = document.getElementById('charCount');
        
        textarea.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length + ' caractères (minimum 10)';
            
            if (length < 10) {
                charCount.style.color = '#dc2626';
            } else if (length < 100) {
                charCount.style.color = '#f59e0b';
            } else {
                charCount.style.color = '#10b981';
            }
        });
        
        // Sélection visuelle des CVs
        function selectCV(cvId) {
            // Retirer la classe selected
            document.querySelectorAll('.cv-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Ajouter selected à l'élément cliqué
            if (cvId === 'none') {
                document.getElementById('cvNone').closest('.cv-option').classList.add('selected');
                document.getElementById('cvNone').checked = true;
            } else if (cvId === 'new') {
                document.getElementById('cvNew').closest('.cv-option').classList.add('selected');
                document.getElementById('cvNew').checked = true;
                
                // Rediriger vers upload
                window.location.href = 'upload_cv.php?redirect=apply_offer&offer_id=<?php echo $offer_id; ?>';
            } else {
                const cvElement = document.getElementById('cv' + cvId);
                if (cvElement) {
                    cvElement.closest('.cv-option').classList.add('selected');
                    cvElement.checked = true;
                }
            }
        }
        
        // Initialiser la sélection
        document.addEventListener('DOMContentLoaded', function() {
            const checkedInput = document.querySelector('input[name="cv_id"]:checked');
            if (checkedInput) {
                checkedInput.closest('.cv-option').classList.add('selected');
            }
            
            // Déclencher le compteur initial
            textarea.dispatchEvent(new Event('input'));
        });
        
        // Validation du formulaire
        document.getElementById('applyForm').addEventListener('submit', function(e) {
            const message = textarea.value.trim();
            
            if (message.length < 10) {
                e.preventDefault();
                alert('Le message de motivation doit contenir au moins 10 caractères.');
                textarea.focus();
                return false;
            }
            
            // Désactiver le bouton pendant l'envoi
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi en cours...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>