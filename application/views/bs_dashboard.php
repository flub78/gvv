<!-- VIEW: application/views/bs_dashboard.php -->
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
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Dashboard principal - Page d'accueil responsive
 * @package vues
 * @filesource bs_dashboard.php
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('welcome');
?>

<style>
.section-card {
    border-left: 4px solid;
    margin-bottom: 1.5rem;
}
.section-card.user { border-left-color: #0d6efd; }
.section-card.flights { border-left-color: #198754; }
.section-card.admin { border-left-color: #dc3545; }
.section-card.treasurer { border-left-color: #ffc107; }

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
    /* Slightly larger accordion titles */
    .accordion-button {
        font-size: 1.05rem; /* default ~1rem; bump slightly */
        font-weight: 700; /* bold */
    }
</style>

<div id="body" class="body container-fluid py-3">

    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-home text-primary"></i>
                <?= $this->lang->line("welcome_title") ?>
            </h2>
            <?php if (!empty($user_name)): ?>
                <p class="text-muted">Bonjour <?= htmlspecialchars($user_name) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Message du jour -->
    <?php if ($mod): ?>
    <!-- MOD Modal Dialog (hidden by default) -->
    <div id="mod_dialog" style="display:none" title="<?= $this->lang->line("gvv_config_mod") ?>">
        <div class="markdown-content">
            <?= markdown($mod) ?>
        </div>
        <div class="mt-3">
            <label>
                <input type="checkbox" name="no_mod" value="0" id="no_mod" class="form-check-input" />
                <small class="text-muted ms-1"><?= $this->lang->line("gvv_no_more_mod") ?></small>
            </label>
            <input type="hidden" name="mod_title" value="<?= $this->lang->line("gvv_config_mod") ?>" />
        </div>
    </div>
    <?php endif; ?>

    <div class="accordion" id="dashboardAccordion">

    <!-- Section Utilisateur (tous les utilisateurs) -->
    <div class="accordion-item section-card user">
        <h2 class="accordion-header" id="headingUser">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUser" aria-expanded="true" aria-controls="collapseUser">
                <i class="fas fa-user text-primary me-2"></i>
                Mon espace personnel
            </button>
        </h2>
        <div id="collapseUser" class="accordion-collapse collapse show" aria-labelledby="headingUser" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">
                <?php if ($show_calendar): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        <div class="card-title">Calendrier</div>
                        <div class="card-text text-muted">Présences planeur</div>
                        <a href="<?= controller_url('calendar') ?>" class="btn btn-primary btn-sm">Voir</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                $accounts = isset($user_accounts) ? $user_accounts : array();
                if (!empty($accounts)) :
                    foreach ($accounts as $account) :
                        $section_name = isset($account['section_name']) ? $account['section_name'] : $account['club'];
                        $title = translation('dashboard_my_account_section');
                        if ($title && strpos($title, '%s') !== false) {
                            $title = sprintf($title, $section_name);
                        } else {
                            $title = translation('dashboard_my_account') . ' - ' . $section_name;
                        }
                ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-file-invoice-dollar text-success"></i>
                        <div class="card-title"><?= $title ?></div>
                        <div class="card-text text-muted"><?= translation('dashboard_consult') ?></div>
                        <a href="<?= controller_url('compta/mon_compte/' . $account['club']) ?>" class="btn btn-success btn-sm">Accéder</a>
                    </div>
                </div>
                <?php
                    endforeach;
                else:
                ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-file-invoice-dollar text-success"></i>
                        <div class="card-title"><?= translation('dashboard_my_account') ?></div>
                        <div class="card-text text-muted"><?= translation('dashboard_consult') ?></div>
                        <a href="<?= controller_url('compta/mon_compte') ?>" class="btn btn-success btn-sm">Accéder</a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane-departure text-info"></i>
                        <div class="card-title">Mes vols avion/ULM</div>
                        <div class="card-text text-muted">Historique</div>
                        <a href="<?= controller_url('vols_avion/vols_du_pilote/' . $username) ?>" class="btn btn-info btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane text-success"></i>
                        <div class="card-title">Mes vols planeur</div>
                        <div class="card-text text-muted">Historique</div>
                        <a href="<?= controller_url('vols_planeur/vols_du_pilote/' . $username) ?>" class="btn btn-success btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-user-circle text-warning"></i>
                        <div class="card-title">Mes infos</div>
                        <div class="card-text text-muted">Profil</div>
                        <a href="<?= controller_url('membre/edit/' . $username) ?>" class="btn btn-warning btn-sm">Modifier</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-key text-danger"></i>
                        <div class="card-title">Mot de passe</div>
                        <div class="card-text text-muted">Changer</div>
                        <a href="<?= controller_url('auth/change_password') ?>" class="btn btn-danger btn-sm">Modifier</a>
                    </div>
                </div>

                <?php if ($ticket_management_active): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-ticket-alt text-secondary"></i>
                        <div class="card-title">Mes tickets</div>
                        <div class="card-text text-muted">Utilisation</div>
                        <a href="<?= controller_url('tickets/soldes_pilote/' . $username) ?>" class="btn btn-secondary btn-sm">Consulter</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($this->config->item('gestion_formations') && !empty($user_formations)): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-graduation-cap text-primary"></i>
                        <div class="card-title">Mes formations</div>
                        <div class="card-text text-muted"><?= count($user_formations) ?> formation(s)</div>
                        <a href="<?= controller_url('formation_progressions/mes_formations') ?>" class="btn btn-primary btn-sm">Consulter</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>

    <!-- Gestion des vols -->
    <div class="accordion-item section-card flights">
        <h2 class="accordion-header" id="headingFlights">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFlights" aria-expanded="false" aria-controls="collapseFlights">
                <i class="fas fa-clipboard-list text-success me-2"></i>
                Gestion des vols
            </button>
        </h2>
        <div id="collapseFlights" class="accordion-collapse collapse" aria-labelledby="headingFlights" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">
                <!-- Planeur section -->
                <div class="col-12">
                    <h6 class="text-muted mb-2"><i class="fas fa-plane"></i> Planeur</h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-list text-primary"></i>
                        <div class="card-title"><?= translation('gvv_menu_glider_list') ?></div>
                        <div class="card-text text-muted"><?= translation('dashboard_consult') ?></div>
                        <a href="<?= controller_url('vols_planeur/page') ?>" class="btn btn-primary btn-sm"><?= translation('gvv_button_open') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plus text-success"></i>
                        <div class="card-title"><?= translation('gvv_menu_glider_input') ?></div>
                        <div class="card-text text-muted"><?= translation('dashboard_new_flight') ?></div>
                        <a href="<?= controller_url('vols_planeur/create') ?>" class="btn btn-success btn-sm"><?= translation('dashboard_input') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-magic text-info"></i>
                        <div class="card-title"><?= translation('gvv_menu_glider_input_automatic') ?></div>
                        <div class="card-text text-muted"><?= translation('dashboard_quick_entry') ?></div>
                        <a href="<?= controller_url('vols_planeur/plancheauto_select') ?>" class="btn btn-info btn-sm"><?= translation('gvv_button_open') ?></a>
                    </div>
                </div>

                <!-- Avion section -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-plane-departure"></i> Avion</h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-list text-primary"></i>
                        <div class="card-title"><?= translation('gvv_menu_airplane_list') ?></div>
                        <div class="card-text text-muted"><?= translation('dashboard_consult') ?></div>
                        <a href="<?= controller_url('vols_avion/page') ?>" class="btn btn-primary btn-sm"><?= translation('gvv_button_open') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plus text-success"></i>
                        <div class="card-title"><?= translation('gvv_menu_airplane_input') ?></div>
                        <div class="card-text text-muted"><?= translation('dashboard_new_flight') ?></div>
                        <a href="<?= controller_url('vols_avion/create') ?>" class="btn btn-success btn-sm"><?= translation('dashboard_input') ?></a>
                    </div>
                </div>

                <?php if ($this->config->item('gestion_reservations')) : ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-clock text-info"></i>
                        <div class="card-title"><?= translation('gvv_menu_airplane_reservations') ?></div>
                        <div class="card-text text-muted"><?= translation('dashboard_timeline') ?></div>
                        <a href="<?= controller_url('reservations/timeline') ?>" class="btn btn-info btn-sm"><?= translation('gvv_button_open') ?></a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>

    <?php if ($is_bureau): ?>
    <!-- Section Trésorier -->
    <div class="accordion-item section-card treasurer">
        <h2 class="accordion-header" id="headingTreasurer">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTreasurer" aria-expanded="false" aria-controls="collapseTreasurer">
                <i class="fas fa-euro-sign text-warning me-2"></i>
                Trésorerie
            </button>
        </h2>
        <div id="collapseTreasurer" class="accordion-collapse collapse" aria-labelledby="headingTreasurer" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">
                <!-- Compta Menu -->
                <div class="col-12">
                    <h6 class="text-muted mb-2"><i class="fas fa-calculator"></i> Comptabilité</h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-university text-primary"></i>
                        <div class="card-title">Comptes bancaires</div>
                        <div class="card-text text-muted">Soldes</div>
                        <a href="<?= controller_url('comptes/balance/512?start_expanded=true') ?>" class="btn btn-primary btn-sm">Voir</a>
                    </div>
                </div>

                <?php if (has_role('bureau')) : ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-book text-primary"></i>
                        <div class="card-title">Journal</div>
                        <div class="card-text text-muted">Comptable</div>
                        <a href="<?= controller_url('compta/page') ?>" class="btn btn-primary btn-sm">Accéder</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-balance-scale text-info"></i>
                        <div class="card-title">Balance</div>
                        <div class="card-text text-muted">Générale</div>
                        <a href="<?= controller_url('comptes/balance') ?>" class="btn btn-info btn-sm">Voir</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (has_role('ca')) : ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-user-check text-success"></i>
                        <div class="card-title">Comptes pilotes</div>
                        <div class="card-text text-muted">Balance</div>
                        <a href="<?= controller_url('comptes/balance/411?start_expanded=true') ?>" class="btn btn-success btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-chart-pie text-warning"></i>
                        <div class="card-title">Résultat</div>
                        <div class="card-text text-muted">Synthèse</div>
                        <a href="<?= controller_url('comptes/resultat') ?>" class="btn btn-warning btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-table text-info"></i>
                        <div class="card-title">Résultat club</div>
                        <div class="card-text text-muted">Analytique</div>
                        <a href="<?= controller_url('comptes/resultat_par_sections') ?>" class="btn btn-info btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-calculator text-primary"></i>
                        <div class="card-title">Bilan</div>
                        <div class="card-text text-muted">Comptable</div>
                        <a href="<?= controller_url('comptes/bilan') ?>" class="btn btn-primary btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-shopping-bag text-success"></i>
                        <div class="card-title">Achats</div>
                        <div class="card-text text-muted">Par année</div>
                        <a href="<?= controller_url('achats/list_per_year') ?>" class="btn btn-success btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-money-bill-wave text-success"></i>
                        <div class="card-title">Trésorerie</div>
                        <div class="card-text text-muted">Flux</div>
                        <a href="<?= controller_url('comptes/tresorerie') ?>" class="btn btn-success btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-paperclip text-info"></i>
                        <div class="card-title">Pièces jointes</div>
                        <div class="card-text text-muted">Documents</div>
                        <a href="<?= controller_url('attachments') ?>" class="btn btn-info btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-tachometer-alt text-primary"></i>
                        <div class="card-title">Tableau de bord</div>
                        <div class="card-text text-muted">Comptable</div>
                        <a href="<?= controller_url('comptes/dashboard') ?>" class="btn btn-primary btn-sm">Accéder</a>
                    </div>
                </div>

                <?php if (has_role('tresorier')) : ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-download text-success"></i>
                        <div class="card-title">Import opérations</div>
                        <div class="card-text text-muted">OpenFlyers</div>
                        <a href="<?= controller_url('openflyers/select_operations') ?>" class="btn btn-success btn-sm">Importer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-check-double text-success"></i>
                        <div class="card-title">Import soldes</div>
                        <div class="card-text text-muted">OpenFlyers</div>
                        <a href="<?= controller_url('openflyers/select_soldes') ?>" class="btn btn-success btn-sm">Importer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-link text-primary"></i>
                        <div class="card-title">Associations comptes</div>
                        <div class="card-text text-muted">OpenFlyers</div>
                        <a href="<?= controller_url('associations_of/page') ?>" class="btn btn-primary btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-list-check text-warning"></i>
                        <div class="card-title">Rapprochements</div>
                        <div class="card-text text-muted">Bancaire</div>
                        <a href="<?= controller_url('rapprochements/select_releve') ?>" class="btn btn-warning btn-sm">Accéder</a>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (has_role('tresorier')) : ?>

                <!-- Ecritures Menu -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-pen"></i> Écritures</h6>
                </div>

                <!-- Income entries -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-arrow-circle-down text-success"></i>
                        <div class="card-title">Recettes</div>
                        <div class="card-text text-muted">Revenus</div>
                        <a href="<?= controller_url('compta/recettes') ?>" class="btn btn-success btn-sm">Saisir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-hand-holding-usd text-success"></i>
                        <div class="card-title">Règlement pilote</div>
                        <div class="card-text text-muted">Paiement</div>
                        <a href="<?= controller_url('compta/reglement_pilote') ?>" class="btn btn-success btn-sm">Saisir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-file-invoice text-info"></i>
                        <div class="card-title">Facturation pilote</div>
                        <div class="card-text text-muted">Facturer</div>
                        <a href="<?= controller_url('compta/factu_pilote') ?>" class="btn btn-info btn-sm">Saisir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-coins text-warning"></i>
                        <div class="card-title">Saisie cotisation</div>
                        <div class="card-text text-muted">Cotisation membre</div>
                        <a href="<?= controller_url('compta/saisie_cotisation') ?>" class="btn btn-warning btn-sm">Saisir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-receipt text-success"></i>
                        <div class="card-title">Avoir fournisseur</div>
                        <div class="card-text text-muted">Crédit</div>
                        <a href="<?= controller_url('compta/avoir_fournisseur') ?>" class="btn btn-success btn-sm">Saisir</a>
                    </div>
                </div>

                <!-- Expense entries -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-arrow-circle-up text-danger"></i>
                        <div class="card-title">Dépenses</div>
                        <div class="card-text text-muted">Charges</div>
                        <a href="<?= controller_url('compta/depenses') ?>" class="btn btn-danger btn-sm">Saisir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-money-check-alt text-danger"></i>
                        <div class="card-title">Crédit pilote</div>
                        <div class="card-text text-muted">Dépense payée</div>
                        <a href="<?= controller_url('compta/credit_pilote') ?>" class="btn btn-danger btn-sm">Saisir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-undo-alt text-warning"></i>
                        <div class="card-title">Débit pilote</div>
                        <div class="card-text text-muted">Remboursement</div>
                        <a href="<?= controller_url('compta/debit_pilote') ?>" class="btn btn-warning btn-sm">Saisir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-credit-card text-primary"></i>
                        <div class="card-title">Utilisation avoir</div>
                        <div class="card-text text-muted">Fournisseur</div>
                        <a href="<?= controller_url('compta/utilisation_avoir_fournisseur') ?>" class="btn btn-primary btn-sm">Saisir</a>
                    </div>
                </div>

                <!-- Transfer entries -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-exchange-alt text-info"></i>
                        <div class="card-title">Virement</div>
                        <div class="card-text text-muted">Transfert</div>
                        <a href="<?= controller_url('compta/virement') ?>" class="btn btn-info btn-sm">Saisir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-piggy-bank text-success"></i>
                        <div class="card-title">Dépôt espèces</div>
                        <div class="card-text text-muted">Versement</div>
                        <a href="<?= controller_url('compta/depot_especes') ?>" class="btn btn-success btn-sm">Saisir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-money-bill-alt text-danger"></i>
                        <div class="card-title">Retrait liquide</div>
                        <div class="card-text text-muted">Retrait</div>
                        <a href="<?= controller_url('compta/retrait_liquide') ?>" class="btn btn-danger btn-sm">Saisir</a>
                    </div>
                </div>

                <!-- Capital reimbursement -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-coins text-warning"></i>
                        <div class="card-title">Remb. capital</div>
                        <div class="card-text text-muted">Capital</div>
                        <a href="<?= controller_url('compta/remb_capital') ?>" class="btn btn-warning btn-sm">Saisir</a>
                    </div>
                </div>

                <!-- Section Operations -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-building text-info"></i>
                        <div class="card-title">Encaissement section</div>
                        <div class="card-text text-muted">Collection</div>
                        <a href="<?= controller_url('compta/encaissement_pour_une_section') ?>" class="btn btn-info btn-sm">Saisir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-exchange-alt text-secondary"></i>
                        <div class="card-title">Reversement section</div>
                        <div class="card-text text-muted">Transfert</div>
                        <a href="<?= controller_url('compta/reversement_section') ?>" class="btn btn-secondary btn-sm">Saisir</a>
                    </div>
                </div>

                <!-- Generic entry creation - moved to end -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        <div class="card-title">Ecriture générique</div>
                        <div class="card-text text-muted">Sans contrôles</div>
                        <a href="<?= controller_url('compta/create') ?>" class="btn btn-danger btn-sm">Créer</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($is_treasurer): ?>
                <!-- Configuration comptable -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-cog"></i> Configuration comptable</h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-tags text-success"></i>
                        <div class="card-title">Tarifs</div>
                        <div class="card-text text-muted">Produits</div>
                        <a href="<?= controller_url('tarifs') ?>" class="btn btn-success btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-th-list text-info"></i>
                        <div class="card-title">Plan comptable</div>
                        <div class="card-text text-muted">Comptes</div>
                        <a href="<?= controller_url('plan_comptable/page') ?>" class="btn btn-info btn-sm">Gérer</a>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($this->config->item('gestion_formations') && ($is_ca || $is_admin)): ?>
    <!-- Section Formation -->
    <div class="accordion-item section-card formation">
        <h2 class="accordion-header" id="headingFormation">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFormation" aria-expanded="false" aria-controls="collapseFormation">
                <i class="fas fa-graduation-cap text-primary me-2"></i>
                Formation
            </button>
        </h2>
        <div id="collapseFormation" class="accordion-collapse collapse" aria-labelledby="headingFormation" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-book text-primary"></i>
                        <div class="card-title">Programmes</div>
                        <div class="card-text text-muted">Gestion</div>
                        <a href="<?= controller_url('programmes') ?>" class="btn btn-primary btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-user-graduate text-success"></i>
                        <div class="card-title">Formations</div>
                        <div class="card-text text-muted">Gestion</div>
                        <a href="<?= controller_url('formation_inscriptions') ?>" class="btn btn-success btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane text-warning"></i>
                        <div class="card-title">Ré-entrainement</div>
                        <div class="card-text text-muted">Séances</div>
                        <a href="<?= controller_url('formation_seances') ?>/libres" class="btn btn-warning btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-chart-bar text-info"></i>
                        <div class="card-title">Rapports</div>
                        <div class="card-text text-muted">Synthèse</div>
                        <a href="<?= controller_url('formation_rapports') ?>" class="btn btn-info btn-sm">Voir</a>
                    </div>
                </div>

            </div>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($is_ca): ?>
    <!-- Section Administration Club -->
    <div class="accordion-item section-card admin">
        <h2 class="accordion-header" id="headingAdminClub">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdminClub" aria-expanded="false" aria-controls="collapseAdminClub">
                <i class="fas fa-cogs text-danger me-2"></i>
                Administration du club
            </button>
        </h2>
        <div id="collapseAdminClub" class="accordion-collapse collapse" aria-labelledby="headingAdminClub" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-cog text-primary"></i>
                        <div class="card-title">Configuration</div>
                        <div class="card-text text-muted">Club</div>
                        <a href="<?= controller_url('config') ?>" class="btn btn-primary btn-sm">Accéder</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-cogs text-info"></i>
                        <div class="card-title">Paramètres</div>
                        <div class="card-text text-muted">Configuration</div>
                        <a href="<?= controller_url('configuration') ?>" class="btn btn-info btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-shield-alt text-danger"></i>
                        <div class="card-title">Autorisations</div>
                        <div class="card-text text-muted">Gestion</div>
                        <a href="<?= controller_url('authorization') ?>" class="btn btn-danger btn-sm">Accéder</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-road text-success"></i>
                        <div class="card-title">Terrains</div>
                        <div class="card-text text-muted">Gestion</div>
                        <a href="<?= controller_url('terrains/page') ?>" class="btn btn-success btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane text-info"></i>
                        <div class="card-title">Planeurs</div>
                        <div class="card-text text-muted">Flotte</div>
                        <a href="<?= controller_url('planeur/page') ?>" class="btn btn-info btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane-departure text-warning"></i>
                        <div class="card-title">Avions</div>
                        <div class="card-text text-muted">Flotte</div>
                        <a href="<?= controller_url('avion/page') ?>" class="btn btn-warning btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-users text-primary"></i>
                        <div class="card-title">Membres</div>
                        <div class="card-text text-muted">Gestion</div>
                        <a href="<?= controller_url('membre/page') ?>" class="btn btn-primary btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-id-card text-success"></i>
                        <div class="card-title">Cotisations</div>
                        <div class="card-text text-muted">Par année</div>
                        <a href="<?= controller_url('licences/per_year') ?>" class="btn btn-success btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-envelope text-warning"></i>
                        <div class="card-title">Liste de diffusion</div>
                        <div class="card-text text-muted">Emails</div>
                        <a href="<?= controller_url('email_lists') ?>" class="btn btn-warning btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-certificate text-warning"></i>
                        <div class="card-title">Formation</div>
                        <div class="card-text text-muted">Certificats</div>
                        <a href="<?= controller_url('event/page') ?>" class="btn btn-warning btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-chart-bar text-primary"></i>
                        <div class="card-title">Rapports</div>
                        <div class="card-text text-muted">Club</div>
                        <a href="<?= controller_url('welcome/ca') ?>" class="btn btn-primary btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-users text-success"></i>
                        <div class="card-title">Adhérents</div>
                        <div class="card-text text-muted">Par âge</div>
                        <a href="<?= controller_url('adherents_report') ?>" class="btn btn-success btn-sm">Voir</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-history text-info"></i>
                        <div class="card-title">Historique</div>
                        <div class="card-text text-muted">Événements</div>
                        <a href="<?= controller_url('historique') ?>" class="btn btn-info btn-sm">Consulter</a>
                    </div>
                </div>

                <!-- Vols découverte management -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-gift text-success"></i>
                        <div class="card-title">Vols découverte</div>
                        <div class="card-text text-muted">Baptêmes</div>
                        <a href="<?= controller_url('vols_decouverte') ?>" class="btn btn-success btn-sm">Gérer</a>
                    </div>
                </div>

                <!-- Procedures management -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-book text-secondary"></i>
                        <div class="card-title">Procédures</div>
                        <div class="card-text text-muted">Documentation</div>
                        <a href="<?= controller_url('procedures') ?>" class="btn btn-secondary btn-sm">Gérer</a>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($is_admin): ?>
    <!-- Section Administration Système -->
    <div class="accordion-item section-card admin">
        <h2 class="accordion-header" id="headingAdminSys">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdminSys" aria-expanded="false" aria-controls="collapseAdminSys">
                <i class="fas fa-server text-danger me-2"></i>
                Administration système
            </button>
        </h2>
        <div id="collapseAdminSys" class="accordion-collapse collapse" aria-labelledby="headingAdminSys" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-save text-primary"></i>
                        <div class="card-title">Sauvegarde</div>
                        <div class="card-text text-muted">Données</div>
                        <a href="<?= controller_url('admin/backup_form') ?>" class="btn btn-primary btn-sm">Accéder</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-undo text-warning"></i>
                        <div class="card-title">Restauration</div>
                        <div class="card-text text-muted">Restore</div>
                        <a href="<?= controller_url('admin/restore') ?>" class="btn btn-warning btn-sm">Accéder</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-exchange-alt text-info"></i>
                        <div class="card-title">Migrations</div>
                        <div class="card-text text-muted">Base de données</div>
                        <a href="<?= controller_url('migration') ?>" class="btn btn-info btn-sm">Accéder</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-users text-primary"></i>
                        <div class="card-title">Utilisateurs</div>
                        <div class="card-text text-muted">Gestion</div>
                        <a href="<?= controller_url('backend/users') ?>" class="btn btn-primary btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-user-tag text-info"></i>
                        <div class="card-title">Rôles</div>
                        <div class="card-text text-muted">Gestion</div>
                        <a href="<?= controller_url('backend/roles') ?>" class="btn btn-info btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-lock text-danger"></i>
                        <div class="card-title">Permissions</div>
                        <div class="card-text text-muted">URI</div>
                        <a href="<?= controller_url('backend/uri_permissions') ?>" class="btn btn-danger btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-layer-group text-success"></i>
                        <div class="card-title">Sections</div>
                        <div class="card-text text-muted">Gestion</div>
                        <a href="<?= controller_url('sections') ?>" class="btn btn-success btn-sm">Gérer</a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-user-cog text-warning"></i>
                        <div class="card-title">Rôles sections</div>
                        <div class="card-text text-muted">Par utilisateur</div>
                        <a href="<?= controller_url('user_roles_per_section') ?>" class="btn btn-warning btn-sm">Gérer</a>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($is_dev_authorized) && $is_dev_authorized): ?>
    <!-- Section Développement / Test (fpeignot only) -->
    <div class="accordion-item section-card admin">
        <h2 class="accordion-header" id="headingDevTest">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDevTest" aria-expanded="false" aria-controls="collapseDevTest">
                <i class="fas fa-flask text-warning me-2"></i>
                Développement & Tests
            </button>
        </h2>
        <div id="collapseDevTest" class="accordion-collapse collapse" aria-labelledby="headingDevTest" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">
                <!-- Tests Section -->
                <div class="col-12">
                    <h6 class="text-muted mb-2"><i class="fas fa-vial"></i> Tests</h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-warning">
                        <i class="fas fa-flask text-success"></i>
                        <div class="card-title">Tests unitaires</div>
                        <div class="card-text text-muted">PHPUnit</div>
                        <a href="<?= controller_url('tests_ciunit') ?>" class="btn btn-success btn-sm">Accéder</a>
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

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-warning">
                        <i class="fas fa-user-secret text-warning"></i>
                        <div class="card-title">Login As</div>
                        <div class="card-text text-muted">Changer d'utilisateur</div>
                        <a href="<?= controller_url('login_as') ?>" class="btn btn-warning btn-sm">Accéder</a>
                    </div>
                </div>

                <!-- Outils de Développement -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-wrench"></i> Outils de développement</h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-warning">
                        <i class="fas fa-user-secret text-warning"></i>
                        <div class="card-title">Anonymiser données</div>
                        <div class="card-text text-muted">Toutes les données</div>
                        <a href="<?= controller_url('admin/anonymize_all_data') ?>" class="btn btn-warning btn-sm"
                           onclick="return confirm('Cette action va anonymiser toutes les données personnelles. Continuer ?');">Exécuter</a>
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

                <!-- Cohérence de la Base de Données -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-database"></i> Cohérence de la base de données</h6>
                </div>

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
            </div>
        </div>
        </div>
    </div>
    <?php endif; ?>

    </div> <!-- end accordion -->

</div>

<!-- CSS for responsive MOD dialog -->
<style>
/* Ensure dialog never exceeds viewport width */
.ui-dialog {
    max-width: 90vw !important;
    box-sizing: border-box;
}

/* Make dialog content responsive */
#mod_dialog {
    max-width: 100%;
    overflow-x: hidden;
    word-wrap: break-word;
}

/* Ensure images display properly in MOD dialog */
#mod_dialog img {
    display: block !important;
    max-width: 100% !important;
    height: auto !important;
    margin: 10px auto !important;
    visibility: visible !important;
}

#mod_dialog .markdown-content img {
    display: block !important;
    visibility: visible !important;
}

/* Ensure links are highly visible and clickable in MOD dialog */
#mod_dialog a,
#mod_dialog .markdown-content a {
    color: #007bff !important;
    text-decoration: underline !important;
    cursor: pointer !important;
    font-weight: 500 !important;
}

#mod_dialog a:hover,
#mod_dialog .markdown-content a:hover {
    color: #0056b3 !important;
    text-decoration: underline !important;
    font-weight: 600 !important;
}

/* Ensure dialog appears above navbar */
.ui-dialog {
    z-index: 9999 !important;
}

.ui-widget-overlay {
    z-index: 9998 !important;
}

/* Responsive adjustments for small screens */
@media (max-width: 768px) {
    .ui-dialog {
        margin: 10px !important;
        /* Ensure dialog doesn't go under fixed navbar */
        top: 70px !important;
        max-height: calc(100vh - 80px) !important;
    }

    .ui-dialog .ui-dialog-content {
        padding: 10px !important;
        max-height: calc(100vh - 180px) !important;
        overflow-y: auto !important;
    }

    .ui-dialog .ui-dialog-buttonpane {
        padding: 5px 10px !important;
    }

    /* Make dialog title bar more compact on mobile */
    .ui-dialog .ui-dialog-titlebar {
        padding: 8px 10px !important;
    }
}
</style>

<!-- JavaScript for MOD dialog handling and accordion state persistence -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Accordion state persistence
    const accordionElement = document.getElementById('dashboardAccordion');
    if (accordionElement) {
        const STORAGE_KEY = 'gvv_dashboard_accordion_state';
        
        // Function to save accordion state
        function saveAccordionState() {
            const collapseElements = accordionElement.querySelectorAll('.accordion-collapse');
            const state = {};
            
            collapseElements.forEach(function(element) {
                const isExpanded = element.classList.contains('show');
                state[element.id] = isExpanded;
            });
            
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        }
        
        // Function to restore accordion state
        function restoreAccordionState() {
            const savedState = sessionStorage.getItem(STORAGE_KEY);
            if (savedState) {
                try {
                    const state = JSON.parse(savedState);
                    
                    Object.keys(state).forEach(function(elementId) {
                        const element = document.getElementById(elementId);
                        const button = document.querySelector('[data-bs-target="#' + elementId + '"]');
                        
                        if (element && button) {
                            if (state[elementId]) {
                                // Should be expanded
                                element.classList.add('show');
                                button.classList.remove('collapsed');
                                button.setAttribute('aria-expanded', 'true');
                            } else {
                                // Should be collapsed
                                element.classList.remove('show');
                                button.classList.add('collapsed');
                                button.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                } catch (e) {
                    console.warn('Failed to restore accordion state:', e);
                    // Clear corrupted state
                    sessionStorage.removeItem(STORAGE_KEY);
                }
            }
        }
        
        // Listen for accordion state changes
        accordionElement.addEventListener('shown.bs.collapse', saveAccordionState);
        accordionElement.addEventListener('hidden.bs.collapse', saveAccordionState);
        
        // Restore state after a short delay to ensure Bootstrap has initialized
        setTimeout(restoreAccordionState, 100);
    }
    
    // Initialize MOD dialog if it exists
    const modDialog = document.getElementById('mod_dialog');
    if (modDialog) {
        // Shared function to handle "don't show again" checkbox
        function handleDontShowAgain() {
            const noModCheckbox = document.getElementById('no_mod');
            if (noModCheckbox && noModCheckbox.checked) {
                // Set cookie to hide MOD
                fetch('<?= controller_url("welcome/set_cookie") ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'OK') {
                            console.log('MOD cookie set successfully');
                        }
                    })
                    .catch(error => {
                        console.error('Error setting MOD cookie:', error);
                    });
            }
        }

        // Function to calculate responsive width
        function getResponsiveWidth() {
            return Math.min(600, window.innerWidth * 0.9);
        }

        // Calculate initial responsive width for modal
        const modalWidth = getResponsiveWidth();

        // Initialize jQuery UI dialog
        $('#mod_dialog').dialog({
            modal: true,
            width: modalWidth,
            height: 'auto',
            resizable: true,
            draggable: true,
            closeOnEscape: true,
            buttons: {
                "OK": function() {
                    // Handle "don't show again" checkbox
                    handleDontShowAgain();
                    $(this).dialog('close');
                }
            },
            close: function() {
                // Handle "don't show again" checkbox when closed via X button or Escape
                handleDontShowAgain();
            },
            // Ensure dialog is centered
            position: { my: "center", at: "center", of: window }
        });

        // Handle window resize to keep dialog responsive
        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if ($('#mod_dialog').dialog('isOpen')) {
                    const newWidth = getResponsiveWidth();
                    $('#mod_dialog').dialog('option', 'width', newWidth);
                    $('#mod_dialog').dialog('option', 'position', { my: "center", at: "center", of: window });
                }
            }, 250);
        });

        // Show dialog on page load
        $('#mod_dialog').dialog('open');

        // Debug image loading in MOD dialog
        $('#mod_dialog img').on('error', function() {
            console.error('Failed to load image:', this.src);
            console.error('Image element:', this);
            // Try to fix protocol mismatch (http vs https)
            if (this.src.startsWith('http://') && window.location.protocol === 'https:') {
                console.warn('Mixed content detected - attempting to fix by using https');
                this.src = this.src.replace('http://', 'https://');
            }
        });

        $('#mod_dialog img').on('load', function() {
            console.log('Successfully loaded image:', this.src);
        });

        // Log all images found in dialog
        console.log('Images in MOD dialog:', $('#mod_dialog img').length);
        $('#mod_dialog img').each(function() {
            console.log('Image src:', this.src, 'Complete:', this.complete, 'Natural width:', this.naturalWidth);
        });

        // Debug links in MOD dialog
        console.log('Links in MOD dialog:', $('#mod_dialog a').length);
        $('#mod_dialog a').each(function() {
            console.log('Link href:', this.href, 'Text:', this.textContent);
        });

        // Ensure links open in new tab for external links
        $('#mod_dialog a').each(function() {
            const link = $(this);
            const href = link.attr('href');

            // External links open in new tab
            if (href && (href.startsWith('http://') || href.startsWith('https://'))) {
                link.attr('target', '_blank');
                link.attr('rel', 'noopener noreferrer');
            }
        });
    }
});
</script>
