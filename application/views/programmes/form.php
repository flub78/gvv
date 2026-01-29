<!-- VIEW: application/views/programmes/form.php -->
<?php
/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Vue formulaire création/édition programme de formation
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('formation');
$this->lang->load('gvv');

$is_edit = isset($action) && $action === 'edit';
$form_url = $is_edit ? 
    controller_url($controller) . '/update/' . $programme['id'] : 
    controller_url($controller) . '/store';

?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?= $is_edit ? 
            $this->lang->line("formation_programmes_edit") : 
            $this->lang->line("formation_programmes_create") 
        ?></h3>
        <div>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("formation_programmes_back") ?>
            </a>
        </div>
    </div>

    <?php
    // Display validation errors
    if (validation_errors()) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<strong><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Erreurs de validation</strong><br>';
        echo validation_errors();
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }

    // Display import errors
    if (isset($import_error) && !empty($import_error)) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<strong><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Erreur d\'import Markdown</strong><br><br>';
        echo '<pre style="white-space: pre-wrap; font-family: monospace; background: #f8f9fa; padding: 10px; border-radius: 4px;">';
        echo htmlspecialchars($import_error);
        echo '</pre>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        
        // Auto-switch to import tab if import error
        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded", function() {';
        echo '  var importTab = new bootstrap.Tab(document.getElementById("import-tab"));';
        echo '  importTab.show();';
        echo '});';
        echo '</script>';
    }
    ?>

    <?php if (!$is_edit): ?>
        <!-- Import/Manual toggle for creation -->
        <ul class="nav nav-tabs mb-3" id="createMethodTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="manual-tab" data-bs-toggle="tab" 
                        data-bs-target="#manual-panel" type="button" role="tab">
                    <i class="fas fa-keyboard" aria-hidden="true"></i> <?= $this->lang->line("formation_import_manual") ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="import-tab" data-bs-toggle="tab" 
                        data-bs-target="#import-panel" type="button" role="tab">
                    <i class="fas fa-file-upload" aria-hidden="true"></i> <?= $this->lang->line("formation_import_from_markdown") ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="createMethodContent">
            <!-- Manual creation tab -->
            <div class="tab-pane fade show active" id="manual-panel" role="tabpanel">
    <?php endif; ?>

                <!-- Manual creation/edit form -->
                <?= form_open($form_url, array('id' => 'programme-form', 'class' => 'needs-validation', 'novalidate' => '')) ?>

                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle" aria-hidden="true"></i> 
                                <?= $this->lang->line("gvv_str_informations") ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Titre -->
                            <div class="mb-3">
                                <label for="titre" class="form-label">
                                    <?= $this->lang->line("formation_programme_titre") ?> 
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="titre" name="titre" 
                                       value="<?= set_value('titre', $programme['titre'] ?? '') ?>" 
                                       maxlength="255" required>
                            </div>

                            <!-- Description -->
                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <?= $this->lang->line("formation_programme_description") ?>
                                </label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3" maxlength="1000"><?= set_value('description', $programme['description'] ?? '') ?></textarea>
                                <div class="form-text">
                                    <?= $this->lang->line("formation_form_optional") ?>
                                </div>
                            </div>

                            <!-- Section -->
                            <div class="mb-3">
                                <label for="section_id" class="form-label">
                                    <?= $this->lang->line("formation_programme_section") ?>
                                </label>
                                <?php
                                $this->load->model('sections_model');
                                $sections = $this->sections_model->section_selector_with_null();
                                echo form_dropdown(
                                    'section_id',
                                    $sections,
                                    set_value('section_id', $programme['section_id'] ?? ''),
                                    'class="form-select" id="section_id"'
                                );
                                ?>
                                <div class="form-text">
                                    <?= $this->lang->line("formation_programme_section_help") ?>
                                </div>
                            </div>

                            <!-- Type aéronef -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <?= $this->lang->line("formation_programme_type_aeronef") ?>
                                    <span class="text-danger">*</span>
                                </label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="type_aeronef" id="type_planeur" value="planeur" <?= set_radio('type_aeronef', 'planeur', !isset($programme['type_aeronef']) || $programme['type_aeronef'] === 'planeur') ?>>
                                        <label class="form-check-label" for="type_planeur">
                                            <i class="fas fa-plane" aria-hidden="true"></i>
                                            <?= $this->lang->line("formation_programme_type_planeur") ?>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="type_aeronef" id="type_avion" value="avion" <?= set_radio('type_aeronef', 'avion', isset($programme['type_aeronef']) && $programme['type_aeronef'] === 'avion') ?>>
                                        <label class="form-check-label" for="type_avion">
                                            <i class="fas fa-fighter-jet" aria-hidden="true"></i>
                                            <?= $this->lang->line("formation_programme_type_avion") ?>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <?php if ($is_edit): ?>
                                <!-- Statut radio buttons (edit only) -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <?= $this->lang->line("formation_programme_actif") ?>
                                    </label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="statut" id="statut_actif" value="actif" <?= set_radio('statut', 'actif', isset($programme['statut']) && $programme['statut'] === 'actif') ?>>
                                            <label class="form-check-label" for="statut_actif">
                                                Actif
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="statut" id="statut_archive" value="archive" <?= set_radio('statut', 'archive', isset($programme['statut']) && $programme['statut'] === 'archive') ?>>
                                            <label class="form-check-label" for="statut_archive">
                                                Archivé
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$is_edit): ?>
                        <!-- Markdown content for manual creation -->
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-code" aria-hidden="true"></i>
                                    Structure du programme (Markdown)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="contenu_markdown" class="form-label">Contenu Markdown</label>
                                    <textarea class="form-control font-monospace"
                                              id="contenu_markdown"
                                              name="contenu_markdown"
                                              rows="15"
                                              style="font-size: 0.9rem;"><?= set_value('contenu_markdown', '') ?></textarea>
                                    <div class="form-text">
                                        Syntaxe : <code># Titre du programme</code>, <code>## Leçon X : Titre</code>, <code>### Sujet X.Y : Titre</code>.
                                        Ce champ est optionnel. Vous pouvez aussi ajouter la structure après la création.
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_edit && !empty($lecons)): ?>
                        <!-- Lessons display (read-only in edit form) -->
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-book" aria-hidden="true"></i> 
                                    <?= $this->lang->line("formation_lecons") ?> (<?= count($lecons) ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="accordion" id="leconsAccordion">
                                    <?php foreach ($lecons as $index => $lecon): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?= $lecon['id'] ?>">
                                                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" 
                                                        type="button" data-bs-toggle="collapse" 
                                                        data-bs-target="#collapse<?= $lecon['id'] ?>">
                                                    <strong>Leçon <?= $lecon['numero'] ?>:</strong>&nbsp;
                                                    <?= htmlspecialchars($lecon['titre']) ?>
                                                    <span class="badge bg-secondary ms-2">
                                                        <?= count($lecon['sujets']) ?> 
                                                        <?= count($lecon['sujets']) > 1 ? 
                                                            $this->lang->line("formation_sujets") : 
                                                            $this->lang->line("formation_sujet") ?>
                                                    </span>
                                                </button>
                                            </h2>
                                            <div id="collapse<?= $lecon['id'] ?>" 
                                                 class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                                                 data-bs-parent="#leconsAccordion">
                                                <div class="accordion-body">
                                                    <?php if (!empty($lecon['sujets'])): ?>
                                                        <ul class="list-group">
                                                            <?php foreach ($lecon['sujets'] as $sujet): ?>
                                                                <li class="list-group-item">
                                                                    <strong>Sujet <?= $sujet['numero'] ?>:</strong> 
                                                                    <?= htmlspecialchars($sujet['titre']) ?>
                                                                    <?php if (!empty($sujet['description'])): ?>
                                                                        <p class="mb-0 mt-1 text-muted small">
                                                                            <?= nl2br(htmlspecialchars($sujet['description'])) ?>
                                                                        </p>
                                                                    <?php endif; ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <p class="text-muted mb-0">
                                                            <i class="fas fa-info-circle" aria-hidden="true"></i> 
                                                            <?= $this->lang->line("formation_programmes_no_programmes") ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Submit buttons -->
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save" aria-hidden="true"></i> <?= $this->lang->line("formation_form_save") ?>
                        </button>
                        <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                            <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line("formation_form_cancel") ?>
                        </a>
                    </div>

                <?= form_close() ?>

                <?php if ($is_edit): ?>
                    <!-- Structure editing panel (outside main form to avoid nesting) -->
                    <?php $has_inscriptions = isset($nb_inscriptions) && $nb_inscriptions > 0; ?>
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-code" aria-hidden="true"></i>
                                <?= $this->lang->line("formation_structure_markdown") ?>
                            </h5>
                            <?php if ($has_inscriptions): ?>
                                <span class="btn btn-secondary btn-sm disabled" title="<?= sprintf($this->lang->line("formation_programme_update_structure_blocked"), $nb_inscriptions) ?>">
                                    <i class="fas fa-lock" aria-hidden="true"></i> Modification bloquée
                                </span>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary btn-sm" id="toggleEditStructure">
                                    <i class="fas fa-edit" aria-hidden="true"></i> Modifier la structure
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($has_inscriptions): ?>
                        <div class="card-body">
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                <?= sprintf($this->lang->line("formation_programme_update_structure_blocked"), $nb_inscriptions) ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="card-body" id="editStructurePanel" style="display: none;">
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="edit-text-tab" data-bs-toggle="tab"
                                            data-bs-target="#edit-text-panel" type="button" role="tab">
                                        <i class="fas fa-edit" aria-hidden="true"></i> Éditer le texte
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="upload-file-tab" data-bs-toggle="tab"
                                            data-bs-target="#upload-file-panel" type="button" role="tab">
                                        <i class="fas fa-file-upload" aria-hidden="true"></i> Importer un fichier
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <!-- Text editor tab -->
                                <div class="tab-pane fade show active" id="edit-text-panel" role="tabpanel">
                                    <?= form_open(controller_url($controller) . '/update_structure/' . $programme['id']) ?>
                                        <div class="mb-3">
                                            <label for="markdown_content" class="form-label">Contenu Markdown</label>
                                            <textarea class="form-control font-monospace"
                                                      id="markdown_content"
                                                      name="markdown_content"
                                                      rows="20"
                                                      style="font-size: 0.9rem;"><?= htmlspecialchars($programme['contenu_markdown'] ?? '') ?></textarea>
                                            <div class="form-text">
                                                Modifiez la structure du programme en Markdown.
                                                Respectez la syntaxe : # Titre, ## Leçon, ### Sujet
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save" aria-hidden="true"></i> Enregistrer les modifications
                                            </button>
                                            <button type="button" class="btn btn-secondary" id="cancelEdit">
                                                <i class="fas fa-times" aria-hidden="true"></i> Annuler
                                            </button>
                                        </div>
                                    <?= form_close() ?>
                                </div>

                                <!-- File upload tab -->
                                <div class="tab-pane fade" id="upload-file-panel" role="tabpanel">
                                    <?= form_open_multipart(controller_url($controller) . '/update_structure/' . $programme['id']) ?>
                                        <div class="mb-3">
                                            <label for="markdown_file_update" class="form-label">Fichier Markdown</label>
                                            <input type="file" class="form-control" id="markdown_file_update"
                                                   name="markdown_file" accept=".md,.markdown,.txt" required>
                                            <div class="form-text">
                                                Sélectionnez un fichier Markdown pour remplacer la structure actuelle du programme.
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-upload" aria-hidden="true"></i> Importer et mettre à jour
                                            </button>
                                            <button type="button" class="btn btn-secondary" id="cancelUpload">
                                                <i class="fas fa-times" aria-hidden="true"></i> Annuler
                                            </button>
                                        </div>
                                    <?= form_close() ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$has_inscriptions): ?>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const toggleBtn = document.getElementById('toggleEditStructure');
                        const editPanel = document.getElementById('editStructurePanel');
                        const cancelEditBtn = document.getElementById('cancelEdit');
                        const cancelUploadBtn = document.getElementById('cancelUpload');

                        function closePanel() {
                            editPanel.style.display = 'none';
                            toggleBtn.innerHTML = '<i class="fas fa-edit" aria-hidden="true"></i> Modifier la structure';
                            toggleBtn.classList.remove('btn-secondary');
                            toggleBtn.classList.add('btn-primary');
                        }

                        toggleBtn.addEventListener('click', function() {
                            if (editPanel.style.display === 'none') {
                                editPanel.style.display = 'block';
                                toggleBtn.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i> Fermer';
                                toggleBtn.classList.remove('btn-primary');
                                toggleBtn.classList.add('btn-secondary');
                            } else {
                                closePanel();
                            }
                        });

                        cancelEditBtn.addEventListener('click', closePanel);
                        cancelUploadBtn.addEventListener('click', closePanel);
                    });
                    </script>
                    <?php endif; ?>
                <?php endif; ?>

    <?php if (!$is_edit): ?>
            </div>

            <!-- Import from Markdown tab -->
            <div class="tab-pane fade" id="import-panel" role="tabpanel">
                <?= form_open_multipart($form_url, array('id' => 'import-form')) ?>
                    
                    <input type="hidden" name="import_markdown" value="1">
                    
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-file-upload" aria-hidden="true"></i> 
                                <?= $this->lang->line("formation_import_from_markdown") ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                <strong>Format Markdown attendu :</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Titre du programme : <code># Titre</code></li>
                                    <li>Leçon : <code>## Leçon X : Titre</code></li>
                                    <li>Sujet : <code>### Sujet X.Y : Titre</code></li>
                                </ul>
                            </div>

                            <!-- File upload -->
                            <div class="mb-3">
                                <label for="markdown_file" class="form-label">
                                    <?= $this->lang->line("formation_import_file") ?> 
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="markdown_file" name="markdown_file" 
                                       accept=".md,.markdown,text/markdown" required>
                                <div class="form-text">
                                    <?= $this->lang->line("formation_import_file_help") ?>
                                </div>
                            </div>

                            <!-- Section -->
                            <div class="mb-3">
                                <label for="section_id_import" class="form-label">
                                    <?= $this->lang->line("formation_programme_section") ?>
                                </label>
                                <?php
                                $this->load->model('sections_model');
                                $sections = $this->sections_model->section_selector_with_null();
                                echo form_dropdown(
                                    'section_id',
                                    $sections,
                                    set_value('section_id', ''),
                                    'class="form-select" id="section_id_import"'
                                );
                                ?>
                                <div class="form-text">
                                    <?= $this->lang->line("formation_programme_section_help") ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit buttons -->
                    <div class="mb-3">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload" aria-hidden="true"></i> <?= $this->lang->line("formation_import_from_markdown") ?>
                        </button>
                        <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                            <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line("formation_form_cancel") ?>
                        </a>
                    </div>

                <?= form_close() ?>
            </div>
        </div>
    <?php endif; ?>
</div>
