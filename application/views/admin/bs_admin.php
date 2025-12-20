<!-- VIEW: application/views/admin/bs_admin.php -->
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
 * Vue de la page d'administration - Dashboard responsive
 * @package vues
 * @filesource admin.php
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('admin');
?>

<style>
.section-card {
    border-left: 4px solid;
    margin-bottom: 1.5rem;
}
.section-card.config { border-left-color: #0d6efd; }
.section-card.admin { border-left-color: #198754; }
.section-card.rights { border-left-color: #ffc107; }
.section-card.tests { border-left-color: #6f42c1; }
.section-card.dev-tools { border-left-color: #fd7e14; }
.section-card.dbcheck { border-left-color: #dc3545; }

.sub-card {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 0.75rem;
    transition: all 0.2s ease;
    height: 100%;
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
</style>

<div id="body" class="body container-fluid py-3">

    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-tachometer-alt text-primary"></i>
                <?= $this->lang->line("gvv_admin_title") ?>
            </h2>
        </div>
    </div>

    <?php if (isset($is_dev_authorized) && $is_dev_authorized) : ?>
    <!-- Tests Section -->
    <div class="card section-card tests">
        <div class="card-header" style="background-color: rgba(111, 66, 193, 0.1);">
            <h5 class="mb-0">
                <i class="fas fa-vial" style="color: #6f42c1;"></i>
                <?= $this->lang->line("gvv_admin_title_tests") ?>
                <span class="badge bg-warning text-dark">Autorisé</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-warning">
                        <i class="fas fa-flask text-success"></i>
                        <div class="card-title"><?= $this->lang->line("gvv_admin_menu_ut") ?></div>
                        <div class="card-text text-muted">Tests unitaires</div>
                        <a href="<?= controller_url('tests') ?>" class="btn btn-success btn-sm">Accéder</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-warning">
                        <i class="fas fa-chart-pie text-info"></i>
                        <div class="card-title"><?= $this->lang->line("gvv_admin_menu_coverage") ?></div>
                        <div class="card-text text-muted">Couverture</div>
                        <a href="<?= controller_url('coverage') ?>" class="btn btn-info btn-sm">Accéder</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-warning">
                        <i class="fas fa-info-circle text-primary"></i>
                        <div class="card-title">phpinfo()</div>
                        <div class="card-text text-muted">Config PHP</div>
                        <a href="<?= controller_url('admin/info') ?>" class="btn btn-primary btn-sm">Accéder</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Outils de Développement Section -->
    <div class="card section-card dev-tools">
        <div class="card-header bg-danger bg-opacity-10">
            <h5 class="mb-0">
                <i class="fas fa-wrench text-danger"></i>
                Outils de développement
                <span class="badge bg-warning text-dark">Autorisé</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-user-secret text-warning"></i>
                        <div class="card-title">Anonymiser données</div>
                        <div class="card-text text-muted">Toutes les données</div>
                        <a href="<?= controller_url('admin/anonymize_all_data') ?>" class="btn btn-warning btn-sm"
                           onclick="return confirm('Cette action va anonymiser toutes les données personnelles. Continuer ?');">Exécuter</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-envelope text-secondary"></i>
                        <div class="card-title">Anonymiser emails</div>
                        <div class="card-text text-muted">API JSON</div>
                        <a href="<?= controller_url('backend/anonymize_all') ?>" class="btn btn-secondary btn-sm">API</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-info">
                        <i class="fas fa-database text-info"></i>
                        <div class="card-title">Extraire données test</div>
                        <div class="card-text text-muted">Pour Playwright</div>
                        <a href="<?= controller_url('admin/extract_test_data') ?>" class="btn btn-info btn-sm">Extraire</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-success">
                        <i class="fas fa-file-archive text-success"></i>
                        <div class="card-title">Générer base de test</div>
                        <div class="card-text text-muted">Chiffrée pour CI/CD</div>
                        <a href="<?= controller_url('admin/generate_test_database') ?>" class="btn btn-success btn-sm">Générer</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-primary">
                        <i class="fas fa-file-code text-primary"></i>
                        <div class="card-title">Schéma initial DB</div>
                        <div class="card-text text-muted">Pour installation</div>
                        <a href="<?= controller_url('admin/generate_initial_schema') ?>" class="btn btn-primary btn-sm">Générer</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cohérence de la Base de Données Section -->
    <div class="card section-card dbcheck">
        <div class="card-header bg-danger bg-opacity-10">
            <h5 class="mb-0">
                <i class="fas fa-database text-danger"></i>
                Cohérence de la base de données
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-pen text-primary"></i>
                        <div class="card-title">Écritures</div>
                        <div class="card-text text-muted">Vérifier</div>
                        <a href="<?= controller_url('dbchecks') ?>" class="btn btn-primary btn-sm">Vérifier</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane text-success"></i>
                        <div class="card-title">Vols planeur</div>
                        <div class="card-text text-muted">Cohérence</div>
                        <a href="<?= controller_url('dbchecks/volsp') ?>" class="btn btn-success btn-sm">Vérifier</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane-departure text-info"></i>
                        <div class="card-title">Vols avion</div>
                        <div class="card-text text-muted">Cohérence</div>
                        <a href="<?= controller_url('dbchecks/volsa') ?>" class="btn btn-info btn-sm">Vérifier</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-shopping-cart text-warning"></i>
                        <div class="card-title">Achats</div>
                        <div class="card-text text-muted">Cohérence</div>
                        <a href="<?= controller_url('dbchecks/achats') ?>" class="btn btn-warning btn-sm">Vérifier</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-wallet text-danger"></i>
                        <div class="card-title">Soldes</div>
                        <div class="card-text text-muted">Comptes</div>
                        <a href="<?= controller_url('dbchecks/soldes') ?>" class="btn btn-danger btn-sm">Vérifier</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-layer-group text-secondary"></i>
                        <div class="card-title">Sections</div>
                        <div class="card-text text-muted">Cohérence</div>
                        <a href="<?= controller_url('dbchecks/sections') ?>" class="btn btn-secondary btn-sm">Vérifier</a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-link text-warning"></i>
                        <div class="card-title">Rapprochements</div>
                        <div class="card-text text-muted">Associations orphelines</div>
                        <a href="<?= controller_url('dbchecks/associations_orphelines') ?>" class="btn btn-warning btn-sm">Vérifier</a>
                    </div>
                </div>
                <?php if (isset($is_dev_authorized) && $is_dev_authorized) : ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-warning">
                        <i class="fas fa-check-circle text-success"></i>
                        <div class="card-title">Cohérence comptes</div>
                        <div class="card-text text-muted">Vérifier soldes <span class="badge bg-warning text-dark" style="font-size: 0.6rem;">Autorisé</span></div>
                        <a href="<?= controller_url('comptes/check') ?>" class="btn btn-success btn-sm">Vérifier</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
