<!-- VIEW: application/views/formation_inscriptions/ouvrir.php -->
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
 * Vue formulaire d'ouverture d'inscription
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('formation');
$this->lang->load('gvv');

?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-user-plus" aria-hidden="true"></i>
            <?= $this->lang->line("formation_inscriptions_ouvrir") ?>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("gvv_str_back") ?>
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
    
    if ($this->session->flashdata('error')) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i> ' . $this->session->flashdata('error');
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    ?>

    <?= form_open(controller_url($controller) . '/store', array('id' => 'inscription-form', 'class' => 'needs-validation', 'novalidate' => '')) ?>

        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle" aria-hidden="true"></i> 
                    <?= $this->lang->line("gvv_str_informations") ?>
                </h5>
            </div>
            <div class="card-body">
                <!-- Pilote -->
                <div class="mb-3">
                    <label for="pilote_id" class="form-label">
                        <?= $this->lang->line("formation_inscription_pilote") ?> 
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="pilote_id" name="pilote_id" required>
                        <option value="">-- Sélectionnez un pilote --</option>
                        <?php foreach ($pilotes as $id => $nom): ?>
                            <option value="<?= $id ?>" <?= set_select('pilote_id', $id) ?>>
                                <?= htmlspecialchars($nom) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Programme -->
                <div class="mb-3">
                    <label for="programme_id" class="form-label">
                        <?= $this->lang->line("formation_inscription_programme") ?> 
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="programme_id" name="programme_id" required>
                        <option value="">-- Sélectionnez un programme --</option>
                        <?php foreach ($programmes as $id => $titre): ?>
                            <option value="<?= $id ?>" <?= set_select('programme_id', $id) ?>>
                                <?= htmlspecialchars($titre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Instructeur référent -->
                <div class="mb-3">
                    <label for="instructeur_referent_id" class="form-label">
                        <?= $this->lang->line("formation_inscription_instructeur") ?>
                    </label>
                    <select class="form-select" id="instructeur_referent_id" name="instructeur_referent_id">
                        <option value="">-- Aucun --</option>
                        <?php foreach ($instructeurs as $id => $nom): ?>
                            <option value="<?= $id ?>" <?= set_select('instructeur_referent_id', $id) ?>>
                                <?= htmlspecialchars($nom) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <?= $this->lang->line("formation_form_optional") ?>
                    </div>
                </div>

                <!-- Date d'ouverture -->
                <div class="mb-3">
                    <label for="date_ouverture" class="form-label">
                        <?= $this->lang->line("formation_inscription_date_ouverture") ?> 
                        <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control" id="date_ouverture" name="date_ouverture" 
                           value="<?= set_value('date_ouverture', date('Y-m-d')) ?>" required>
                </div>

                <!-- Commentaire -->
                <div class="mb-3">
                    <label for="commentaire" class="form-label">
                        <?= $this->lang->line("formation_inscription_commentaire") ?>
                    </label>
                    <textarea class="form-control" id="commentaire" name="commentaire" 
                              rows="3" maxlength="500"><?= set_value('commentaire') ?></textarea>
                    <div class="form-text">
                        <?= $this->lang->line("formation_form_optional") ?>
                    </div>
                </div>
            </div>
        </div>

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
</div>

<?php $this->load->view('bs_footer'); ?>
