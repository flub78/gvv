<!-- VIEW: application/views/formation_inscriptions/cloturer.php -->
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
 * Vue dialogue clôture d'inscription
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
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <?= $this->lang->line("formation_inscription_cloturer_title") ?>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>/detail/<?= $inscription['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("gvv_str_back") ?>
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> <?= $this->session->flashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <strong><?= $this->lang->line("formation_inscription_cloturer_info") ?></strong>
            </div>

            <?= form_open(controller_url($controller) . '/cloturer/' . $inscription['id']) ?>

                <div class="mb-3">
                    <label class="form-label">
                        <?= $this->lang->line("formation_inscription_type_cloture") ?>
                        <span class="text-danger">*</span>
                    </label>
                    <div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_cloturee" 
                                   value="cloturee" <?= set_radio('type', 'cloturee', true) ?> required>
                            <label class="form-check-label" for="type_cloturee">
                                <i class="fas fa-check text-success" aria-hidden="true"></i>
                                <strong><?= $this->lang->line("formation_inscription_cloturee") ?></strong>
                                <small class="text-muted d-block">Formation terminée avec succès</small>
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="type" id="type_abandonnee" 
                                   value="abandonnee" <?= set_radio('type', 'abandonnee') ?> required>
                            <label class="form-check-label" for="type_abandonnee">
                                <i class="fas fa-times text-danger" aria-hidden="true"></i>
                                <strong><?= $this->lang->line("formation_inscription_abandonnee") ?></strong>
                                <small class="text-muted d-block">Formation abandonnée (nécessite un motif)</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-3" id="motif_container">
                    <label for="motif" class="form-label">
                        <?= $this->lang->line("formation_inscription_motif_cloture") ?>
                        <span class="text-danger" id="motif_required">*</span>
                    </label>
                    <textarea class="form-control" id="motif" name="motif" rows="4" 
                              placeholder="Ex: Formation terminée / Déménagement / Manque de temps..."><?= set_value('motif') ?></textarea>
                    <div class="form-text">
                        Motif obligatoire pour les abandons
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check" aria-hidden="true"></i> <?= $this->lang->line("formation_inscription_cloturer_confirm_btn") ?>
                    </button>
                    <a href="<?= controller_url($controller) ?>/detail/<?= $inscription['id'] ?>" class="btn btn-secondary">
                        <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line("gvv_str_cancel") ?>
                    </a>
                </div>

            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
// Toggle required attribute on motif based on type selection
document.addEventListener('DOMContentLoaded', function() {
    const typeCloturee = document.getElementById('type_cloturee');
    const typeAbandonnee = document.getElementById('type_abandonnee');
    const motifField = document.getElementById('motif');
    const motifRequired = document.getElementById('motif_required');
    
    function updateMotifRequired() {
        if (typeAbandonnee.checked) {
            motifField.required = true;
            motifRequired.style.display = 'inline';
        } else {
            motifField.required = false;
            motifRequired.style.display = 'none';
        }
    }
    
    typeCloturee.addEventListener('change', updateMotifRequired);
    typeAbandonnee.addEventListener('change', updateMotifRequired);
    updateMotifRequired();
});
</script>

<?php $this->load->view('bs_footer'); ?>
