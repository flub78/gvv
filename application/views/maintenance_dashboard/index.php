<?php
/**
 * Vue : dashboard maintenance dedie, regroupe toutes les cartes du module (Etape 5.7)
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-wrench" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_dashboard_title') ?>
        </h3>
    </div>

    <div class="row g-2">
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-cogs text-primary"></i>
                <div class="card-title"><?= $this->lang->line('db_card_maintenance_equipements') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                <a href="<?= controller_url('maintenance_equipements') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-clipboard-list text-primary"></i>
                <div class="card-title"><?= $this->lang->line('db_card_maintenance_prog') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                <a href="<?= controller_url('maintenance_programmes') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-folder-open text-success"></i>
                <div class="card-title"><?= $this->lang->line('db_card_maintenance_dossiers') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                <a href="<?= controller_url('maintenance_dossiers') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-tools text-warning"></i>
                <div class="card-title"><?= $this->lang->line('db_card_maintenance_ops') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                <a href="<?= controller_url('maintenance_operations') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-bell text-danger"></i>
                <div class="card-title"><?= $this->lang->line('db_card_maintenance_bulletins') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                <a href="<?= controller_url('maintenance_bulletins') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-shield-alt text-info"></i>
                <div class="card-title"><?= $this->lang->line('db_card_maintenance_synthese') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_synthesis') ?></div>
                <a href="<?= controller_url('maintenance_synthese') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
            </div>
        </div>
    </div>

</div>
<?php $this->load->view('bs_footer'); ?>
