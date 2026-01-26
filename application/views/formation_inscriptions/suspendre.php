<!-- VIEW: application/views/formation_inscriptions/suspendre.php -->
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
 * Vue dialogue suspension d'inscription
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
            <i class="fas fa-pause" aria-hidden="true"></i>
            <?= $this->lang->line("formation_inscription_suspendre_title") ?>
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
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <strong><?= $this->lang->line("formation_inscription_suspendre_confirm") ?></strong>
            </div>

            <?= form_open(controller_url($controller) . '/suspendre/' . $inscription['id']) ?>

                <div class="mb-3">
                    <label for="motif" class="form-label">
                        <?= $this->lang->line("formation_inscription_motif_suspension") ?>
                        <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="motif" name="motif" rows="4" required 
                              placeholder="Ex: Blessure, interruption temporaire pour raisons personnelles..."><?= set_value('motif') ?></textarea>
                    <div class="form-text">
                        Indiquez la raison de la suspension
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-pause" aria-hidden="true"></i> <?= $this->lang->line("formation_inscription_suspendre_confirm_btn") ?>
                    </button>
                    <a href="<?= controller_url($controller) ?>/detail/<?= $inscription['id'] ?>" class="btn btn-secondary">
                        <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line("gvv_str_cancel") ?>
                    </a>
                </div>

            <?= form_close() ?>
        </div>
    </div>
</div>

<?php $this->load->view('bs_footer'); ?>
