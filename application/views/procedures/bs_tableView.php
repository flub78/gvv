<?php
/**
 * Vue liste des procédures
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('procedures');
?>

<div id="body" class="body ui-widget-content">
    <div class="container-fluid">
        
        <!-- Header avec titre et bouton création -->
        <div class="row mb-3">
            <div class="col-md-8">
                <h2>
                    <i class="fas fa-book"></i>
                    <?= $this->lang->line('procedures_title') ?: 'Gestion des Procédures' ?>
                </h2>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($this->dx_auth->is_role('ca') || $this->dx_auth->is_role('admin')): ?>
                    <a href="<?= site_url('procedures/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouvelle Procédure
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filtres -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Statut</label>
                                <select name="status" id="status" class="form-select">
                                    <?php foreach ($status_options as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= ($status_filter === $value) ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="section" class="form-label">Section</label>
                                <select name="section" id="section" class="form-select">
                                    <?php foreach ($section_options as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= ($section_filter === $value) ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-secondary me-2">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                                <a href="<?= site_url('procedures') ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
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

        <!-- Liste des procédures -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($procedures)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucune procédure trouvée</h5>
                                <p class="text-muted">
                                    <?php if ($this->dx_auth->is_role('ca') || $this->dx_auth->is_role('admin')): ?>
                                        <a href="<?= site_url('procedures/create') ?>" class="btn btn-primary">
                                            Créer la première procédure
                                        </a>
                                    <?php else: ?>
                                        Aucune procédure n'est disponible pour l'instant.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Titre</th>
                                            <th>Section</th>
                                            <th>Statut</th>
                                            <th>Version</th>
                                            <th>Modifié le</th>
                                            <th>Fichiers</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($procedures as $procedure): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong>
                                                            <a href="<?= site_url("procedures/view/{$procedure['id']}") ?>" 
                                                               class="text-decoration-none">
                                                                <?= htmlspecialchars($procedure['title']) ?>
                                                            </a>
                                                        </strong>
                                                        <?php if (!empty($procedure['description'])): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?= htmlspecialchars(substr($procedure['description'], 0, 100)) ?>
                                                                <?= strlen($procedure['description']) > 100 ? '...' : '' ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($procedure['section_name']): ?>
                                                        <span class="badge bg-info">
                                                            <?= htmlspecialchars($procedure['section_name']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Globale</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
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
                                                </td>
                                                <td>
                                                    <code><?= htmlspecialchars($procedure['version']) ?></code>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?= date('d/m/Y H:i', strtotime($procedure['updated_at'])) ?>
                                                        <br>
                                                        <span class="text-muted">par <?= htmlspecialchars($procedure['created_by']) ?></span>
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <?php if ($procedure['has_markdown']): ?>
                                                            <span class="badge bg-primary mb-1">
                                                                <i class="fab fa-markdown"></i> MD
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($procedure['attachments_count'] > 0): ?>
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-paperclip"></i> <?= $procedure['attachments_count'] ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="<?= site_url("procedures/view/{$procedure['id']}") ?>" 
                                                           class="btn btn-outline-primary" title="Voir">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($this->dx_auth->is_role('ca') || $this->dx_auth->is_role('admin')): ?>
                                                            <a href="<?= site_url("procedures/edit/{$procedure['id']}") ?>" 
                                                               class="btn btn-outline-secondary" title="Modifier">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="<?= site_url("procedures/attachments/{$procedure['id']}") ?>" 
                                                               class="btn btn-outline-info" title="Fichiers">
                                                                <i class="fas fa-paperclip"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($this->dx_auth->is_role('admin')): ?>
                                                            <button class="btn btn-outline-danger" 
                                                                    onclick="confirmDelete(<?= $procedure['id'] ?>, '<?= addslashes($procedure['title']) ?>')"
                                                                    title="Supprimer">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal de confirmation de suppression -->
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

<script>
function confirmDelete(id, title) {
    document.getElementById('procedureName').textContent = title;
    document.getElementById('confirmDeleteBtn').href = '<?= site_url("procedures/delete") ?>/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Auto-submit des filtres
document.getElementById('status').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('section').addEventListener('change', function() {
    this.form.submit();
});
</script>