<?php
session_start();

// Vérifier si étudiant connecté
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

// Traitement upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv_file'])) {
    $file = $_FILES['cv_file'];
    
    // Validation
    $allowedTypes = ['application/pdf', 'application/msword', 
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxSize = 5 * 1024 * 1024; // 5 Mo
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Erreur lors de l'upload: " . $file['error'];
    } elseif (!in_array($file['type'], $allowedTypes)) {
        $message = "Type de fichier non autorisé. Formats acceptés: PDF, DOC, DOCX";
    } elseif ($file['size'] > $maxSize) {
        $message = "Fichier trop volumineux (max 5 Mo)";
    } else {
        // Créer dossier uploads s'il n'existe pas
        $uploadDir = dirname(__DIR__, 2) . '/uploads/cvs/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Générer nom unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'cv_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // CHEMIN CORRECT POUR LA BASE DE DONNÉES
            $filePathInDB = '/interneasy/uploads/cvs/' . $fileName;
            
            // Insérer dans base de données
            $query = "INSERT INTO cvs (student_id, cv_name, file_path, file_type, file_size) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            // Rendre le premier CV uploadé par défaut
            $checkDefault = "SELECT COUNT(*) as count FROM cvs WHERE student_id = ?";
            $checkStmt = $db->prepare($checkDefault);
            $checkStmt->execute([$_SESSION['user_id']]);
            $hasCV = $checkStmt->fetch()['count'] > 0;
            
            // Utiliser le nom personnalisé si fourni, sinon le nom du fichier
            $cvName = $_POST['cv_name'] ?? $file['name'];
            $isDefault = $hasCV ? 0 : 1; // Premier CV = défaut
            
            $stmt->execute([
                $_SESSION['user_id'],
                htmlspecialchars($cvName),
                $filePathInDB,  // CHEMIN CORRECT
                $file['type'],
                $file['size']
            ]);
            
            // Si demandé, définir comme CV par défaut
            if (isset($_POST['set_default']) && $_POST['set_default']) {
                $lastId = $db->lastInsertId();
                // Mettre à jour tous les CVs de cet étudiant
                $updateDefault = "UPDATE cvs SET is_default = CASE 
                                  WHEN id = ? THEN 1 
                                  ELSE 0 
                                  END 
                                  WHERE student_id = ?";
                $updateStmt = $db->prepare($updateDefault);
                $updateStmt->execute([$lastId, $_SESSION['user_id']]);
            }
            
            $message = "✅ CV téléchargé avec succès !";
            $success = true;
            
            // Redirection si venant de apply_offer.php
            if (isset($_GET['redirect']) && $_GET['redirect'] === 'apply_offer' && isset($_GET['offer_id'])) {
                header('Location: apply_offer.php?offer_id=' . $_GET['offer_id'] . '&cv_uploaded=1');
                exit();
            }
        } else {
            $message = "❌ Erreur lors du déplacement du fichier";
        }
    }
}

// Récupérer les CVs existants
$cvsQuery = "SELECT * FROM cvs WHERE student_id = ? ORDER BY is_default DESC, created_at DESC";
$cvsStmt = $db->prepare($cvsQuery);
$cvsStmt->execute([$_SESSION['user_id']]);
$existingCVs = $cvsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon CV • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .upload-area {
            border: 3px dashed #0d6efd;
            border-radius: 10px;
            padding: 3rem;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover {
            background: #e9f2ff;
            border-color: #0a58ca;
        }
        .cv-card {
            border-left: 4px solid #0d6efd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .cv-card.default {
            border-left-color: #198754;
            background: #f8fff9;
        }
        .file-icon {
            font-size: 3rem;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Mon CV</h1>
                <p class="text-muted">Gérez vos CVs téléchargés</p>
            </div>
            <div>
                <a href="dashboard_student.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour au dashboard
                </a>
            </div>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
        <div class="alert alert-<?= $success ? 'success' : 'danger' ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Message de redirection depuis apply_offer.php -->
        <?php if (isset($_GET['cv_uploaded']) && $_GET['cv_uploaded'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show">
            ✅ CV téléchargé avec succès ! Vous pouvez maintenant postuler à l'offre.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Upload CV -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Télécharger un nouveau CV</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="cvForm">
                            <div class="upload-area" onclick="document.getElementById('cvFile').click()">
                                <i class="bi bi-file-earmark-pdf file-icon mb-3"></i>
                                <h5>Glissez-déposez votre CV ici</h5>
                                <p class="text-muted">ou cliquez pour sélectionner</p>
                                <p class="text-muted small">
                                    Formats acceptés: PDF, DOC, DOCX<br>
                                    Taille max: 5 Mo
                                </p>
                            </div>
                            <input type="file" name="cv_file" id="cvFile" 
                                   class="form-control mt-3 d-none" 
                                   accept=".pdf,.doc,.docx"
                                   onchange="previewFile()">
                            
                            <div class="mt-3" id="filePreview"></div>
                            
                            <div class="mt-3">
                                <label class="form-label">Nom du CV (optionnel)</label>
                                <input type="text" name="cv_name" class="form-control" 
                                       placeholder="Ex: CV_Stage_Informatique_2024"
                                       value="<?= isset($_POST['cv_name']) ? htmlspecialchars($_POST['cv_name']) : '' ?>">
                            </div>
                            
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="set_default" id="setDefault" checked>
                                <label class="form-check-label" for="setDefault">
                                    Définir comme CV par défaut
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mt-3">
                                <i class="bi bi-upload"></i> Télécharger le CV
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- CVs existants -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-files"></i> Mes CVs (<?= count($existingCVs) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($existingCVs)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-file-earmark-text display-1 text-muted"></i>
                                <p class="mt-3">Aucun CV téléchargé</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($existingCVs as $cv): ?>
                            <div class="cv-card <?= $cv['is_default'] ? 'default' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?= htmlspecialchars($cv['cv_name'] ?: basename($cv['file_path'])) ?>
                                            <?php if ($cv['is_default']): ?>
                                            <span class="badge bg-success ms-2">Défaut</span>
                                            <?php endif; ?>
                                        </h6>
                                        <p class="text-muted mb-1">
                                            <i class="bi bi-file-text"></i> <?= htmlspecialchars($cv['file_type']) ?><br>
                                            <i class="bi bi-hdd"></i> <?= round($cv['file_size'] / 1024, 1) ?> Ko<br>
                                            <i class="bi bi-calendar"></i> <?= date('d/m/Y H:i', strtotime($cv['created_at'])) ?>
                                        </p>
                                    </div>
                                    <div class="btn-group">
                                        <a href="<?= htmlspecialchars($cv['file_path']) ?>" 
                                           target="_blank" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-eye"></i> Voir
                                        </a>
                                        <a href="<?= htmlspecialchars($cv['file_path']) ?>" 
                                           download="<?= htmlspecialchars(basename($cv['file_path'])) ?>"
                                           class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="deleteCV(<?= $cv['id'] ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Aperçu fichier
        function previewFile() {
            const fileInput = document.getElementById('cvFile');
            const preview = document.getElementById('filePreview');
            
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                preview.innerHTML = `
                    <div class="alert alert-info">
                        <i class="bi bi-file-earmark"></i> <strong>${file.name}</strong><br>
                        Taille: ${(file.size / 1024).toFixed(1)} Ko<br>
                        Type: ${file.type}
                    </div>
                `;
            }
        }

        // Drag & drop
        const uploadArea = document.querySelector('.upload-area');
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.background = '#e9f2ff';
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.background = '#f8f9fa';
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.background = '#f8f9fa';
            const fileInput = document.getElementById('cvFile');
            fileInput.files = e.dataTransfer.files;
            previewFile();
        });

        // Supprimer CV
        function deleteCV(cvId) {
            if (confirm('Supprimer ce CV ? Cette action est irréversible.')) {
                fetch('delete_cv.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'cv_id=' + cvId
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data);
                    }
                });
            }
        }
    </script>
</body>
</html>