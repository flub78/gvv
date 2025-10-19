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

<div id="body" class="body container-fluid py-4">

    <!-- Header du Dashboard -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-3">
                <i class="fas fa-tachometer-alt text-primary"></i>
                <?= $this->lang->line("gvv_admin_title") ?>
            </h2>
            <p class="text-muted">Panneau de configuration et d'administration de GVV</p>
        </div>
    </div>

    <!-- Section Configuration -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-cog text-primary"></i>
                <?= $this->lang->line("gvv_admin_title_config") ?>
            </h4>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-cogs fa-3x text-primary mb-3"></i>
                    <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_config") ?></h5>
                    <p class="card-text text-muted">Configuration générale de l'application</p>
                    <a href="<?= controller_url('config') ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Accéder
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-certificate fa-3x text-success mb-3"></i>
                    <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_certificates") ?></h5>
                    <p class="card-text text-muted">Gestion des types d'événements</p>
                    <a href="<?= controller_url('events_types') ?>" class="btn btn-success">
                        <i class="fas fa-arrow-right"></i> Accéder
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Administration -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-tools text-danger"></i>
                <?= $this->lang->line("gvv_admin_title_admin") ?>
            </h4>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-database fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Sauvegardes</h5>
                    <p class="card-text text-muted">Gérer les sauvegardes de données</p>
                    <a href="<?= controller_url('admin/backup_form') ?>" class="btn btn-info">
                        <i class="fas fa-arrow-right"></i> Accéder
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-undo fa-3x text-warning mb-3"></i>
                    <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_restore") ?></h5>
                    <p class="card-text text-muted">Restaurer des données</p>
                    <a href="<?= controller_url('admin/restore') ?>" class="btn btn-warning">
                        <i class="fas fa-arrow-right"></i> Accéder
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-exchange-alt fa-3x text-primary mb-3"></i>
                    <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_migrate") ?></h5>
                    <p class="card-text text-muted">Migration de base de données</p>
                    <a href="<?= controller_url('migration') ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Accéder
                    </a>
                </div>
            </div>
        </div>

        <?php if (ENVIRONMENT == 'development') : ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm hover-shadow border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-file-code fa-3x text-secondary mb-3"></i>
                        <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_structure") ?></h5>
                        <p class="card-text text-muted">Backup structure (dev)</p>
                        <a href="<?= controller_url('admin/backup/structure') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm hover-shadow border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-file-alt fa-3x text-secondary mb-3"></i>
                        <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_default") ?></h5>
                        <p class="card-text text-muted">Backup défaut (dev)</p>
                        <a href="<?= controller_url('admin/backup/defaut') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm hover-shadow border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-lock fa-3x text-secondary mb-3"></i>
                        <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_lock") ?></h5>
                        <p class="card-text text-muted">Verrouillage (dev)</p>
                        <a href="<?= controller_url('welcome/nyi') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section Droits -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-shield-alt text-success"></i>
                <?= $this->lang->line("gvv_admin_title_rights") ?>
            </h4>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_users") ?></h5>
                    <p class="card-text text-muted">Gestion des utilisateurs</p>
                    <a href="<?= controller_url('backend/users') ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Accéder
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-user-tag fa-3x text-success mb-3"></i>
                    <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_roles") ?></h5>
                    <p class="card-text text-muted">Gestion des rôles</p>
                    <a href="<?= controller_url('backend/roles') ?>" class="btn btn-success">
                        <i class="fas fa-arrow-right"></i> Accéder
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                    <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_permissions") ?></h5>
                    <p class="card-text text-muted">Gestion des permissions</p>
                    <a href="<?= controller_url('backend/uri_permissions') ?>" class="btn btn-danger">
                        <i class="fas fa-arrow-right"></i> Accéder
                    </a>
                </div>
            </div>
        </div>

        <?php if (ENVIRONMENT == 'development') : ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm hover-shadow border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-user-lock fa-3x text-secondary mb-3"></i>
                        <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_custom_permissions") ?></h5>
                        <p class="card-text text-muted">Permissions personnalisées (dev)</p>
                        <a href="<?= controller_url('auth/custom_permissions') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (ENVIRONMENT == 'development') : ?>
    <!-- Section Tests (Dev only) -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-vial text-success"></i>
                <?= $this->lang->line("gvv_admin_title_tests") ?>
                <span class="badge bg-warning text-dark">Développement</span>
            </h4>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm hover-shadow border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-vial fa-3x text-success mb-3"></i>
                    <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_ut") ?></h5>
                    <p class="card-text text-muted">Tests unitaires</p>
                    <a href="<?= controller_url('tests') ?>" class="btn btn-success">
                        <i class="fas fa-arrow-right"></i> Accéder
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm hover-shadow border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-chart-pie fa-3x text-info mb-3"></i>
                    <h5 class="card-title"><?= $this->lang->line("gvv_admin_menu_coverage") ?></h5>
                    <p class="card-text text-muted">Couverture de code</p>
                    <a href="<?= controller_url('coverage') ?>" class="btn btn-info">
                        <i class="fas fa-arrow-right"></i> Accéder
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm hover-shadow border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-info-circle fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">phpinfo()</h5>
                    <p class="card-text text-muted">Informations PHP</p>
                    <a href="<?= controller_url('admin/info') ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Accéder
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm hover-shadow border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-book-open fa-3x text-secondary mb-3"></i>
                    <h5 class="card-title">phpdoc</h5>
                    <p class="card-text text-muted">Documentation API</p>
                    <a href="http://localhost/gvv2/phpdoc/" class="btn btn-secondary">
                        <i class="fas fa-external-link-alt"></i> Ouvrir
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Outils de développement -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-wrench text-warning"></i>
                Outils de développement
                <span class="badge bg-warning text-dark">Développement</span>
            </h4>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card h-100 shadow-sm hover-shadow border-danger">
                <div class="card-body text-center">
                    <i class="fas fa-user-secret fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Anonymiser toutes les données</h5>
                    <p class="card-text text-muted">Remplace les emails personnels par des données anonymisées</p>
                    <a href="<?= controller_url('admin/anonymize_all_data') ?>" class="btn btn-warning">
                        <i class="fas fa-exclamation-triangle"></i> Exécuter
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card h-100 shadow-sm hover-shadow border-danger">
                <div class="card-body text-center">
                    <i class="fas fa-envelope fa-3x text-secondary mb-3"></i>
                    <h5 class="card-title">Anonymiser emails (JSON)</h5>
                    <p class="card-text text-muted">API JSON pour anonymiser les emails utilisateurs</p>
                    <a href="<?= controller_url('backend/anonymize_all') ?>" class="btn btn-secondary">
                        <i class="fas fa-code"></i> API
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Section Cohérence BD -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-database text-info"></i>
                Cohérence de la base de données
            </h4>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-pen fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Écritures</h5>
                    <p class="card-text text-muted">Vérifier la cohérence des écritures</p>
                    <a href="<?= controller_url('dbchecks') ?>" class="btn btn-primary">
                        <i class="fas fa-check"></i> Vérifier
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-plane fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Vols planeur</h5>
                    <p class="card-text text-muted">Vérifier les vols planeur</p>
                    <a href="<?= controller_url('dbchecks/volsp') ?>" class="btn btn-success">
                        <i class="fas fa-check"></i> Vérifier
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-plane-departure fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Vols avion</h5>
                    <p class="card-text text-muted">Vérifier les vols avion</p>
                    <a href="<?= controller_url('dbchecks/volsa') ?>" class="btn btn-info">
                        <i class="fas fa-check"></i> Vérifier
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Achats</h5>
                    <p class="card-text text-muted">Vérifier les achats</p>
                    <a href="<?= controller_url('dbchecks/achats') ?>" class="btn btn-warning">
                        <i class="fas fa-check"></i> Vérifier
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-wallet fa-3x text-danger mb-3"></i>
                    <h5 class="card-title">Solde des comptes</h5>
                    <p class="card-text text-muted">Vérifier les soldes</p>
                    <a href="<?= controller_url('dbchecks/soldes') ?>" class="btn btn-danger">
                        <i class="fas fa-check"></i> Vérifier
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <i class="fas fa-layer-group fa-3x text-secondary mb-3"></i>
                    <h5 class="card-title">Sections</h5>
                    <p class="card-text text-muted">Vérifier les sections</p>
                    <a href="<?= controller_url('dbchecks/sections') ?>" class="btn btn-secondary">
                        <i class="fas fa-check"></i> Vérifier
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.card {
    border-radius: 10px;
}

.card-body i.fa-3x {
    transition: transform 0.3s ease;
}

.card:hover i.fa-3x {
    transform: scale(1.1);
}
</style>
