<!-- VIEW: application/views/bs_sub_dashboard.php -->
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
 * Sub-dashboard - Affiche les cartes d'une section du tableau de bord
 * @package vues
 * @filesource bs_sub_dashboard.php
 */

$this->load->view('bs_header');
$this->load->view('bs_menu', array(
    'is_planchiste'      => $is_planchiste,
    'is_auto_planchiste' => $is_auto_planchiste,
    'is_pilote_rem'      => isset($is_pilote_rem) ? $is_pilote_rem : false,
));
$this->load->view('bs_banner');

$this->lang->load('welcome');
$this->lang->load('tableaux_de_bord');

$show_planeurs  = empty($section) || !empty($section['gestion_planeurs']);
$show_avions    = empty($section) || !empty($section['gestion_avions']);
$show_presences = empty($section) || !isset($section['show_presences']) || !empty($section['show_presences']);
$label_avions   = (!empty($section['libelle_menu_avions'])) ? $section['libelle_menu_avions'] : 'Avion';

$section_meta = array(
    'user'       => array('icon' => 'fas fa-user text-primary',          'title' => $this->lang->line('db_section_personal')),
    'flights'    => array('icon' => 'fas fa-clipboard-list text-success', 'title' => $this->lang->line('db_section_flights')),
    'treasurer'  => array('icon' => 'fas fa-euro-sign text-warning',      'title' => $this->lang->line('db_section_treasury')),
    'formation'  => array('icon' => 'fas fa-graduation-cap text-primary', 'title' => $this->lang->line('db_section_training')),
    'maintenance'=> array('icon' => 'fas fa-wrench text-secondary',       'title' => $this->lang->line('db_section_maintenance')),
    'admin_club' => array('icon' => 'fas fa-cogs text-danger',            'title' => $this->lang->line('db_section_admin_club')),
    'admin_sys'  => array('icon' => 'fas fa-server text-danger',          'title' => $this->lang->line('db_section_admin_sys')),
    'dev'        => array('icon' => 'fas fa-flask text-warning',          'title' => $this->lang->line('db_section_dev')),
);

$meta = isset($section_meta[$dashboard_section]) ? $section_meta[$dashboard_section] : array('icon' => 'fas fa-th', 'title' => '');
?>

<style>
.sub-card {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 0.75rem;
    transition: all 0.2s ease;
    height: 100%;
    background-color: #fff;
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

    <div class="row mb-3">
        <div class="col-12">
            <a href="<?= controller_url('welcome') ?>" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="fas fa-arrow-left me-1"></i><?= $this->lang->line('db_btn_retour') ?>
            </a>
            <h2 class="mb-1">
                <i class="<?= $meta['icon'] ?>"></i>
                <?= $meta['title'] ?>
            </h2>
        </div>
    </div>

    <?php if ($dashboard_section === 'user'): ?>
    <!-- ================================================================
         Section Utilisateur
         ================================================================ -->
    <div class="row g-2">
        <?php if ($show_calendar && $show_presences && $show_planeurs): ?>
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

        <?php if ($this->config->item('gestion_documentaire')): ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-archive text-info"></i>
                <div class="card-title"><?= translation('archived_documents_my_documents') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_documents') ?></div>
                <a href="<?= controller_url('archived_documents/my_documents') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-id-badge text-success"></i>
                <div class="card-title"><?= $this->lang->line('db_card_my_member_card') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_my_member_card') ?></div>
                <a href="<?= controller_url('cartes_membre/carte') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
            </div>
        </div>

        <?php if (!empty($active_payment_section) || !empty($show_pay_cotisation_card)): ?>
        <?php
            $active_section_name = (!empty($section['id']) && (int) $section['id'] > 0 && !empty($section['nom']))
                ? $section['nom']
                : '';
        ?>
        <div class="col-12 mt-3">
            <h6 class="text-muted mb-2">
                <i class="fas fa-credit-card me-1"></i>
                <?= $this->lang->line('gvv_dashboard_payments_title') ?>
            </h6>
        </div>

        <?php if (!empty($show_pay_cotisation_card)): ?>
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

    <?php elseif ($dashboard_section === 'flights'): ?>
    <!-- ================================================================
         Section Gestion des vols
         ================================================================ -->
    <div class="row g-2">
        <?php if ($show_planeurs): ?>
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

        <?php if ($is_planchiste || $is_auto_planchiste || (isset($is_pilote_rem) && $is_pilote_rem && $show_planeurs)) : ?>
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

        <?php if ($this->config->item('gestion_vd') && has_vd_role()) : ?>
        <?php $current_section_id = (int) $this->session->userdata('section'); ?>
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
                <i class="fas fa-plus-circle text-warning"></i>
                <div class="card-title"><?= $this->lang->line('db_card_sell_voucher') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_sell_voucher') ?></div>
                <a href="<?= controller_url('vols_decouverte/create') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
            </div>
        </div>

        <?php if ($current_section_id > 0) : ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-globe text-info"></i>
                <div class="card-title"><?= $this->lang->line('db_card_public_page') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_public_page') ?></div>
                <a href="<?= site_url('vols_decouverte/public_vd?section=' . $current_section_id) ?>" class="btn btn-info btn-sm" target="_blank" rel="noopener noreferrer"><?= $this->lang->line('gvv_button_open') ?></a>
            </div>
        </div>
        <?php endif; ?>

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

    <?php elseif ($dashboard_section === 'treasurer'): ?>
    <!-- ================================================================
         Section Trésorerie
         ================================================================ -->
    <div class="row g-2">
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

        <?php if (has_role('club-admin') || has_role('tresorier') || ($this->config->item('tresorers_can_access_others_sections') && $is_treasurer)) : ?>
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
        <div class="col-12 mt-3">
            <h6 class="text-muted mb-2"><i class="fas fa-pen"></i> <?= $this->lang->line('db_sub_entries') ?></h6>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-credit-card text-primary"></i>
                <div class="card-title"><?= $this->lang->line('gvv_paiement_generique_menu') ?></div>
                <div class="card-text text-muted">HelloAsso</div>
                <a href="<?= controller_url('paiements_en_ligne/paiement_generique') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
            </div>
        </div>

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
                <div class="card-title"><?= $this->lang->line('db_card_pilot_payment') ?></div>
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

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-tools text-secondary"></i>
                <div class="card-title"><?= $this->lang->line('db_card_depreciation') ?></div>
                <div class="card-text text-muted">(68 - 281)</div>
                <a href="<?= controller_url('compta/amortissement') ?>" class="btn btn-secondary btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
            </div>
        </div>

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
                <div class="card-text text-muted">(467 - 512)</div>
                <a href="<?= controller_url('compta/reversement_section') ?>" class="btn btn-secondary btn-sm"><?= $this->lang->line('db_btn_saisir') ?></a>
            </div>
        </div>

        <?php if (has_role('super-tresorier') || $this->dx_auth->is_admin()): ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center border-danger">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                <div class="card-title"><?= $this->lang->line('db_card_generic_entry') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_no_controls') ?></div>
                <a href="<?= controller_url('compta/create') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_creer') ?></a>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($is_treasurer_in_section): ?>
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

    <?php elseif ($dashboard_section === 'formation'): ?>
    <!-- ================================================================
         Section Formation
         ================================================================ -->
    <div class="row g-2">
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

        <div class="col-12 mt-3">
            <h6 class="text-muted mb-2"><i class="fas fa-user-tie"></i> <?= $this->lang->line('db_sub_training_manager') ?></h6>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-plus-circle text-primary"></i>
                <div class="card-title"><?= $this->lang->line('db_card_open_training') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_new_inscription') ?></div>
                <a href="<?= controller_url('formation_inscriptions') ?>/ouvrir" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_creer') ?></a>
            </div>
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

    <?php elseif ($dashboard_section === 'maintenance'): ?>
    <!-- ================================================================
         Section Maintenance
         ================================================================ -->
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

    <?php elseif ($dashboard_section === 'admin_club'): ?>
    <!-- ================================================================
         Section Administration Club
         ================================================================ -->
    <div class="row g-2">
        <?php if (has_role('club-admin')): ?>
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
        <?php endif; ?>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-road text-success"></i>
                <div class="card-title"><?= $this->lang->line('db_card_airfields') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                <a href="<?= controller_url('terrains/page') ?>" class="btn btn-success btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
            </div>
        </div>

        <?php if ($show_planeurs): ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-plane text-info"></i>
                <div class="card-title"><?= $this->lang->line('db_card_gliders') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_fleet') ?></div>
                <a href="<?= controller_url('planeur/page') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($show_avions): ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-plane-departure text-warning"></i>
                <div class="card-title"><?= $this->lang->line('db_card_aircraft') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_fleet') ?></div>
                <a href="<?= controller_url('avion/page') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($show_planeurs): ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-certificate text-warning"></i>
                <div class="card-title"><?= $this->lang->line('db_card_formation_certs') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_certificates') ?></div>
                <a href="<?= controller_url('event/page') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
            </div>
        </div>
        <?php endif; ?>
    </div>

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
                <i class="fas fa-users-cog text-danger"></i>
                <div class="card-title"><?= $this->lang->line('gvv_gestion_roles_title') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                <a href="<?= controller_url('gestion_roles') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
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

        <?php if (has_role('club-admin')): ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center">
                <i class="fas fa-id-badge text-primary"></i>
                <div class="card-title"><?= $this->lang->line('db_card_member_cards') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_member_cards') ?></div>
                <a href="<?= controller_url('cartes_membre/lot') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_gerer') ?></a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($this->config->item('gestion_documentaire')) : ?>
    <h5 class="mt-4 mb-3"><?= $this->lang->line('db_h5_doc_management') ?></h5>
    <div class="row g-2">
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

    <?php elseif ($dashboard_section === 'admin_sys'): ?>
    <!-- ================================================================
         Section Administration Système
         ================================================================ -->
    <div class="row g-2">
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

        <div class="col-12 mt-3">
            <h6 class="text-muted mb-2"><i class="fas fa-sliders-h"></i> <?= $this->lang->line('db_sub_config_section') ?></h6>
        </div>

        <?php $is_locked = $this->config->item('locked') === TRUE; ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center border-danger" id="lock-access-card">
                <i class="fas fa-<?= $is_locked ? 'lock' : 'lock-open' ?> <?= $is_locked ? 'text-danger' : 'text-success' ?>" id="lock-access-icon" style="font-size:1.5rem;"></i>
                <div class="card-title"><?= $this->lang->line('db_card_lock_access') ?></div>
                <div class="card-text text-muted mb-1"><?= $this->lang->line('db_desc_lock_access') ?></div>
                <div class="form-check form-switch d-flex justify-content-center" style="margin:0.6rem 0;">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggle-locked"
                        <?= $is_locked ? 'checked' : '' ?>
                        style="width:3.5em;height:1.75em;cursor:pointer;background-color:<?= $is_locked ? '#dc3545' : '#198754' ?>;border-color:<?= $is_locked ? '#dc3545' : '#198754' ?>;">
                </div>
                <div id="lock-status-text" class="fw-bold <?= $is_locked ? 'text-danger' : 'text-success' ?>"><?= $this->lang->line($is_locked ? 'db_lock_status_locked' : 'db_lock_status_open') ?></div>
                <div id="lock-feedback" style="min-height:1rem;font-size:0.75rem;" class="text-danger mt-1"></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center border-danger">
                <i class="fas fa-users-cog text-info"></i>
                <div class="card-title"><?= $this->lang->line('admin_connected_users_title') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_management') ?></div>
                <a href="<?= controller_url('admin/connected_users') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
            </div>
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

        <div class="col-12 mt-3">
            <h6 class="text-muted mb-2"><i class="fas fa-server"></i> <?= $this->lang->line('db_sub_supervision') ?></h6>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center border-danger">
                <i class="fas fa-file-alt text-secondary"></i>
                <div class="card-title"><?= $this->lang->line('db_card_logs') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_logs') ?></div>
                <a href="<?= controller_url('admin/logs') ?>" class="btn btn-secondary btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center border-danger">
                <i class="fas fa-server text-info"></i>
                <div class="card-title"><?= $this->lang->line('db_card_sysres') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_sysres') ?></div>
                <a href="<?= controller_url('admin/system_resources') ?>" class="btn btn-info btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
            </div>
        </div>
        <?php endif; // $is_admin ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleLocked = document.getElementById('toggle-locked');
        if (toggleLocked) {
            toggleLocked.addEventListener('change', function() {
                const checked = this.checked;
                toggleLocked.disabled = true;

                fetch('<?= controller_url('admin/ajax_toggle_locked') ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        const locked = data.locked;
                        const icon = document.getElementById('lock-access-icon');
                        const status = document.getElementById('lock-status-text');
                        const color = locked ? '#dc3545' : '#198754';
                        if (icon) {
                            icon.className = 'fas fa-' + (locked ? 'lock text-danger' : 'lock-open text-success');
                            icon.style.fontSize = '1.5rem';
                        }
                        if (status) {
                            status.textContent = locked ? '<?= $this->lang->line('db_lock_status_locked') ?>' : '<?= $this->lang->line('db_lock_status_open') ?>';
                            status.className = 'fw-bold ' + (locked ? 'text-danger' : 'text-success');
                        }
                        toggleLocked.style.backgroundColor = color;
                        toggleLocked.style.borderColor = color;
                    } else {
                        toggleLocked.checked = !checked;
                        const fb = document.getElementById('lock-feedback');
                        if (fb) fb.textContent = data.message || 'Erreur';
                    }
                })
                .catch(function() {
                    toggleLocked.checked = !checked;
                    const fb = document.getElementById('lock-feedback');
                    if (fb) fb.textContent = 'Erreur de communication';
                })
                .finally(function() {
                    toggleLocked.disabled = false;
                });
            });
        }
    });
    </script>

    <?php elseif ($dashboard_section === 'dev'): ?>
    <!-- ================================================================
         Section Développement / Test
         ================================================================ -->
    <div class="row g-2">
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

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center border-warning">
                <i class="fas fa-paper-plane text-warning"></i>
                <div class="card-title"><?= $this->lang->line('db_card_test_email') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_test_email') ?></div>
                <a href="<?= controller_url('admin/test_email') ?>" class="btn btn-warning btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
            </div>
        </div>

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
            <div class="sub-card text-center border-danger">
                <i class="fas fa-calendar-alt text-danger"></i>
                <div class="card-title"><?= $this->lang->line('db_card_freeze_date_edit') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_freeze_date_edit') ?></div>
                <a href="<?= controller_url('dates_gel/page') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
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

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center border-danger">
                <i class="fas fa-code-branch text-danger"></i>
                <div class="card-title"><?= $this->lang->line('db_card_fusion_membres') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_fusion_membres') ?></div>
                <a href="<?= controller_url('membres_fusion') ?>" class="btn btn-danger btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="sub-card text-center border-primary">
                <i class="fas fa-wrench text-primary"></i>
                <div class="card-title"><?= $this->lang->line('db_card_rename_member') ?></div>
                <div class="card-text text-muted"><?= $this->lang->line('db_desc_rename_member') ?></div>
                <a href="<?= controller_url('membres/renommer') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('db_btn_acceder') ?></a>
            </div>
        </div>

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

    <?php endif; ?>

</div>
