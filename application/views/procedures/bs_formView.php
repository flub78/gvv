<?php
/**
 * Vue formulaire création/modification de procédure
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('procedures');

$is_edit = isset($id) && !empty($id);
$form_title = $is_edit ? 'Modifier la procédure' : 'Nouvelle procédure';
$form_action = $is_edit ? 'procedures/modifier' : 'procedures/ajout';
?>

<div id="body" class="body ui-widget-content">
    <div class="container-fluid">
        
        <!-- Header -->
        <div class="row mb-3">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="<?= site_url('procedures') ?>">Procédures</a>
                        </li>
                        <?php if ($is_edit): ?>
                            <li class="breadcrumb-item">
                                <a href="<?= site_url("procedures/view/{$id}") ?>"><?= htmlspecialchars($title ?? 'Procédure') ?></a>
                            </li>
                            <li class="breadcrumb-item active">Modifier</li>
                        <?php else: ?>
                            <li class="breadcrumb-item active">Nouvelle procédure</li>
                        <?php endif; ?>
                    </ol>
                </nav>
                <h2>
                    <i class="fas fa-<?= $is_edit ? 'edit' : 'plus' ?>"></i>
                    <?= $form_title ?>
                </h2>
            </div>
        </div>

        <!-- Messages d'erreur de validation -->
        <?php if (validation_errors()): ?>
            <div class="alert alert-danger" role="alert">
                <h5><i class="fas fa-exclamation-triangle"></i> Erreurs de validation</h5>
                <?= validation_errors() ?>
            </div>
        <?php endif; ?>

        <!-- Messages flash -->
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $this->session->flashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('warning')): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?= $this->session->flashdata('warning') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= form_open_multipart($form_action, array('class' => 'needs-validation', 'novalidate' => true)) ?>
        
        <?php if ($is_edit): ?>
            <?= form_hidden('id', $id) ?>
        <?php endif; ?>

        <div class="row">
            <!-- Formulaire principal -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> Informations générales
                        </h5>
                    </div>
                    <div class="card-body">
                        
                        <!-- Nom (uniquement en création) -->
                        <?php if (!$is_edit): ?>
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    Nom technique <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?= set_value('name', $name ?? '') ?>"
                                       pattern="[a-zA-Z0-9_]+"
                                       required>
                                <div class="form-text">
                                    Identifiant unique (lettres, chiffres et underscores uniquement). 
                                    Sera utilisé pour nommer les fichiers.
                                </div>
                                <div class="invalid-feedback">
                                    Le nom est requis et ne doit contenir que des lettres, chiffres et underscores.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label">Nom technique</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($name ?? '') ?>" disabled>
                                <div class="form-text">Le nom technique ne peut pas être modifié après création.</div>
                            </div>
                        <?php endif; ?>

                        <!-- Titre -->
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                Titre <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   value="<?= set_value('title', $title ?? '') ?>"
                                   required>
                            <div class="invalid-feedback">
                                Le titre est requis.
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3"><?= set_value('description', $description ?? '') ?></textarea>
                            <div class="form-text">Description courte de la procédure (optionnel).</div>
                        </div>

                        <!-- Section -->
                        <div class="mb-3">
                            <label for="section_id" class="form-label">Section</label>
                            <select class="form-select" id="section_id" name="section_id">
                                <?php foreach ($section_options as $value => $label): ?>
                                    <option value="<?= $value ?>" 
                                            <?= set_select('section_id', $value, ($section_id ?? '') == $value) ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Section à laquelle appartient cette procédure.</div>
                        </div>

                        <div class="row">
                            <!-- Statut -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Statut</label>
                                    <select class="form-select" id="status" name="status">
                                        <?php foreach ($status_options as $value => $label): ?>
                                            <option value="<?= $value ?>" 
                                                    <?= set_select('status', $value, ($status ?? 'draft') == $value) ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Version -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="version" class="form-label">Version</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="version" 
                                           name="version" 
                                           value="<?= set_value('version', $version ?? '1.0') ?>"
                                           pattern="[0-9]+\.[0-9]+(\.[0-9]+)?"
                                           placeholder="1.0">
                                    <div class="form-text">Format recommandé: x.y ou x.y.z</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Section contenu markdown -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fab fa-markdown"></i> Contenu markdown
                        </h5>
                    </div>
                    <div class="card-body">
                        
                        <!-- Upload de fichier markdown -->
                        <div class="mb-3">
                            <label for="markdown_file" class="form-label">
                                <?= $is_edit ? 'Remplacer le fichier markdown' : 'Fichier markdown initial' ?>
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   id="markdown_file" 
                                   name="markdown_file" 
                                   accept=".md,.txt">
                            <div class="form-text">
                                <?php if ($is_edit): ?>
                                    Optionnel. Si fourni, remplacera le contenu actuel.
                                <?php else: ?>
                                    Optionnel. Un fichier vide sera créé si aucun fichier n'est fourni.
                                <?php endif; ?>
                                Formats acceptés: .md, .txt
                            </div>
                        </div>

                        <!-- Éditeur de contenu pour modification -->
                        <?php if ($is_edit && isset($markdown_content)): ?>
                            <div class="mb-3">
                                <label for="markdown_content" class="form-label">
                                    Contenu actuel
                                    <small class="text-muted">(ou utilisez l'éditeur dédié)</small>
                                </label>
                                <textarea class="form-control font-monospace" 
                                          id="markdown_content" 
                                          name="markdown_content" 
                                          rows="15"
                                          style="font-size: 0.9em;"><?= htmlspecialchars($markdown_content) ?></textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i>
                                    Pour un éditeur plus avancé, utilisez 
                                    <a href="<?= site_url("procedures/edit_markdown/{$id}") ?>" target="_blank">
                                        l'éditeur markdown dédié
                                    </a>.
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>

            <!-- Sidebar avec aide et actions -->
            <div class="col-lg-4">
                
                <!-- Aide markdown -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-question-circle"></i> Aide markdown
                        </h5>
                    </div>
                    <div class="card-body">
                        <small>
                            <strong>Syntaxe de base:</strong><br>
                            <code># Titre 1</code><br>
                            <code>## Titre 2</code><br>
                            <code>**Gras**</code><br>
                            <code>*Italique*</code><br>
                            <code>[Lien](url)</code><br>
                            <code>![Image](chemin)</code><br>
                            <code>- Liste</code><br>
                            <code>1. Liste numérotée</code><br>
                            <code>- [ ] Case à cocher</code><br>
                            <code>`Code`</code><br>
                            <code>> Citation</code>
                        </small>
                        
                        <hr>
                        
                        <strong>Images:</strong><br>
                        <small>
                            Les images peuvent être uploadées séparément 
                            et référencées avec: <br>
                            <code>![Description](nom_fichier.jpg)</code>
                        </small>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-save"></i> Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                <?= $is_edit ? 'Enregistrer les modifications' : 'Créer la procédure' ?>
                            </button>
                            
                            <?php if ($is_edit): ?>
                                <a href="<?= site_url("procedures/view/{$id}") ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                            <?php else: ?>
                                <a href="<?= site_url('procedures') ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($is_edit): ?>
                            <hr>
                            <div class="d-grid">
                                <a href="<?= site_url("procedures/edit_markdown/{$id}") ?>" 
                                   class="btn btn-outline-info btn-sm">
                                    <i class="fab fa-markdown"></i> Éditeur markdown avancé
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Informations de création/modification -->
                <?php if ($is_edit): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info"></i> Informations
                            </h5>
                        </div>
                        <div class="card-body">
                            <small>
                                <strong>Créé le:</strong><br>
                                <?= date('d/m/Y H:i', strtotime($created_at)) ?><br>
                                par <?= htmlspecialchars($created_by) ?>
                                
                                <?php if (isset($updated_at) && $updated_at !== $created_at): ?>
                                    <br><br>
                                    <strong>Modifié le:</strong><br>
                                    <?= date('d/m/Y H:i', strtotime($updated_at)) ?><br>
                                    par <?= htmlspecialchars($updated_by ?? $created_by) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <?= form_close() ?>
        
    </div>
</div>

<script>
// Validation Bootstrap
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Auto-génération du nom à partir du titre (uniquement en création)
<?php if (!$is_edit): ?>
document.getElementById('title').addEventListener('input', function() {
    const nameField = document.getElementById('name');
    if (nameField.value === '') {
        // Générer un nom à partir du titre
        let name = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s]/g, '') // Supprimer caractères spéciaux
            .replace(/\s+/g, '_') // Remplacer espaces par underscore
            .substring(0, 50); // Limiter la longueur
        nameField.value = name;
    }
});
<?php endif; ?>

// Prévisualisation markdown en temps réel (si en modification)
<?php if ($is_edit): ?>
function toggleMarkdownPreview() {
    const content = document.getElementById('markdown_content').value;
    // Ici on pourrait ajouter une prévisualisation en temps réel avec un parser markdown JS
}
<?php endif; ?>
</script>