<!-- VIEW: application/views/terrains/bs_ajax_modal.php -->
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
 * Fenêtre modale de création rapide d'un aérodrome, incluse dans les
 * formulaires de saisie de vol (vols_avion/create, vols_planeur/create).
 *
 * Ne contient volontairement pas de balise <form> : elle est placée en
 * dehors du formulaire de saisie de vol et l'envoi AJAX est géré par
 * assets/javascript/terrain_modal.js.
 *
 * @package vues
 */
$this->lang->load('terrains');
?>
<div class="modal fade" id="terrainModal" tabindex="-1" aria-labelledby="terrainModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="terrainModalLabel"><?= $this->lang->line('gvv_terrains_ajax_modal_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $this->lang->line('gvv_terrains_ajax_cancel') ?>"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="terrainModalGlobalError" role="alert"></div>

                <div class="mb-3">
                    <label for="terrainModalOaci" class="form-label"><?= $this->lang->line('gvv_terrains_field_oaci') ?></label>
                    <input type="text" class="form-control text-uppercase" id="terrainModalOaci" maxlength="10" autocomplete="off">
                    <div class="invalid-feedback" id="terrainModalOaciError"></div>
                </div>

                <div class="mb-3">
                    <label for="terrainModalNom" class="form-label"><?= $this->lang->line('gvv_terrains_field_nom') ?></label>
                    <input type="text" class="form-control" id="terrainModalNom" maxlength="64" autocomplete="off">
                    <div class="invalid-feedback" id="terrainModalNomError"></div>
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="terrainModalFreq1" class="form-label"><?= $this->lang->line('gvv_terrains_field_freq1') ?></label>
                        <input type="text" class="form-control" id="terrainModalFreq1" inputmode="decimal" placeholder="122.500" autocomplete="off">
                        <div class="invalid-feedback" id="terrainModalFreq1Error"></div>
                    </div>
                    <div class="col-6 mb-3">
                        <label for="terrainModalFreq2" class="form-label"><?= $this->lang->line('gvv_terrains_field_freq2') ?></label>
                        <input type="text" class="form-control" id="terrainModalFreq2" inputmode="decimal" autocomplete="off">
                        <div class="invalid-feedback" id="terrainModalFreq2Error"></div>
                    </div>
                </div>

                <div class="mb-2">
                    <label for="terrainModalComment" class="form-label"><?= $this->lang->line('gvv_terrains_field_comment') ?></label>
                    <textarea class="form-control" id="terrainModalComment" rows="2" maxlength="256"></textarea>
                </div>

                <p class="small text-muted mb-0"><?= $this->lang->line('gvv_terrains_ajax_help') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $this->lang->line('gvv_terrains_ajax_cancel') ?></button>
                <button type="button" class="btn btn-primary" id="terrainModalSubmit"><?= $this->lang->line('gvv_terrains_ajax_submit') ?></button>
            </div>
        </div>
    </div>
</div>
<script>
var terrain_modal_config = {
    url: <?= json_encode(site_url('terrains/ajax_create')) ?>,
    // ids des <select> d'aérodrome présents sur la page (décollage + atterrissage)
    selects: <?= json_encode(isset($terrain_modal_selects) ? $terrain_modal_selects : array()) ?>,
    messages: {
        network: <?= json_encode($this->lang->line('gvv_terrains_ajax_error_network')) ?>,
        save: <?= json_encode($this->lang->line('gvv_terrains_ajax_error_save')) ?>
    }
};
</script>
<script type="text/javascript" src="<?php echo js_url('terrain_modal'); ?>"></script>
