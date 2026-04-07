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
$this->load->view('bs_menu', array('is_planchiste' => $is_planchiste, 'is_auto_planchiste' => $is_auto_planchiste));
$this->load->view('bs_banner');

$this->lang->load('welcome');
$this->lang->load('tableaux_de_bord');
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
.section-card.maintenance { border-left-color: #6c757d; }

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
                <p class="text-muted"><?= $this->lang->line('db_greeting') ?><?= htmlspecialchars($user_name) ?></p>
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

    <?php
    // Calculé ici pour être disponible dans toutes les sections du dashboard
    $show_planeurs  = empty($section) || !empty($section['gestion_planeurs']);
    $show_avions    = empty($section) || !empty($section['gestion_avions']);
    $show_presences = empty($section) || !isset($section['show_presences']) || !empty($section['show_presences']);
    ?>

    <!-- Section Utilisateur (tous les utilisateurs) -->
    <div class="accordion-item section-card user">
        <h2 class="accordion-header" id="headingUser">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUser" aria-expanded="true" aria-controls="collapseUser">
                <i class="fas fa-user text-primary me-2"></i>
                <?= $this->lang->line('db_section_personal') ?>
            </button>
        </h2>
        <div id="collapseUser" class="accordion-collapse collapse show" aria-labelledby="headingUser" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">
                <?php if ($show_calendar && $show_presences): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_calendar') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_calendar') ?></div>
                        <a href="<?= controller_url('calendar') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
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
                <?php
                    $solde = isset($account['solde']) ? (float)$account['solde'] : null;
                    $solde_class = ($solde !== null && $solde < 0) ? 'text-danger fw-bold' : 'text-success fw-bold';
                ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-file-invoice-dollar <?= $solde !== null && $solde < 0 ? 'text-danger' : 'text-success' ?>"></i>
                        <div class="card-title"><?= $title ?></div>
                        <?php if ($solde !== null): ?>
                        <div class="card-text <?= $solde_class ?>"><?= euros($solde) ?></div>
                        <?php else: ?>
                        <div class="card-text text-muted"><?= translation('dashboard_consult') ?></div>
                        <?php endif; ?>
                        <a href="<?= controller_url('compta/mon_compte/' . $account['club']) ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
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
                        <a href="<?= controller_url('compta/mon_compte') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($show_avions): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane-departure text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_my_flights_plane') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_history') ?></div>
                        <a href="<?= controller_url('vols_avion/vols_du_pilote/' . $username) ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($show_planeurs): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_my_flights_glider') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_history') ?></div>
                        <a href="<?= controller_url('vols_planeur/vols_du_pilote/' . $username) ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-user-circle text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_my_info') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_profile') ?></div>
                        <a href="<?= controller_url('membre/edit/' . $username) ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_modifier') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-key text-danger"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_password') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_change') ?></div>
                        <a href="<?= controller_url('auth/change_password') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_modifier') ?></a>
                    </div>
                </div>

                <?php if ($ticket_management_active): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-ticket-alt text-secondary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_my_tickets') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_usage') ?></div>
                        <a href="<?= controller_url('tickets/soldes_pilote/' . $username) ?>" class="btn btn-secondary btn-sm"><?= $this->lang->line('db_btn_consulter') ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($this->config->item('gestion_formations') && !empty($user_formations)): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-graduation-cap text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_my_trainings') ?></div>
                        <div class="card-text text-muted"><?= count($user_formations) ?> formation(s)</div>
                        <a href="<?= controller_url('formation_progressions/mes_formations') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_consulter') ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($use_new_auth)): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-shield-alt text-danger"></i>
                        <div class="card-title"><?= translation('authorization_my_authorizations_title') ?></div>
                        <div class="card-text text-muted"><?= translation('authorization_my_authorizations_desc') ?></div>
                        <a href="<?= controller_url('membre/mes_autorisations') ?>" class="btn btn-danger btn-sm"><?= translation('dashboard_consult') ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($active_payment_section) || !empty($show_pay_cotisation_card)): ?>
                <?php
                    $active_section_name = (!empty($section['id']) && (int) $section['id'] > 0 && !empty($section['nom']))
                        ? $section['nom']
                        : '';
                ?>
                <!-- Sous-section Mes paiements -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2">
                        <i class="fas fa-credit-card me-1"></i>
                        <?= $this->lang->line('gvv_dashboard_payments_title') ?>
                    </h6>
                </div>

                <?php if (!empty($show_pay_cotisation_card)): ?>
                <!-- Payer ma cotisation -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-id-card text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('gvv_dashboard_pay_cotisation') ?></div>
                        <div class="card-text text-muted">
                            <?php if ($active_section_name !== ''): ?>
                                <?= sprintf($this->lang->line('gvv_dashboard_pay_section_active'), htmlspecialchars($active_section_name)) ?>
                            <?php else: ?>
                                <?= $this->lang->line('gvv_dashboard_pay_section_required') ?>
                            <?php endif; ?>
                        </div>
                        <a href="<?= controller_url('paiements_en_ligne/cotisation') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_payer') ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($active_payment_section)): ?>
                    <?php if ($active_payment_section['has_bar']): ?>
                    <!-- Payer mes notes de bar -->
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="sub-card text-center">
                            <i class="fas fa-coffee text-warning"></i>
                            <div class="card-title"><?= $this->lang->line('gvv_dashboard_pay_bar') ?></div>
                            <div class="card-text text-muted"><?= htmlspecialchars($active_payment_section['section_name']) ?></div>
                            <a href="<?= controller_url('paiements_en_ligne/bar_debit_solde') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_payer') ?></a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($active_payment_section['has_approvisio_par_cb']): ?>
                    <!-- Approvisionner mon compte (CB) -->
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="sub-card text-center">
                            <i class="fas fa-wallet text-success"></i>
                            <div class="card-title"><?= sprintf($this->lang->line('gvv_dashboard_provision_account'), htmlspecialchars($active_payment_section['section_name'])) ?></div>
                            <div class="card-text text-muted"><?= $this->lang->line('gvv_dashboard_provision_sub') ?></div>
                            <a href="<?= controller_url('paiements_en_ligne/demande') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_payer') ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>
        </div>
    </div>

    <!-- Gestion des vols -->
    <?php
    // Si aucune section active (vue "Toutes sections"), afficher toutes les activités
    // $show_planeurs et $show_avions sont déjà calculés plus haut (utilisés aussi dans la section utilisateur)
    $label_avions  = (!empty($section['libelle_menu_avions'])) ? $section['libelle_menu_avions'] : 'Avion';
    ?>
    <?php if ($show_planeurs || $show_avions): ?>
    <div class="accordion-item section-card flights">
        <h2 class="accordion-header" id="headingFlights">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFlights" aria-expanded="false" aria-controls="collapseFlights">
                <i class="fas fa-clipboard-list text-success me-2"></i>
                <?= $this->lang->line('db_section_flights') ?>
            </button>
        </h2>
        <div id="collapseFlights" class="accordion-collapse collapse" aria-labelledby="headingFlights" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">

                <?php if ($show_planeurs): ?>
                <!-- Sous-section Planeur -->
                <div class="col-12">
                    <h6 class="text-muted mb-2"><i class="fas fa-plane"></i> <?= $this->lang->line('db_sub_glider') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-list text-primary"></i>
                        <div class="card-title"><?= translation('gvv_menu_glider_list') ?></div>
                        <div class="card-text text-muted"><?= translation('dashboard_consult') ?></div>
                        <a href="<?= controller_url('vols_planeur/page') ?>" class="btn btn-primary btn-sm"><?= translation('gvv_button_open') ?></a>
                    </div>
                </div>

                <?php if ($is_planchiste) : ?>
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
                <?php endif; ?>
                <?php endif; ?>

                <?php if ($show_avions): ?>
                <!-- Sous-section activité motorisée (libellé défini par section) -->
                <div class="col-12 <?= $show_planeurs ? 'mt-3' : '' ?>">
                    <h6 class="text-muted mb-2"><i class="fas fa-plane-departure"></i> <?= htmlspecialchars($label_avions) ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-list text-primary"></i>
                        <div class="card-title"><?= translation('gvv_menu_airplane_list') ?></div>
                        <div class="card-text text-muted"><?= translation('dashboard_consult') ?></div>
                        <a href="<?= controller_url('vols_avion/page') ?>" class="btn btn-primary btn-sm"><?= translation('gvv_button_open') ?></a>
                    </div>
                </div>

                <?php if ($is_planchiste || $is_auto_planchiste) : ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plus text-success"></i>
                        <div class="card-title"><?= translation('gvv_menu_airplane_input') ?></div>
                        <div class="card-text text-muted"><?= translation('dashboard_new_flight') ?></div>
                        <a href="<?= controller_url('vols_avion/create') ?>" class="btn btn-success btn-sm"><?= translation('dashboard_input') ?></a>
                    </div>
                </div>
                <?php endif; ?>

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
                <?php endif; ?>

                <!-- Sous-section Vols de découverte -->
                <?php if ($this->config->item('gestion_vd') && has_vd_role()) : ?>
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-gift"></i> <?= $this->lang->line('db_sub_discovery') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-gift text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_discovery_flights') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_baptisms') ?></div>
                        <a href="<?= controller_url('vols_decouverte') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-clipboard-check text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_passenger_briefing') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_declarations') ?></div>
                        <a href="<?= controller_url('briefing_passager/admin_list') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($is_admin || $is_treasurer): ?>
    <!-- Section Trésorier -->
    <div class="accordion-item section-card treasurer">
        <h2 class="accordion-header" id="headingTreasurer">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTreasurer" aria-expanded="false" aria-controls="collapseTreasurer">
                <i class="fas fa-euro-sign text-warning me-2"></i>
                <?= $this->lang->line('db_section_treasury') ?>
            </button>
        </h2>
        <div id="collapseTreasurer" class="accordion-collapse collapse" aria-labelledby="headingTreasurer" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">
                <!-- Compta Menu -->
                <div class="col-12">
                    <h6 class="text-muted mb-2"><i class="fas fa-calculator"></i> <?= $this->lang->line('db_sub_accounting') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-university text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_bank_accounts') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_balances') ?></div>
                        <a href="<?= controller_url('comptes/balance/512?start_expanded=true') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>

                <?php if (has_role('club-admin') || has_role('tresorier')) : ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-book text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_journal') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_accounting') ?></div>
                        <a href="<?= controller_url('compta/page') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-balance-scale text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_balance') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_general') ?></div>
                        <a href="<?= controller_url('comptes/balance') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (has_role('ca')) : ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-user-check text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_pilot_accounts') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_balance') ?></div>
                        <a href="<?= controller_url('comptes/balance/411?start_expanded=true') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-chart-pie text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_result') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_synthesis') ?></div>
                        <a href="<?= controller_url('comptes/resultat') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-table text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_club_result') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_analytical') ?></div>
                        <a href="<?= controller_url('comptes/resultat_par_sections') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-calculator text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_bilan') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_accounting') ?></div>
                        <a href="<?= controller_url('comptes/bilan') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-shopping-bag text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_purchases') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_per_year') ?></div>
                        <a href="<?= controller_url('achats/list_per_year') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-money-bill-wave text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_treasury_flow') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_flow') ?></div>
                        <a href="<?= controller_url('comptes/tresorerie') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-paperclip text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_attachments') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_documents') ?></div>
                        <a href="<?= controller_url('attachments') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-tachometer-alt text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_accounting_dash') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_accounting') ?></div>
                        <a href="<?= controller_url('comptes/dashboard') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <?php if (has_role('tresorier')) : ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-download text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_import_operations') ?></div>
                        <div class="card-text text-muted">OpenFlyers</div>
                        <a href="<?= controller_url('openflyers/select_operations') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_importer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-check-double text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_import_balances') ?></div>
                        <div class="card-text text-muted">OpenFlyers</div>
                        <a href="<?= controller_url('openflyers/select_soldes') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_importer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-link text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_account_assoc') ?></div>
                        <div class="card-text text-muted">OpenFlyers</div>
                        <a href="<?= controller_url('associations_of/page') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-list-check text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_reconciliations') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_bank') ?></div>
                        <a href="<?= controller_url('rapprochements/select_releve') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (has_role('tresorier') || has_role('bureau') || $this->dx_auth->is_admin()): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-credit-card text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_online_payments') ?></div>
                        <div class="card-text text-muted">HelloAsso</div>
                        <a href="<?= controller_url('paiements_en_ligne/liste') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_consulter') ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (has_role('tresorier')) : ?>

                <!-- Ecritures Menu -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-pen"></i> <?= $this->lang->line('db_sub_entries') ?></h6>
                </div>

                <!-- Income entries -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-arrow-circle-down text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_revenues') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_revenues') ?></div>
                        <a href="<?= controller_url('compta/recettes') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-hand-holding-usd text-success"></i>
                        <div class="card-title"><?= translation("gvv_menu_entries_pilot_payment") ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_payment') ?></div>
                        <a href="<?= controller_url('compta/reglement_pilote') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-file-invoice text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_pilot_billing') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_invoice') ?></div>
                        <a href="<?= controller_url('compta/factu_pilote') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-coins text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_membership_entry') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_membership_fee') ?></div>
                        <a href="<?= controller_url('compta/saisie_cotisation') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-receipt text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_supplier_credit') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_credit') ?></div>
                        <a href="<?= controller_url('compta/avoir_fournisseur') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <!-- Expense entries -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-arrow-circle-up text-danger"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_expenses') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_charges') ?></div>
                        <a href="<?= controller_url('compta/depenses') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-money-check-alt text-danger"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_pilot_credit') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_expense_paid') ?></div>
                        <a href="<?= controller_url('compta/credit_pilote') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-undo-alt text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_pilot_debit') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_reimbursement') ?></div>
                        <a href="<?= controller_url('compta/debit_pilote') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-credit-card text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_use_credit') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_supplier') ?></div>
                        <a href="<?= controller_url('compta/utilisation_avoir_fournisseur') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <!-- Transfer entries -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-exchange-alt text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_transfer') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_transfer') ?></div>
                        <a href="<?= controller_url('compta/virement') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-piggy-bank text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_cash_deposit') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_payment_in') ?></div>
                        <a href="<?= controller_url('compta/depot_especes') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-money-bill-alt text-danger"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_cash_withdrawal') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_withdrawal') ?></div>
                        <a href="<?= controller_url('compta/retrait_liquide') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <!-- Capital reimbursement -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-coins text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_capital_repayment') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_capital') ?></div>
                        <a href="<?= controller_url('compta/remb_capital') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-university text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_loan_disbursement') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_loan_disbursement') ?></div>
                        <a href="<?= controller_url('compta/mise_a_disposition_emprunt') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <!-- Depreciation -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-tools text-secondary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_depreciation') ?></div>
                        <div class="card-text text-muted">68x / 281x</div>
                        <a href="<?= controller_url('compta/amortissement') ?>" class="btn btn-secondary btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <!-- Section Operations -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-building text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_section_collection') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_collection') ?></div>
                        <a href="<?= controller_url('compta/encaissement_pour_une_section') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-exchange-alt text-secondary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_section_transfer') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_transfer') ?></div>
                        <a href="<?= controller_url('compta/reversement_section') ?>" class="btn btn-secondary btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
                    </div>
                </div>

                <!-- Generic entry creation - moved to end -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_generic_entry') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_no_controls') ?></div>
                        <a href="<?= controller_url('compta/create') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_creer') ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($is_treasurer): ?>
                <!-- Configuration comptable -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-cog"></i> <?= $this->lang->line('db_sub_accounting_config') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-tags text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_rates') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_products') ?></div>
                        <a href="<?= controller_url('tarifs') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-th-list text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_chart_of_accounts') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_accounts') ?></div>
                        <a href="<?= controller_url('plan_comptable/page') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center" style="opacity: 0.5;">
                        <i class="fas fa-file-invoice text-secondary"></i>
                        <div class="card-title text-muted"><?= $this->lang->line('db_card_billing_config') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_coming_soon') ?></div>
                        <button class="btn btn-secondary btn-sm" disabled><?= $this->lang->line('db_btn_bientot') ?></button>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($this->config->item('gestion_formations') && (isset($can_view_formation) ? $can_view_formation : ($is_ca || $is_admin || $is_instructeur))): ?>
    <!-- Section Formation -->
    <div class="accordion-item section-card formation">
        <h2 class="accordion-header" id="headingFormation">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFormation" aria-expanded="false" aria-controls="collapseFormation">
                <i class="fas fa-graduation-cap text-primary me-2"></i>
                <?= $this->lang->line('db_section_training') ?>
            </button>
        </h2>
        <div id="collapseFormation" class="accordion-collapse collapse" aria-labelledby="headingFormation" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">

                <!-- Sous-section Instructeur -->
                <div class="col-12">
                    <h6 class="text-muted mb-2"><i class="fas fa-chalkboard-teacher"></i> <?= $this->lang->line('db_sub_instructor') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-user-graduate text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_trainings_mgmt') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                        <a href="<?= controller_url('formation_inscriptions') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-clipboard-check text-danger"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_solo_auth') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                        <a href="<?= controller_url('formation_autorisations_solo') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_retraining') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_sessions') ?></div>
                        <a href="<?= controller_url('formation_seances') ?>/libres" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-chalkboard text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_ground_sessions') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_theory') ?></div>
                        <a href="<?= controller_url('formation_seances_theoriques') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>

                <!-- Sous-section Responsable Pédagogique -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-user-tie"></i> <?= $this->lang->line('db_sub_training_manager') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-book text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_programs') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                        <a href="<?= controller_url('programmes') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-chart-bar text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_reports') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_synthesis') ?></div>
                        <a href="<?= controller_url('formation_rapports') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center" style="opacity: 0.5;">
                        <i class="fas fa-certificate text-secondary"></i>
                        <div class="card-title text-muted"><?= $this->lang->line('db_card_training_certs') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_coming_soon') ?></div>
                        <button class="btn btn-secondary btn-sm" disabled><?= $this->lang->line('db_btn_bientot') ?></button>
                    </div>
                </div>

            </div>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($is_mecano || $is_admin): ?>
    <!-- Section Maintenance et suivi de navigabilité -->
    <div class="accordion-item section-card maintenance">
        <h2 class="accordion-header" id="headingMaintenance">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaintenance" aria-expanded="false" aria-controls="collapseMaintenance">
                <i class="fas fa-wrench text-secondary me-2"></i>
                <?= $this->lang->line('db_section_maintenance') ?>
            </button>
        </h2>
        <div id="collapseMaintenance" class="accordion-collapse collapse" aria-labelledby="headingMaintenance" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center" style="opacity: 0.5;">
                        <i class="fas fa-clipboard-list text-secondary"></i>
                        <div class="card-title text-muted"><?= $this->lang->line('db_card_maintenance_prog') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_coming_soon') ?></div>
                        <button class="btn btn-secondary btn-sm" disabled><?= $this->lang->line('db_btn_bientot') ?></button>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center" style="opacity: 0.5;">
                        <i class="fas fa-tools text-secondary"></i>
                        <div class="card-title text-muted"><?= $this->lang->line('db_card_maintenance_ops') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_coming_soon') ?></div>
                        <button class="btn btn-secondary btn-sm" disabled><?= $this->lang->line('db_btn_bientot') ?></button>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center" style="opacity: 0.5;">
                        <i class="fas fa-shield-alt text-secondary"></i>
                        <div class="card-title text-muted"><?= $this->lang->line('db_card_airworthiness') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_coming_soon') ?></div>
                        <button class="btn btn-secondary btn-sm" disabled><?= $this->lang->line('db_btn_bientot') ?></button>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center" style="opacity: 0.5;">
                        <i class="fas fa-warehouse text-secondary"></i>
                        <div class="card-title text-muted"><?= $this->lang->line('db_card_fleet_mgmt') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_coming_soon') ?></div>
                        <button class="btn btn-secondary btn-sm" disabled><?= $this->lang->line('db_btn_bientot') ?></button>
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
                <?= $this->lang->line('db_section_admin_club') ?>
            </button>
        </h2>
        <div id="collapseAdminClub" class="accordion-collapse collapse" aria-labelledby="headingAdminClub" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-cog text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_club_config') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_club') ?></div>
                        <a href="<?= controller_url('config') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-cogs text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_parameters') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_configuration') ?></div>
                        <a href="<?= controller_url('configuration') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-road text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_airfields') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                        <a href="<?= controller_url('terrains/page') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_gliders') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_fleet') ?></div>
                        <a href="<?= controller_url('planeur/page') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane-departure text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_aircraft') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_fleet') ?></div>
                        <a href="<?= controller_url('avion/page') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-certificate text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_formation_certs') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_certificates') ?></div>
                        <a href="<?= controller_url('event/page') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>
            </div>

            <!-- Gestion des membres subsection -->
            <h5 class="mt-4 mb-3"><?= $this->lang->line('db_h5_member_management') ?></h5>
            <div class="row g-2">
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-users text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_members') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                        <a href="<?= controller_url('membre/page') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-id-card text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_memberships') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_per_year') ?></div>
                        <a href="<?= controller_url('licences/per_year') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-envelope text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_mailing_list') ?></div>
                        <div class="card-text text-muted">Emails</div>
                        <a href="<?= controller_url('email_lists') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-users text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_members_report') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_per_year') ?></div>
                        <a href="<?= controller_url('adherents_report') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_voir') ?></a>
                    </div>
                </div>
            </div>

            <!-- Gestion documentaire subsection -->
            <?php if ($this->config->item('gestion_documentaire')) : ?>
            <h5 class="mt-4 mb-3"><?= $this->lang->line('db_h5_doc_management') ?></h5>
            <div class="row g-2">
                <!-- Archived documents management -->
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-archive text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_archive') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_documents') ?></div>
                        <a href="<?= controller_url('archived_documents') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center" style="opacity: 0.5;">
                        <i class="fas fa-stamp text-secondary"></i>
                        <div class="card-title text-muted"><?= $this->lang->line('db_card_doc_approval') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_coming_soon') ?></div>
                        <button class="btn btn-secondary btn-sm" disabled><?= $this->lang->line('db_btn_bientot') ?></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-2">
            </div>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($is_admin || $is_backup_db): ?>
    <!-- Section Administration Système -->
    <div class="accordion-item section-card admin">
        <h2 class="accordion-header" id="headingAdminSys">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdminSys" aria-expanded="false" aria-controls="collapseAdminSys">
                <i class="fas fa-server text-danger me-2"></i>
                <?= $this->lang->line('db_section_admin_sys') ?>
            </button>
        </h2>
        <div id="collapseAdminSys" class="accordion-collapse collapse" aria-labelledby="headingAdminSys" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">

                <!-- Sous-section Base de données -->
                <div class="col-12">
                    <h6 class="text-muted mb-2"><i class="fas fa-database"></i> <?= $this->lang->line('db_sub_database') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-save text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_backup') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_data') ?></div>
                        <a href="<?= controller_url('admin/backup_form') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <?php if ($is_admin): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-undo text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_restore') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_card_restore') ?></div>
                        <a href="<?= controller_url('admin/restore') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-exchange-alt text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_migrations') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_sub_database') ?></div>
                        <a href="<?= controller_url('migration') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <!-- Sous-section Configuration -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-sliders-h"></i> <?= $this->lang->line('db_sub_config_section') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-users text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_users') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                        <a href="<?= controller_url('backend/users') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-user-tag text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_roles') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                        <a href="<?= controller_url('backend/roles') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-lock text-danger"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_permissions') ?></div>
                        <div class="card-text text-muted">URI</div>
                        <a href="<?= controller_url('backend/uri_permissions') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-user-cog text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_section_roles') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_per_user') ?></div>
                        <a href="<?= controller_url('user_roles_per_section') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-shield-alt text-danger"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_authorizations') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                        <a href="<?= controller_url('authorization') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-credit-card text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_online_payments') ?></div>
                        <div class="card-text text-muted">HelloAsso</div>
                        <a href="<?= controller_url('paiements_en_ligne/admin_config') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_configurer') ?></a>
                    </div>
                </div>

                <!-- Sous-section Organisation -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-sitemap"></i> <?= $this->lang->line('db_sub_organisation') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-layer-group text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_sections') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                        <a href="<?= controller_url('sections') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-list-alt text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_session_types') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_section_training') ?></div>
                        <a href="<?= controller_url('formation_types_seances') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <!-- Gestion documentaire subsection -->
                <?php if ($this->config->item('gestion_documentaire')) : ?>
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-folder-open"></i> <?= $this->lang->line('db_sub_doc_management') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-book text-secondary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_procedures') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_documentation') ?></div>
                        <a href="<?= controller_url('procedures') ?>" class="btn btn-secondary btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-file-alt text-danger"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_doc_types') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_regulatory') ?></div>
                        <a href="<?= controller_url('document_types') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sous-section Fichiers journaux -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-file-alt"></i> <?= $this->lang->line('db_sub_logs') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-file-alt text-secondary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_logs') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_logs') ?></div>
                        <a href="<?= controller_url('admin/logs') ?>" class="btn btn-secondary btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <?php endif; // $is_admin — end of admin-only subsections ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; // $is_admin || $is_backup_db ?>

    <?php if (isset($is_dev_authorized) && $is_dev_authorized): ?>
    <!-- Section Développement / Test (fpeignot only) -->
    <div class="accordion-item section-card admin">
        <h2 class="accordion-header" id="headingDevTest">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDevTest" aria-expanded="false" aria-controls="collapseDevTest">
                <i class="fas fa-flask text-warning me-2"></i>
                <?= $this->lang->line('db_section_dev') ?>
            </button>
        </h2>
        <div id="collapseDevTest" class="accordion-collapse collapse" aria-labelledby="headingDevTest" data-bs-parent="#dashboardAccordion">
        <div class="accordion-body">
            <div class="row g-2">
                <!-- Tests Section -->
                <div class="col-12">
                    <h6 class="text-muted mb-2"><i class="fas fa-vial"></i> <?= $this->lang->line('db_sub_tests') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-warning">
                        <i class="fas fa-info-circle text-primary"></i>
                        <div class="card-title">phpinfo()</div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_php_config') ?></div>
                        <a href="<?= controller_url('admin/info') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-warning">
                        <i class="fas fa-user-secret text-warning"></i>
                        <div class="card-title">Login As</div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_change_user') ?></div>
                        <a href="<?= controller_url('login_as') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-purple" style="border-left: 3px solid #6f42c1;">
                        <i class="fas fa-user-cog" style="color:#6f42c1;"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_new_auth') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_migration_user') ?></div>
                        <a href="<?= controller_url('authorization/new_auth_users') ?>" class="btn btn-sm" style="background-color:#6f42c1;color:#fff;"><?= $this->lang->line('db_btn_gerer') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-info">
                        <i class="fas fa-credit-card text-info"></i>
                        <div class="card-title">HelloAsso test</div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_sandbox') ?></div>
                        <a href="<?= controller_url('payments/test_helloasso') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <!-- Outils de Développement -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-wrench"></i> <?= $this->lang->line('db_sub_dev_tools') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-danger">
                        <i class="fas fa-unlock text-danger"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_decloture') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_decloture') ?></div>
                        <a href="<?= controller_url('comptes/decloture') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-success">
                        <i class="fas fa-file-archive text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_gen_test_db') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_encrypted_ci') ?></div>
                        <a href="<?= controller_url('admin/generate_test_database') ?>" class="btn btn-success btn-sm"
                           onclick="return confirm('<?= addslashes($this->lang->line('db_confirm_dev_env')) ?>');"><?= $this->lang->line('db_btn_generer') ?></a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center border-primary">
                        <i class="fas fa-file-code text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_gen_schema') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_for_install') ?></div>
                        <a href="<?= controller_url('admin/generate_initial_schema') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_generer') ?></a>
                    </div>
                </div>

                <!-- Cohérence de la Base de Données -->
                <div class="col-12 mt-3">
                    <h6 class="text-muted mb-2"><i class="fas fa-database"></i> <?= $this->lang->line('db_sub_db_consistency') ?></h6>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-pen text-primary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_entries_check') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_check') ?></div>
                        <a href="<?= controller_url('dbchecks') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_verifier') ?></a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane text-success"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_glider_flights_chk') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_consistency') ?></div>
                        <a href="<?= controller_url('dbchecks/volsp') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_verifier') ?></a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-plane-departure text-info"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_aircraft_flights_chk') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_consistency') ?></div>
                        <a href="<?= controller_url('dbchecks/volsa') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_verifier') ?></a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-shopping-cart text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_purchases_check') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_consistency') ?></div>
                        <a href="<?= controller_url('dbchecks/achats') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_verifier') ?></a>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-layer-group text-secondary"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_sections_check') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_consistency') ?></div>
                        <a href="<?= controller_url('dbchecks/sections') ?>" class="btn btn-secondary btn-sm"><?= $this->lang->line('db_btn_verifier') ?></a>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="sub-card text-center">
                        <i class="fas fa-link text-warning"></i>
                        <div class="card-title"><?= $this->lang->line('db_card_reconcil_check') ?></div>
                        <div class="card-text text-muted"><?= $this->lang->line('db_desc_orphan_links') ?></div>
                        <a href="<?= controller_url('dbchecks/associations_orphelines') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_verifier') ?></a>
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
