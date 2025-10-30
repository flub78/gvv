<!-- VIEW: application/views/bs_menu.php -->
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
 *    Menu horizontal
 *
 *    @package vues
 */

$this->lang->load('gvv');
$this->lang->load('welcome');
$this->lang->load('admin');
$this->lang->load('attachments');
$this->lang->load('sections');

$CI = &get_instance();
$CI->load->model('sections_model');
$section = $CI->sections_model->section();
$section_count = $CI->sections_model->safe_count_all();
?>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-sm navbar-dark bg-dark pb-3 fixed-top" style="position: sticky;">
    <div class="container-fluid">

      <a class="navbar-brand" href="<?= controller_url("welcome") ?>">GVV</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mynavbar">
        <span class="" role="button"><i class="fa fa-bars" aria-hidden="true" style="color:#e6e6ff"></i></span>
      </button>

      <?php if (is_logged_in()) : ?>
        <div class="collapse navbar-collapse" id="mynavbar">
          <ul class="navbar-nav me-auto">

            <?php if (has_role('ca')) : ?>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("gvv_menu_admin") ?></a>
                <ul class="dropdown-menu">

                  <li><a class="dropdown-item" href="#"><i class="fas fa-plane-departure text-info"></i> <?= translation("gvv_menu_vols_decouverte") ?>&raquo;</a>
                    <ul class="submenu dropdown-menu">

                      <?php if (has_role('ca')) : ?>
                        <li><a class="dropdown-item" href="<?= controller_url("vols_decouverte") ?>"><i class="fas fa-ticket-alt text-success"></i> <?= translation("gvv_menu_liste_des_bons") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("vols_decouverte/select_by_id") ?>"><i class="fas fa-search text-primary"></i> <?= translation("gvv_menu_vols_decouverte_select") ?></a></li>
                      <?php endif; ?>
                  </li>
                </ul>


              <li><a class="dropdown-item" href="#"><i class="fas fa-chart-line text-info"></i> <?= translation("gvv_menu_reports") ?> &raquo;</a>
                <ul class="submenu dropdown-menu">

                  <li><a class="dropdown-item" href="<?= controller_url("alarmes") ?>"><i class="fas fa-exclamation-triangle text-warning"></i> <?= translation("gvv_menu_validities") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url("tickets/page") ?>"><i class="fas fa-ticket-alt text-info"></i> <?= translation("gvv_menu_reports_tickets_usage") ?></a></li>
                  <?php if (has_role('bureau')) : ?>
                    <li><a class="dropdown-item" href="<?= controller_url("rapports/financier") ?>"><i class="fas fa-file-invoice-dollar text-success"></i> <?= translation("gvv_menu_reports_financial_reports") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("tickets/solde") ?>"><i class="fas fa-coins text-warning"></i> <?= translation("gvv_menu_reports_remaining_tickets") ?></a></li>
                  <?php endif; ?>
                  <?php if (has_role('ca')) : ?>
                    <li><a class="dropdown-item" href="<?= controller_url("reports/page") ?>"><i class="fas fa-file-alt text-primary"></i> <?= translation("gvv_menu_reports_user_reports") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("rapports/ffvv") ?>"><i class="fas fa-flag text-primary"></i> <?= translation("gvv_menu_reports_federal_report") ?></a></li>
                  <?php endif; ?>
                  <?php if (has_role('admin')) : ?>
                    <li><a class="dropdown-item" href="<?= controller_url("rapports/dgac") ?>"><i class="fas fa-building text-danger"></i> <?= translation("gvv_menu_reports_admin_report") ?></a></li>
                  <?php endif; ?>


                </ul>
              </li>

              <?php if (has_role('ca')) : ?>
                <li><a class="dropdown-item" href="#"><i class="fas fa-cloud text-primary"></i> <?= translation("HEVA") ?> &raquo;</a>
                  <ul class="submenu dropdown-menu">
                    <li><a class="dropdown-item" href="<?= controller_url("FFVV/association") ?>"><i class="fas fa-users text-primary"></i> <?= translation("Association") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("FFVV/licences") ?>"><i class="fas fa-id-card text-success"></i> <?= translation("Licenciés") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("FFVV/sales") ?>"><i class="fas fa-shopping-cart text-success"></i> <?= translation("Facturation club") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("FFVV/players") ?>"><i class="fas fa-cash-register text-warning"></i> <?= translation("Vente Licences") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("FFVV/qualif_types") ?>"><i class="fas fa-certificate text-info"></i> <?= translation("Types de qualif") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("FFVV/facturation") ?>"><i class="fas fa-file-invoice text-danger"></i> <?= translation("Facturation") ?></a></li>
                  </ul>
                </li>

                <li><a class="dropdown-item" href="#"><i class="fas fa-cogs text-primary"></i> <?= translation("gvv_menu_admin_club") ?> &raquo;</a>
                  <ul class="submenu dropdown-menu">
                    <li><a class="dropdown-item" href="<?= controller_url("config") ?>"><i class="fas fa-cog text-primary"></i> <?= translation("gvv_admin_menu_config") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("configuration") ?>"><i class="fas fa-cogs text-info"></i> <?= translation("gvv_configuration_title_list") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("authorization") ?>"><i class="fas fa-shield-alt text-danger"></i> <?= translation("authorization_title") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("terrains/page") ?>"><i class="fas fa-road text-success"></i> <?= translation("welcome_airfield_title") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("historique") ?>"><i class="fas fa-history text-info"></i> <?= translation("welcome_history_title") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("welcome/ca") ?>"><i class="fas fa-chart-bar text-primary"></i> <?= translation("welcome_reports_title") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url('procedures') ?>"><i class="fas fa-book text-primary"></i> Procédures</a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("event/page") ?>"><i class="fas fa-certificate text-warning"></i> <?= translation("welcome_certificates") ?></a></li>
                  </ul>
                </li>

              <?php endif; ?>

              <?php if (has_role('tresorier')) : ?>

                <li><a class="dropdown-item" href="#"><i class="fas fa-calculator text-success"></i> <?= translation("gvv_menu_admin_accounting") ?> &raquo;</a>
                  <ul class="submenu dropdown-menu">
                    <li><a class="dropdown-item" href="<?= controller_url("admin/backup") ?>"><i class="fas fa-database text-primary"></i> <?= translation("welcome_database_backup_title") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("comptes/cloture") ?>"><i class="fas fa-calendar-check text-danger"></i> <?= translation("welcome_database_endofyear_title") ?></a></li>

                    <li><a class="dropdown-item" href="<?= controller_url("plan_comptable/page") ?>"><i class="fas fa-book text-primary"></i> <?= translation("welcome_chart_of_account_title") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("tarifs/page") ?>"><i class="fas fa-dollar-sign text-success"></i> <?= translation("welcome_price_list_title") ?></a></li>
                    <?php if ($this->config->item('gestion_tickets')) : ?>
                      <li><a class="dropdown-item" href="<?= controller_url("types_ticket/page") ?>"><i class="fas fa-ticket-alt text-warning"></i> <?= translation("welcome_ticket_types_title") ?></a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="<?= controller_url("compta/create") ?>"><i class="fas fa-pencil-alt text-info"></i> <?= translation("welcome_global_entries_title") ?></a></li>

                    <li><a class="dropdown-item" href="<?= controller_url("rapports/financier") ?>"><i class="fas fa-chart-line text-success"></i> <?= translation("welcome_global_financial_report") ?></a></li>

                  </ul>
                </li>



              <?php endif; ?>

              <?php if (has_role('admin')) : ?>

                <li><a class="dropdown-item" href="#"><i class="fas fa-server text-danger"></i> <?= translation("gvv_menu_admin_system") ?> &raquo;</a>
                  <ul class="submenu dropdown-menu">
                    <li><a class="dropdown-item" href="<?= controller_url("admin/backup_form") ?>"><i class="fas fa-save text-primary"></i> Sauvegarde des données</a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("admin/restore") ?>"><i class="fas fa-undo text-warning"></i> <?= translation("gvv_admin_menu_restore") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("migration") ?>"><i class="fas fa-exchange-alt text-info"></i> <?= translation("gvv_admin_menu_migrate") ?></a></li>

                    <li><a class="dropdown-item" href="<?= controller_url("backend/users") ?>"><i class="fas fa-users text-primary"></i> <?= translation("gvv_admin_menu_users") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("backend/roles") ?>"><i class="fas fa-user-tag text-info"></i> <?= translation("gvv_admin_menu_roles") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("backend/uri_permissions") ?>"><i class="fas fa-lock text-danger"></i> <?= translation("gvv_admin_menu_permissions") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url('sections') ?>"><i class="fas fa-layer-group text-success"></i> <?= translation("gvv_sections_title") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url('user_roles_per_section') ?>"><i class="fas fa-user-cog text-warning"></i> <?= translation("gvv_users_roles_per_sections_title") ?></a></li>
                  </ul>
                </li>

                <li><a class="dropdown-item" href="<?= controller_url("admin/page") ?>"><i class="fas fa-tools text-danger"></i> Admin</a></li>

              <?php endif; ?>

          </ul>
          </li>
        <?php endif; ?>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("gvv_menu_membres") ?></a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= controller_url("membre/page") ?>"><i class="fas fa-users text-primary"></i> <?= translation("gvv_menu_membres") ?></a></li>
            <?php if (has_role('ca')) : ?>
              <li><a class="dropdown-item" href="<?= controller_url("licences/per_year") ?>"><i class="fas fa-id-card text-success"></i> <?= translation("gvv_menu_membres_licences") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url("mails/addresses") ?>"><i class="fas fa-address-book text-warning"></i> <?= translation("gvv_menu_membres_email_addresses") ?></a></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="<?= controller_url("membre/edit") ?>"><i class="fas fa-user-edit text-primary"></i> <?= translation("gvv_menu_membres_fiches") ?></a></li>
            <li><a class="dropdown-item" href="<?= controller_url("auth/change_password") ?>"><i class="fas fa-key text-warning"></i> <?= translation("gvv_menu_membres_password") ?></a></li>
            <li><a class="dropdown-item" href="<?= controller_url("compta/mon_compte") ?>"><i class="fas fa-file-invoice-dollar text-success"></i> <?= translation("gvv_menu_reports_my_bill") ?></a></li>
            <li><a class="dropdown-item" href="<?= controller_url("calendar") ?>"><i class="fas fa-calendar-alt text-info"></i> <?= translation("gvv_menu_membres_calendar") ?></a></li>

          </ul>
        </li>

        <?php if (empty($section) || ($section && ($section['id'] == '1'))) : ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("gvv_menu_glider") ?></a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/page") ?>"><i class="fas fa-list text-primary"></i> <?= translation("gvv_menu_glider_list") ?></a></li>
              <?php if (has_role('planchiste')) : ?>
                <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/create") ?>"><i class="fas fa-plus text-success"></i> <?= translation("gvv_menu_glider_input") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/plancheauto_select") ?>"><i class="fas fa-magic text-info"></i> <?= translation("gvv_menu_glider_input_automatic") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/gesasso") ?>"><i class="fas fa-sync text-primary"></i> <?= translation("GESASSO") ?></a></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="<?= controller_url("planeur/page") ?>"><i class="fas fa-plane text-success"></i> <?= translation("gvv_menu_glider_machines") ?></a></li>

              <li><a class="dropdown-item" href="#"><i class="fas fa-chart-bar text-info"></i> <?= translation("gvv_menu_statistic") ?> &raquo;</a>
                <ul class="submenu dropdown-menu">
                  <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/statistic") ?>"><i class="fas fa-calendar-day text-primary"></i> <?= translation("gvv_menu_statistic_monthly") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/cumuls") ?>"><i class="fas fa-calendar-alt text-success"></i> <?= translation("gvv_menu_statistic_yearly") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/histo") ?>"><i class="fas fa-history text-info"></i> <?= translation("gvv_menu_statistic_history") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/age") ?>"><i class="fas fa-birthday-cake text-warning"></i> <?= translation("gvv_menu_statistic_age") ?></a></li>
                </ul>
              </li>
              <li><a class="dropdown-item" href="#"><i class="fas fa-graduation-cap text-primary"></i> <?= translation("gvv_menu_formation") ?> &raquo;</a>
                <ul class="submenu dropdown-menu">
                  <li><a class="dropdown-item" href="<?= controller_url("event/stats") ?>"><i class="fas fa-chart-bar text-info"></i> <?= translation("gvv_menu_formation_annuel") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url("event/formation") ?>"><i class="fas fa-graduation-cap text-success"></i> <?= translation("gvv_menu_formation_club") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url("event/fai") ?>"><i class="fas fa-globe text-primary"></i> <?= translation("gvv_menu_formation_fai") ?></a></li>
                  <?php if (has_role('ca')) : ?>
                    <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/par_pilote_machine") ?>"><i class="fas fa-user-tie text-warning"></i> <?= translation("gvv_menu_formation_pilote") ?></a></li>
                  <?php endif; ?>
                </ul>
              </li>
            </ul>
          </li>
        <?php endif; ?>


        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("gvv_menu_airplane") ?></a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= controller_url("vols_avion/page") ?>"><i class="fas fa-list text-primary"></i> <?= translation("gvv_menu_airplane_list") ?></a></li>
            <?php if (has_role('planchiste')) : ?>
              <li><a class="dropdown-item" href="<?= controller_url("vols_avion/create") ?>"><i class="fas fa-plus text-success"></i> <?= translation("gvv_menu_airplane_input") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url("avion/page") ?>"><i class="fas fa-plane-departure text-success"></i> <?= translation("gvv_menu_airplane_machines") ?></a></li>
            <?php endif; ?>

            <li><a class="dropdown-item" href="#"><i class="fas fa-chart-bar text-info"></i> <?= translation("gvv_menu_statistic") ?> &raquo;</a>
              <ul class="submenu dropdown-menu">

                <li><a class="dropdown-item" href="<?= controller_url("vols_avion/statistic") ?>"><i class="fas fa-calendar-day text-primary"></i> <?= translation("gvv_menu_statistic_monthly") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("vols_avion/cumuls") ?>"><i class="fas fa-calendar-alt text-success"></i> <?= translation("gvv_menu_statistic_yearly") ?></a></li>

              </ul>
            </li>
            <?php if ((has_role('admin')) &&  $this->config->item('gestion_pompes')) : ?>
              <li><a class="dropdown-item" href="<?= controller_url("pompes") ?>"><i class="fas fa-gas-pump text-warning"></i> <?= translation("Pompes") ?></a></li>
            <?php endif; ?>
          </ul>
        </li>

        <?php if (has_role('bureau')) : ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("gvv_menu_accounting") ?></a>
            <ul class="dropdown-menu">

              <?php if (has_role('bureau')) : ?>
                <li><a class="dropdown-item" href="<?= controller_url("compta/page") ?>"><i class="fas fa-book text-primary"></i> <?= translation("gvv_menu_accounting_journal") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("comptes/general") ?>"><i class="fas fa-balance-scale text-info"></i> <?= translation("gvv_menu_accounting_balance") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("comptes/balance") ?>"><i class="fas fa-balance-scale text-info"></i> <?= translation("gvv_menu_accounting_balance_hierarchical") ?></a></li>
              <?php endif; ?>

              <?php if (has_role('ca')) : ?>
                <li><a class="dropdown-item" href="<?= controller_url("comptes/page/411") ?>"><i class="fas fa-user-check text-success"></i> <?= translation("gvv_menu_accounting_pilot_balance") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("comptes/resultat") ?>"><i class="fas fa-chart-pie text-warning"></i> <?= translation("gvv_menu_accounting_results") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("comptes/bilan") ?>"><i class="fas fa-calculator text-primary"></i> <?= translation("gvv_menu_accounting_bilan") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("achats/list_per_year") ?>"><i class="fas fa-shopping-bag text-success"></i> <?= translation("gvv_menu_accounting_sales") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("comptes/tresorerie") ?>"><i class="fas fa-money-bill-wave text-success"></i> <?= translation("gvv_menu_accounting_cash") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url('attachments') ?>"><i class="fas fa-paperclip text-info"></i> <?= translation("gvv_attachments_title") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("comptes/dashboard") ?>"><i class="fas fa-tachometer-alt text-primary"></i> <?= translation("gvv_menu_accounting_dashboard") ?></a></li>

                <?php if (has_role('tresorier')) : ?>
                  <li><a class="dropdown-item" href="#"><i class="fas fa-sync text-info"></i> Synchronisation OpenFlyers</a>
                    <ul class="submenu dropdown-menu">

                      <li><a class="dropdown-item" href="<?= controller_url("openflyers/select_operations") ?>"><i class="fas fa-download text-success"></i> Import des opérations</a></li>

                      <li><a class="dropdown-item" href="<?= controller_url("openflyers/select_soldes") ?>"><i class="fas fa-check-double text-success"></i> Import/vérification des soldes</a></li>

                      <li><a class="dropdown-item" href="<?= controller_url("associations_of/page") ?>"><i class="fas fa-link text-primary"></i> Associations des comptes OpenFlyers</a></li>
                    </ul>
                  </li>
                  <li><a class="dropdown-item" href="<?= controller_url('rapprochements/select_releve') ?>"><i class="fas fa-list-check text-warning"></i> <?= translation("gvv_menu_rapprochements") ?></a></li>


                <?php endif; ?>
              <?php endif; ?>

            </ul>
          </li>
        <?php endif; ?>

        <?php if (has_role('tresorier') && ($section || ($section_count < 2))) : ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("gvv_menu_entries") ?></a>
            <ul class="dropdown-menu">

              <li><a class="dropdown-item" href="<?= controller_url("compta/recettes") ?>"><i class="fas fa-arrow-circle-down text-success"></i> <?= translation("gvv_menu_entries_income") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url("compta/reglement_pilote") ?>"><i class="fas fa-hand-holding-usd text-success"></i> <?= translation("gvv_menu_entries_pilot_payment") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url("compta/factu_pilote") ?>"><i class="fas fa-file-invoice text-info"></i> <?= translation("gvv_menu_entries_pilot_billing") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url("compta/avoir_fournisseur") ?>"><i class="fas fa-receipt text-success"></i> <?= translation("gvv_menu_entries_supplier_credit") ?></a></li>

              <li>
                <hr class="dropdown-divider">
              </li>

              <li><a class="dropdown-item" href="<?= controller_url("compta/depenses") ?>"><i class="fas fa-arrow-circle-up text-danger"></i> <?= translation("gvv_menu_entries_expense") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url("compta/credit_pilote") ?>"><i class="fas fa-money-check-alt text-danger"></i> <?= translation("gvv_menu_entries_expense_paid") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url("compta/debit_pilote") ?>"><i class="fas fa-undo-alt text-warning"></i> <?= translation("gvv_menu_entries_pilot_refund") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url("compta/utilisation_avoir_fournisseur") ?>"><i class="fas fa-credit-card text-primary"></i> <?= translation("gvv_menu_entries_pay_with_supplier_credit") ?></a></li>

              <li>
                <hr class="dropdown-divider">
              </li>

              <li><a class="dropdown-item" href="<?= controller_url("compta/virement") ?>"><i class="fas fa-exchange-alt text-info"></i> <?= translation("gvv_menu_entries_wire_transfer") ?></a></li>

              <li><a class="dropdown-item" href="<?= controller_url("compta/depot_especes") ?>"><i class="fas fa-piggy-bank text-success"></i> <?= translation("gvv_menu_entries_wire_deposit") ?></a></li>

              <li><a class="dropdown-item" href="<?= controller_url("compta/retrait_liquide") ?>"><i class="fas fa-money-bill-alt text-danger"></i> <?= translation("gvv_menu_entries_wire_withdrawal") ?></a></li>

              <li>
                <hr class="dropdown-divider">
              </li>

              <li><a class="dropdown-item" href="<?= controller_url("compta/remb_capital") ?>"><i class="fas fa-coins text-warning"></i> <?= translation("gvv_menu_entries_wire_remb_capital") ?></a></li>

              <li>
                <hr class="dropdown-divider">
              </li>

              <li><a class="dropdown-item" href="<?= controller_url("compta/encaissement_pour_une_section") ?>"><i class="fas fa-building text-info"></i> <?= translation("gvv_menu_entries_section_collection") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url("compta/reversement_section") ?>"><i class="fas fa-exchange-alt text-secondary"></i> <?= translation("gvv_menu_entries_section_reversal") ?></a></li>
            </ul>
          </li>
        <?php endif; ?>

        <?php
        // Chargement des sous menu spécifiques par club
        $club = $this->config->item('club');
        $view_name = 'bs_menu_' . $club;
        $menu_file = $view_name . '.php';
        $menu_path = join(DIRECTORY_SEPARATOR, array(
          getcwd(),
          'application',
          'views',
          $menu_file
        ));
        if (file_exists($menu_path)) {
          $this->load->view($view_name);
        } else {
          $msg = "menu not found $menu_path";
          gvv_error($msg);
        }
        ?>

        <?php if ($this->config->item('dev_menu')) : ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Dev</a>
            <ul class="dropdown-menu">

              <li><a class="dropdown-item" href="<?= controller_url("tests/index") ?>"><i class="fas fa-vial text-success"></i> <?= translation("Tests") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url('admin/info') ?>"><i class="fas fa-info-circle text-info"></i> phpinfo</a></li>
              <li><a class="dropdown-item" href="<?= base_url() . '/user_guide' ?>"><i class="fas fa-book-open text-primary"></i> <?= translation("CodeIgniter") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url('admin/metadata') ?>"><i class="fas fa-database text-warning"></i> <?= translation("Dump Metadata") ?></a></li>
            </ul>
          </li>
        <?php endif; ?>


        </ul> <!-- Fin des sous menus gauche -->

        <!-- Nom, role et sous-menu utilisateur -->
        <?php
        $title = $this->config->item('nom_club');
        $CI = &get_instance();

        // echo form_open(controller_url("auth/logout")) . "\n";
        $gvv_user = $CI->dx_auth->get_username();
        $gvv_role = $CI->dx_auth->get_role_name();

        if (strlen($gvv_user) > 1) {
          // if someone is logged in
          echo form_hidden('gvv_user', $gvv_user);
          echo form_hidden('gvv_role', $gvv_role);
        }
        ?>

        </div>

        <form class="d-flex ms-5 bg-dark border-0"> 
          <div class="text-white bg-dark me-1 text-center">
            <?= $gvv_user ?>
            <div class="text-white me-1 text-center">
              <?= $gvv_role ?>
            </div>

            <?php if ($section_count > 1) : ?>
              <div>
                <?= $this->lang->line("gvv_sections_element") . ": " . dropdown_field('section', $this->session->userdata('section'), $this->session->userdata('section_selector'), 'class="" onchange="updateSection(this.value)"') ?>
              </div>
            <?php endif; ?>

          </div>
          <script>
            function updateSection(value) {
              // Store the current page URL before updating section
              const currentPage = window.location.href;
              console.log("Updating section to:", value);
              $.post('<?= site_url('user_roles_per_section/set_section') ?>', {
                section: value
              }, function(response) {
                console.log("Section changed:", response);
                window.location.href = JSON.parse(response).redirect;
              });
            }
          </script>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle  text-white" href="#" role="button" data-bs-toggle="dropdown">
              <i class="fa-solid fa-user fa-2xl" dusk="user_icon"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= controller_url("compta/mon_compte") ?>"><i class="fas fa-file-invoice-dollar text-success"></i> <?= translation("gvv_menu_reports_my_bill") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url("auth/change_password") ?>"><i class="fas fa-key text-warning"></i> <?= translation("gvv_menu_membres_password") ?></a></li>
              <?php if ($this->config->item('gestion_tickets')) : ?>
                <li><a class="dropdown-item" href="<?= controller_url("tickets/page") ?>"><i class="fas fa-ticket-alt text-info"></i> <?= translation("gvv_menu_reports_tickets_usage") ?></a></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="<?= controller_url("alarmes") ?>"><i class="fas fa-exclamation-triangle text-warning"></i> <?= translation("gvv_menu_validities") ?></a></li>

              <li><a class="dropdown-item" href="<?= controller_url("auth/logout") ?>"><i class="fas fa-sign-out-alt text-danger"></i> <?= translation("gvv_button_exit") ?></a></li>
            </ul>
          </li>

        </form>
      <?php endif; ?>

    </div>

  </nav>