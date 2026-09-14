<!-- VIEW: application/views/ecritures/bs_index.php -->
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
 * Page "Autres écritures" : écritures guidées pour les opérations rares
 * (non couvertes par le menu Ecritures) et accès à l'écriture générale.
 * @see doc/design_notes/analyse_types_ecritures.md
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('compta');
$this->lang->load('tableaux_de_bord');
?>

<style>
.sub-card {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 0.75rem;
    transition: all 0.2s ease;
    height: 100%;
    background-color: #fff;
    position: relative;
    cursor: pointer;
}

.sub-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    border-color: #007bff;
}

.sub-card i {
    font-size: 1.5rem;
}

.sub-card .card-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0.5rem 0 0.25rem 0;
}

.sub-card .card-text {
    font-size: 0.75rem;
    margin-bottom: 0.5rem;
}

.sub-card .btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
}

.sub-card .account-route {
    font-family: var(--bs-font-monospace, monospace);
    font-size: 0.7rem;
}
</style>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-pen"></i>
                <?= $title ?>
            </h2>
            <p class="text-muted"><?= $this->lang->line('gvv_ecritures_autres_desc') ?></p>
        </div>
    </div>

    <div class="row g-2">

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-share-square text-primary"></i>
                <div class="card-title"><?= $this->lang->line('gvv_compta_title_repartition_helloasso') ?></div>
                <span class="badge bg-light text-dark border account-route">467 &rarr; 411</span>
                <div class="card-text text-muted"><?= $this->lang->line('gvv_compta_desc_repartition_helloasso') ?></div>
                <a href="<?= controller_url('compta/repartition_helloasso') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-exchange-alt text-info"></i>
                <div class="card-title"><?= $this->lang->line('gvv_compta_title_transfert_membre') ?></div>
                <span class="badge bg-light text-dark border account-route">411 &rarr; 411</span>
                <div class="card-text text-muted"><?= $this->lang->line('gvv_compta_desc_transfert_membre') ?></div>
                <a href="<?= controller_url('compta/transfert_membre') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-undo-alt text-warning"></i>
                <div class="card-title"><?= $this->lang->line('gvv_compta_title_remb_recette_vol') ?></div>
                <span class="badge bg-light text-dark border account-route">7xx &rarr; 411</span>
                <div class="card-text text-muted"><?= $this->lang->line('gvv_compta_desc_remb_recette_vol') ?></div>
                <a href="<?= controller_url('compta/remb_recette_vol') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-hand-holding-usd text-success"></i>
                <div class="card-title"><?= $this->lang->line('gvv_compta_title_remb_charges_membre') ?></div>
                <span class="badge bg-light text-dark border account-route">411 &rarr; 606</span>
                <div class="card-text text-muted"><?= $this->lang->line('gvv_compta_desc_remb_charges_membre') ?></div>
                <a href="<?= controller_url('compta/remb_charges_membre') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
            </div>
        </div>

        <?php if (has_role('super-tresorier') || $this->dx_auth->is_admin()) : ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center border-danger">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                <div class="card-title"><?= $this->lang->line('db_card_generic_entry') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_no_controls') ?></div>
                <a href="<?= controller_url('compta/create') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_creer') ?></a>
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>
