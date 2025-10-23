<!-- VIEW: application/views/procedures/bs_attachments.php -->
<?php
/**
 * Vue gestion des fichiers attachés d'une procédure
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('procedures');
?>

<div id="body" class="body ui-widget-content">
    <div class="container-fluid">
        
        <!-- Header -->
        <div class="row mb-3">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="<?= site_url('procedures') ?>">Procédures</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="<?= site_url("procedures/view/{$procedure['id']}") ?>"><?= htmlspecialchars($procedure['title']) ?></a>
                        </li>
                        <li class="breadcrumb-item active">Fichiers</li>
                    </ol>
                </nav>
                <h2>
                    <i class="fas fa-paperclip"></i>
                    Fichiers - <?= htmlspecialchars($procedure['title']) ?>
                </h2>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= site_url("procedures/view/{$procedure['id']}") ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la procédure
                </a>
            </div>
        </div>

        <!-- Messages flash -->
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $this->session->flashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $this->session->flashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Upload de fichiers -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cloud-upload-alt"></i> Ajouter des fichiers
                        </h5>
                    </div>
                    <div class="card-body">
                        <?= form_open_multipart("procedures/upload_file/{$procedure['id']}", array('id' => 'uploadForm')) ?>
                        
                        <div class="mb-3">
                            <label for="file" class="form-label">Sélectionner un fichier</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="file" 
                                   name="file" 
                                   accept=".pdf,.doc,.docx,.txt,.md,.jpg,.jpeg,.png,.gif,.svg"
                                   required>
                            <div class="form-text">
                                <strong>Types acceptés:</strong><br>
                                Documents: PDF, DOC, DOCX, TXT, MD<br>
                                Images: JPG, PNG, GIF, SVG<br>
                                <strong>Taille max:</strong> 20 MB
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <i class="fas fa-upload"></i> Télécharger
                        </button>
                        
                        <?= form_close() ?>
                        
                        <!-- Zone de drag & drop -->
                        <hr>
                        <div id="dropZone" class="border border-dashed border-2 rounded p-4 text-center text-muted">
                            <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i><br>
                            <small>
                                Ou glissez-déposez vos fichiers ici<br>
                                <em>(fonctionnalité à implémenter)</em>
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Informations -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> Conseils
                        </h5>
                    </div>
                    <div class="card-body">
                        <small>
                            <strong>Images dans le markdown:</strong><br>
                            Après upload, référencez vos images avec:<br>
                            <code>![Description](nom_fichier.jpg)</code>
                            
                            <hr>
                            
                            <strong>Liens vers documents:</strong><br>
                            <code>[Voir le PDF](nom_fichier.pdf)</code>
                            
                            <hr>
                            
                            <strong>Organisation:</strong><br>
                            • Utilisez des noms de fichiers explicites<br>
                            • Préférez les images optimisées (JPG/PNG)<br>
                            • Les miniatures sont générées automatiquement
                        </small>
                    </div>
                </div>
            </div>

            <!-- Liste des fichiers existants -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-folder"></i> 
                            Fichiers existants (<?= count($files) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($files)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun fichier</h5>
                                <p class="text-muted">Cette procédure n'a pas encore de fichiers attachés.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($files as $file): ?>
                                    <div class="col-md-6 col-xl-4 mb-3">
                                        <div class="card h-100 file-card">
                                            <!-- Prévisualisation -->
                                            <div class="card-img-top position-relative" style="height: 150px; overflow: hidden;">
                                                <?php if ($file['is_image'] && isset($file['thumbnail'])): ?>
                                                    <img src="<?= base_url() ?>uploads/<?= $file['thumbnail'] ?>"
                                                         class="w-100 h-100"
                                                         style="object-fit: cover;"
                                                         alt="<?= htmlspecialchars($file['name']) ?>">
                                                <?php else: ?>
                                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                                        <?php
                                                        $ext = strtolower($file['extension']);
                                                        $icon_class = 'fas fa-file text-muted';
                                                        if ($ext === 'pdf') $icon_class = 'fas fa-file-pdf text-danger';
                                                        elseif (in_array($ext, ['doc', 'docx'])) $icon_class = 'fas fa-file-word text-primary';
                                                        elseif (in_array($ext, ['txt', 'md'])) $icon_class = 'fas fa-file-alt text-secondary';
                                                        ?>
                                                        <i class="<?= $icon_class ?> fa-3x"></i>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Badge type fichier -->
                                                <div class="position-absolute top-0 end-0 m-2">
                                                    <span class="badge bg-dark bg-opacity-75">
                                                        <?= strtoupper($file['extension']) ?>
                                                    </span>
                                                </div>
                                                
                                                <!-- Badge fichier markdown principal -->
                                                <?php if ($file['name'] === "procedure_{$procedure['name']}.md"): ?>
                                                    <div class="position-absolute top-0 start-0 m-2">
                                                        <span class="badge bg-primary">
                                                            <i class="fab fa-markdown"></i> Principal
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Informations fichier -->
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-2" title="<?= htmlspecialchars($file['name']) ?>">
                                                    <?= htmlspecialchars(strlen($file['name']) > 30 ? substr($file['name'], 0, 30) . '...' : $file['name']) ?>
                                                </h6>
                                                <p class="card-text small text-muted mb-2">
                                                    <i class="fas fa-weight-hanging"></i> <?= $file['size_human'] ?><br>
                                                    <i class="fas fa-clock"></i> <?= $file['modified_human'] ?>
                                                </p>
                                                
                                                <!-- Actions -->
                                                <div class="btn-group btn-group-sm w-100" role="group">
                                                    <a href="<?= site_url("procedures/download/{$procedure['id']}/{$file['name']}") ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="Télécharger">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    
                                                    <?php if ($file['is_image']): ?>
                                                        <button class="btn btn-outline-info"
                                                                onclick="previewImage('<?= base_url() ?>uploads/<?= $file['path'] ?>', '<?= addslashes($file['name']) ?>')"
                                                                title="Prévisualiser">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-outline-secondary" 
                                                            onclick="copyMarkdownLink('<?= $file['name'] ?>', <?= $file['is_image'] ? 'true' : 'false' ?>)"
                                                            title="Copier lien markdown">
                                                        <i class="fab fa-markdown"></i>
                                                    </button>
                                                    
                                                    <?php if ($file['name'] !== "procedure_{$procedure['name']}.md"): ?>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="confirmDelete('<?= $file['name'] ?>')"
                                                                title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal prévisualisation image -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalTitle">Prévisualisation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imageModalImg" src="" class="img-fluid" alt="">
            </div>
        </div>
    </div>
</div>

<!-- Modal confirmation suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le fichier <strong id="fileNameToDelete"></strong> ?</p>
                <p class="text-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Cette action ne peut pas être annulée.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Supprimer</a>
            </div>
        </div>
    </div>
</div>

<!-- Toast pour notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="copyToast" class="toast" role="alert">
        <div class="toast-header">
            <i class="fas fa-check-circle text-success me-2"></i>
            <strong class="me-auto">Copié</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            Le lien markdown a été copié dans le presse-papiers.
        </div>
    </div>
</div>

<script>
// Prévisualisation d'image
function previewImage(src, title) {
    document.getElementById('imageModalImg').src = src;
    document.getElementById('imageModalTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}

// Copier lien markdown
function copyMarkdownLink(filename, isImage) {
    const link = isImage ? `![Description](${filename})` : `[${filename}](${filename})`;
    
    navigator.clipboard.writeText(link).then(function() {
        // Afficher toast de confirmation
        const toast = new bootstrap.Toast(document.getElementById('copyToast'));
        toast.show();
    }).catch(function() {
        // Fallback pour anciens navigateurs
        const textArea = document.createElement('textarea');
        textArea.value = link;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        const toast = new bootstrap.Toast(document.getElementById('copyToast'));
        toast.show();
    });
}

// Confirmation de suppression
function confirmDelete(filename) {
    document.getElementById('fileNameToDelete').textContent = filename;
    document.getElementById('confirmDeleteBtn').href = 
        '<?= site_url("procedures/delete_file/{$procedure['id']}") ?>/' + encodeURIComponent(filename);
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Améliorer l'UX du formulaire d'upload
document.getElementById('uploadForm').addEventListener('submit', function() {
    const btn = document.getElementById('uploadBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Upload en cours...';
    btn.disabled = true;
    
    // Réactiver en cas d'erreur (la page se rechargera en cas de succès)
    setTimeout(function() {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 10000);
});

// Style au survol de la zone de drop (fonctionnalité future)
const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('border-primary', 'bg-light');
});

dropZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('border-primary', 'bg-light');
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('border-primary', 'bg-light');
    // TODO: Implémenter le drag & drop
});
</script>