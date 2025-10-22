<?php
/**
 * Vue visualisation d'une procédure
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('procedures');
?>

<div id="body" class="body ui-widget-content">
    <div class="container-fluid">
        
        <!-- Header avec titre et actions -->
        <div class="row mb-3">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="<?= site_url('procedures') ?>">Procédures</a>
                        </li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($procedure['title']) ?></li>
                    </ol>
                </nav>
                <h2>
                    <i class="fas fa-book-open"></i>
                    <?= htmlspecialchars($procedure['title']) ?>
                </h2>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group mt-4" role="group">
                    <?php if ($can_edit): ?>
                        <a href="<?= site_url("procedures/edit/{$procedure['id']}") ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <a href="<?= site_url("procedures/edit_markdown/{$procedure['id']}") ?>" class="btn btn-secondary">
                            <i class="fab fa-markdown"></i> Éditer
                        </a>
                        <a href="<?= site_url("procedures/attachments/{$procedure['id']}") ?>" class="btn btn-info">
                            <i class="fas fa-paperclip"></i> Fichiers
                        </a>
                    <?php endif; ?>
                </div>
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
            <!-- Contenu principal -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fab fa-markdown"></i> Contenu de la procédure
                        </h5>
                        <?php if ($can_edit && !empty($markdown_content)): ?>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary" onclick="toggleRawMarkdown()">
                                    <i class="fas fa-code"></i> Source
                                </button>
                                <a href="<?= site_url("procedures/download/{$procedure['id']}/procedure_{$procedure['name']}.md") ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-download"></i> Télécharger
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($markdown_content)): ?>
                            <div class="text-center py-5">
                                <i class="fab fa-markdown fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun contenu markdown</h5>
                                <p class="text-muted">Cette procédure n'a pas encore de contenu.</p>
                                <?php if ($can_edit): ?>
                                    <a href="<?= site_url("procedures/edit_markdown/{$procedure['id']}") ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Ajouter du contenu
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Contenu rendu -->
                            <div id="markdown-rendered" class="markdown-content">
                                <?= $markdown_html ?>
                            </div>
                            
                            <!-- Source markdown (masqué par défaut) -->
                            <div id="markdown-source" style="display: none;">
                                <pre><code class="language-markdown"><?= htmlspecialchars($markdown_content) ?></code></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Fichiers attachés -->
                <?php if (!empty($attached_files)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-paperclip"></i> Fichiers attachés
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($attached_files as $file): ?>
                                    <?php if ($file['name'] !== "procedure_{$procedure['name']}.md"): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card h-100">
                                                <?php if ($file['is_image'] && isset($file['thumbnail'])): ?>
                                                    <img src="<?= base_url() ?>uploads/<?= $file['thumbnail'] ?>"
                                                         class="card-img-top"
                                                         style="height: 150px; object-fit: cover;"
                                                         alt="<?= htmlspecialchars($file['name']) ?>">
                                                <?php else: ?>
                                                    <div class="card-img-top d-flex align-items-center justify-content-center" 
                                                         style="height: 150px; background-color: #f8f9fa;">
                                                        <i class="fas fa-file fa-3x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="card-body p-2">
                                                    <h6 class="card-title small mb-1" title="<?= htmlspecialchars($file['name']) ?>">
                                                        <?= htmlspecialchars(strlen($file['name']) > 25 ? substr($file['name'], 0, 25) . '...' : $file['name']) ?>
                                                    </h6>
                                                    <p class="card-text small text-muted mb-2">
                                                        <?= $file['size_human'] ?>
                                                    </p>
                                                    <a href="<?= site_url("procedures/download/{$procedure['id']}/{$file['name']}") ?>" 
                                                       class="btn btn-sm btn-outline-primary w-100">
                                                        <i class="fas fa-download"></i> Télécharger
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar informations -->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> Informations
                        </h5>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-5">Statut:</dt>
                            <dd class="col-sm-7">
                                <?php
                                $status_classes = [
                                    'draft' => 'bg-warning',
                                    'published' => 'bg-success',
                                    'archived' => 'bg-dark'
                                ];
                                $status_labels = [
                                    'draft' => 'Brouillon',
                                    'published' => 'Publiée',
                                    'archived' => 'Archivée'
                                ];
                                ?>
                                <span class="badge <?= $status_classes[$procedure['status']] ?? 'bg-secondary' ?>">
                                    <?= $status_labels[$procedure['status']] ?? $procedure['status'] ?>
                                </span>
                            </dd>

                            <dt class="col-sm-5">Version:</dt>
                            <dd class="col-sm-7">
                                <code><?= htmlspecialchars($procedure['version']) ?></code>
                            </dd>

                            <dt class="col-sm-5">Section:</dt>
                            <dd class="col-sm-7">
                                <?php if ($procedure['section_id']): ?>
                                    <span class="badge bg-info">
                                        <?= htmlspecialchars($procedure['section_name'] ?? 'Section inconnue') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Globale</span>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-5">Créé le:</dt>
                            <dd class="col-sm-7">
                                <small>
                                    <?= date('d/m/Y H:i', strtotime($procedure['created_at'])) ?><br>
                                    par <?= htmlspecialchars($procedure['created_by']) ?>
                                </small>
                            </dd>

                            <dt class="col-sm-5">Modifié le:</dt>
                            <dd class="col-sm-7">
                                <small>
                                    <?= date('d/m/Y H:i', strtotime($procedure['updated_at'])) ?><br>
                                    par <?= htmlspecialchars($procedure['updated_by'] ?? $procedure['created_by']) ?>
                                </small>
                            </dd>
                        </dl>

                        <?php if (!empty($procedure['description'])): ?>
                            <hr>
                            <h6>Description:</h6>
                            <p class="small text-muted">
                                <?= htmlspecialchars($procedure['description']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions rapides -->
                <?php if ($can_edit): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-tools"></i> Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="<?= site_url("procedures/edit/{$procedure['id']}") ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit"></i> Modifier les propriétés
                                </a>
                                <a href="<?= site_url("procedures/edit_markdown/{$procedure['id']}") ?>" 
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="fab fa-markdown"></i> Éditer le contenu
                                </a>
                                <a href="<?= site_url("procedures/attachments/{$procedure['id']}") ?>" 
                                   class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-paperclip"></i> Gérer les fichiers
                                </a>
                                <?php if ($this->dx_auth->is_role('admin')): ?>
                                    <hr class="my-2">
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="confirmDelete(<?= $procedure['id'] ?>, '<?= addslashes($procedure['title']) ?>')">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<?php if ($this->dx_auth->is_role('admin')): ?>
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer la procédure <strong id="procedureName"></strong> ?</p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Cette action supprimera également tous les fichiers associés et ne peut pas être annulée.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Supprimer</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function toggleRawMarkdown() {
    const rendered = document.getElementById('markdown-rendered');
    const source = document.getElementById('markdown-source');
    
    if (rendered.style.display === 'none') {
        rendered.style.display = 'block';
        source.style.display = 'none';
    } else {
        rendered.style.display = 'none';
        source.style.display = 'block';
    }
}

function confirmDelete(id, title) {
    document.getElementById('procedureName').textContent = title;
    document.getElementById('confirmDeleteBtn').href = '<?= site_url("procedures/delete") ?>/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Améliorer l'affichage des images dans le markdown
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter un click handler pour les images dans le markdown
    const markdownImages = document.querySelectorAll('#markdown-rendered img');
    markdownImages.forEach(function(img) {
        img.style.cursor = 'pointer';
        img.addEventListener('click', function() {
            window.open(this.src, '_blank');
        });
    });
});
</script>