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

?>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-sm navbar-dark bg-dark pb-3 fixed-top" style="position: sticky;">
    <div class="container-fluid">

      <a class="navbar-brand" href="<?= controller_url("calendar") ?>">GVV</a>
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

                  <li><a class="dropdown-item" href="#"><?= translation("gvv_menu_reports") ?> &raquo;</a>
                    <ul class="submenu dropdown-menu">

                      <li><a class="dropdown-item" href="<?= controller_url("alarmes") ?>"><?= translation("gvv_menu_validities") ?></a></li>
                      <li><a class="dropdown-item" href="<?= controller_url("tickets/page") ?>"><?= translation("gvv_menu_reports_tickets_usage") ?></a></li>
                      <?php if (has_role('bureau')) : ?>
                        <li><a class="dropdown-item" href="<?= controller_url("tickets/solde") ?>"><?= translation("gvv_menu_reports_remaining_tickets") ?></a></li>
                      <?php endif; ?>
                      <?php if (has_role('ca')) : ?>
                        <li><a class="dropdown-item" href="<?= controller_url("reports/page") ?>"><?= translation("gvv_menu_reports_user_reports") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("rapports/ffvv") ?>"><?= translation("gvv_menu_reports_federal_report") ?></a></li>
                      <?php endif; ?>
                      <?php if (has_role('admin')) : ?>
                        <li><a class="dropdown-item" href="<?= controller_url("rapports/dgac") ?>"><?= translation("gvv_menu_reports_admin_report") ?></a></li>
                      <?php endif; ?>


                    </ul>
                  </li>

                  <?php if (has_role('ca')) : ?>
                    <li><a class="dropdown-item" href="#"><?= translation("HEVA") ?> &raquo;</a>
                      <ul class="submenu dropdown-menu">
                        <li><a class="dropdown-item" href="<?= controller_url("FFVV/association") ?>"><?= translation("Association") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("FFVV/licences") ?>"><?= translation("Licenciés") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("FFVV/sales") ?>"><?= translation("Facturation club") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("FFVV/players") ?>"><?= translation("Vente Licences") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("FFVV/qualif_types") ?>"><?= translation("Types de qualif") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("FFVV/facturation") ?>"><?= translation("Facturation") ?></a></li>
                      </ul>
                    </li>

                    <li><a class="dropdown-item" href="#"><?= translation("gvv_menu_admin_club") ?> &raquo;</a>
                      <ul class="submenu dropdown-menu">
                        <li><a class="dropdown-item" href="<?= controller_url("config") ?>"><?= translation("gvv_admin_menu_config") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("terrains/page") ?>"><?= translation("welcome_airfield_title") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("historique") ?>"><?= translation("welcome_history_title") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("welcome/ca") ?>"><?= translation("welcome_reports_title") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("event/page") ?>"><?= translation("welcome_certificates") ?></a></li>
                      </ul>
                    </li>

                  <?php endif; ?>

                  <?php if (has_role('tresorier')) : ?>

                    <li><a class="dropdown-item" href="#"><?= translation("gvv_menu_admin_accounting") ?> &raquo;</a>
                      <ul class="submenu dropdown-menu">
                        <li><a class="dropdown-item" href="<?= controller_url("admin/backup") ?>"><?= translation("welcome_database_backup_title") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("comptes/cloture") ?>"><?= translation("welcome_database_endofyear_title") ?></a></li>

                        <li><a class="dropdown-item" href="<?= controller_url("plan_comptable/page") ?>"><?= translation("welcome_chart_of_account_title") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("tarifs/page") ?>"><?= translation("welcome_price_list_title") ?></a></li>
                        <?php if ($this->config->item('gestion_tickets')) : ?>
                          <li><a class="dropdown-item" href="<?= controller_url("types_ticket/page") ?>"><?= translation("welcome_ticket_types_title") ?></a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="<?= controller_url("compta/create") ?>"><?= translation("welcome_global_entries_title") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("rapports/financier") ?>"><?= translation("welcome_global_financial_report") ?></a></li>

                      </ul>
                    </li>

                  <?php endif; ?>

                  <?php if (has_role('admin')) : ?>

                    <li><a class="dropdown-item" href="#"><?= translation("gvv_menu_admin_system") ?> &raquo;</a>
                      <ul class="submenu dropdown-menu">
                        <li><a class="dropdown-item" href="<?= controller_url("admin/backup") ?>"><?= translation("welcome_database_backup_title") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("admin/restore") ?>"><?= translation("gvv_admin_menu_restore") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("migration") ?>"><?= translation("gvv_admin_menu_migrate") ?></a></li>

                        <li><a class="dropdown-item" href="<?= controller_url("backend/users") ?>"><?= translation("gvv_admin_menu_users") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("backend/roles") ?>"><?= translation("gvv_admin_menu_roles") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url("backend/uri_permissions") ?>"><?= translation("gvv_admin_menu_permissions") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url('sections') ?>"><?= translation("gvv_sections_title") ?></a></li>
                        <li><a class="dropdown-item" href="<?= controller_url('user_roles_per_section') ?>"><?= translation("gvv_users_roles_per_sections_title") ?></a></li>
                      </ul>
                    </li>

                    <li><a class="dropdown-item" href="<?= controller_url("admin/page") ?>">Admin</a></li>

                  <?php endif; ?>

                </ul>
              </li>
            <?php endif; ?>

            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("gvv_menu_membres") ?></a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= controller_url("membre/page") ?>"><?= translation("gvv_menu_membres") ?></a></li>
                <?php if (has_role('ca')) : ?>
                  <li><a class="dropdown-item" href="<?= controller_url("licences/per_year") ?>"><?= translation("gvv_menu_membres_licences") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url("mails/page") ?>"><?= translation("gvv_menu_membres_email") ?></a></li>
                <?php endif; ?>
                <li><a class="dropdown-item" href="<?= controller_url("membre/edit") ?>"><?= translation("gvv_menu_membres_fiches") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("auth/change_password") ?>"><?= translation("gvv_menu_membres_password") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("compta/mon_compte") ?>"><?= translation("gvv_menu_reports_my_bill") ?></a></li>
                <li><a class="dropdown-item" href="<?= controller_url("calendar") ?>"><?= translation("gvv_menu_membres_calendar") ?></a></li>

              </ul>
            </li>

            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("gvv_menu_glider") ?></a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/page") ?>"><?= translation("gvv_menu_glider_list") ?></a></li>
                <?php if (has_role('planchiste')) : ?>
                  <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/create") ?>"><?= translation("gvv_menu_glider_input") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/plancheauto_select") ?>"><?= translation("gvv_menu_glider_input_automatic") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/gesasso") ?>"><?= translation("GESASSO") ?></a></li>
                <?php endif; ?>
                <li><a class="dropdown-item" href="<?= controller_url("planeur/page") ?>"><?= translation("gvv_menu_glider_machines") ?></a></li>

                <li><a class="dropdown-item" href="#"><?= translation("gvv_menu_statistic") ?> &raquo;</a>
                  <ul class="submenu dropdown-menu">
                    <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/statistic") ?>"><?= translation("gvv_menu_statistic_monthly") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/cumuls") ?>"><?= translation("gvv_menu_statistic_yearly") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/histo") ?>"><?= translation("gvv_menu_statistic_history") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/age") ?>"><?= translation("gvv_menu_statistic_age") ?></a></li>
                  </ul>
                </li>
                <li><a class="dropdown-item" href="#"><?= translation("gvv_menu_formation") ?> &raquo;</a>
                  <ul class="submenu dropdown-menu">
                    <li><a class="dropdown-item" href="<?= controller_url("event/stats") ?>"><?= translation("gvv_menu_formation_annuel") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("event/formation") ?>"><?= translation("gvv_menu_formation_club") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("event/fai") ?>"><?= translation("gvv_menu_formation_fai") ?></a></li>
                    <?php if (has_role('ca')) : ?>
                      <li><a class="dropdown-item" href="<?= controller_url("vols_planeur/par_pilote_machine") ?>"><?= translation("gvv_menu_formation_pilote") ?></a></li>
                    <?php endif; ?>
                  </ul>
                </li>
              </ul>
            </li>

            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("gvv_menu_airplane") ?></a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= controller_url("vols_avion/page") ?>"><?= translation("gvv_menu_airplane_list") ?></a></li>
                <?php if (has_role('planchiste')) : ?>
                  <li><a class="dropdown-item" href="<?= controller_url("vols_avion/create") ?>"><?= translation("gvv_menu_airplane_input") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url("avion/page") ?>"><?= translation("gvv_menu_airplane_machines") ?></a></li>
                <?php endif; ?>

                <li><a class="dropdown-item" href="#"><?= translation("gvv_menu_statistic") ?> &raquo;</a>
                  <ul class="submenu dropdown-menu">

                    <li><a class="dropdown-item" href="<?= controller_url("vols_avion/statistic") ?>"><?= translation("gvv_menu_statistic_monthly") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("vols_avion/cumuls") ?>"><?= translation("gvv_menu_statistic_yearly") ?></a></li>

                  </ul>
                </li>
                <?php if ((has_role('admin')) &&  $this->config->item('gestion_pompes')) : ?>
                  <li><a class="dropdown-item" href="<?= controller_url("pompes") ?>"><?= translation("Pompes") ?></a></li>
                <?php endif; ?>
              </ul>
            </li>

            <?php if (has_role('bureau')) : ?>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("gvv_menu_accounting") ?></a>
                <ul class="dropdown-menu">

                  <?php if (has_role('bureau')) : ?>
                    <li><a class="dropdown-item" href="<?= controller_url("compta/page") ?>"><?= translation("gvv_menu_accounting_journal") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("comptes/general") ?>"><?= translation("gvv_menu_accounting_balance") ?></a></li>
                  <?php endif; ?>

                  <?php if (has_role('ca')) : ?>
                    <li><a class="dropdown-item" href="<?= controller_url("comptes/page/411") ?>"><?= translation("gvv_menu_accounting_pilot_balance") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("comptes/resultat") ?>"><?= translation("gvv_menu_accounting_results") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("comptes/bilan") ?>"><?= translation("gvv_menu_accounting_bilan") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("achats/list_per_year") ?>"><?= translation("gvv_menu_accounting_sales") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url("comptes/tresorerie") ?>"><?= translation("gvv_menu_accounting_cash") ?></a></li>
                    <li><a class="dropdown-item" href="<?= controller_url('attachments') ?>"><?= translation("gvv_attachments_title") ?></a></li>

                  <?php endif; ?>

                </ul>
              </li>
            <?php endif; ?>

            <?php if (has_role('tresorier')) : ?>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("gvv_menu_entries") ?></a>
                <ul class="dropdown-menu">

                  <li><a class="dropdown-item" href="#"><?= translation("gvv_menu_entries_income") ?> &raquo;</a>
                    <ul class="submenu dropdown-menu">

                      <li><a class="dropdown-item" href="<?= controller_url("compta/recettes") ?>"><?= translation("gvv_menu_entries_income") ?></a></li>
                      <li><a class="dropdown-item" href="<?= controller_url("compta/reglement_pilote") ?>"><?= translation("gvv_menu_entries_pilot_payment") ?></a></li>
                      <li><a class="dropdown-item" href="<?= controller_url("compta/factu_pilote") ?>"><?= translation("gvv_menu_entries_pilot_billing") ?></a></li>
                      <li><a class="dropdown-item" href="<?= controller_url("compta/avoir_fournisseur") ?>"><?= translation("gvv_menu_entries_supplier_credit") ?></a></li>

                    </ul>
                  </li>

                  <li><a class="dropdown-item" href="#"><?= translation("gvv_menu_entries_expense") ?> &raquo;</a>
                    <ul class="submenu dropdown-menu">
                      <li><a class="dropdown-item" href="<?= controller_url("compta/depenses") ?>"><?= translation("gvv_menu_entries_expense") ?></a></li>
                      <li><a class="dropdown-item" href="<?= controller_url("compta/credit_pilote") ?>"><?= translation("gvv_menu_entries_expense_paid") ?></a></li>
                      <li><a class="dropdown-item" href="<?= controller_url("compta/debit_pilote") ?>"><?= translation("gvv_menu_entries_pilot_refund") ?></a></li>
                      <li><a class="dropdown-item" href="<?= controller_url("compta/utilisation_avoir_fournisseur") ?>"><?= translation("gvv_menu_entries_pay_with_supplier_credit") ?></a></li>
                    </ul>
                  </li>
                  <li><a class="dropdown-item" href="<?= controller_url("compta/virement") ?>"><?= translation("gvv_menu_entries_wire_transfer") ?></a></li>

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

                  <li><a class="dropdown-item" href="<?= controller_url("tests/index") ?>"><?= translation("Tests") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url('admin/info') ?>">phpinfo</a></li>
                  <li><a class="dropdown-item" href="<?= base_url() . '/user_guide' ?>"><?= translation("CodeIgniter") ?></a></li>
                  <li><a class="dropdown-item" href="<?= controller_url('admin/metadata') ?>"><?= translation("Dump Metadata") ?></a></li>

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

        <form class="d-flex ms-5">
          <div class="text-white me-1 text-center">
            <?= $gvv_user ?>
            <div class="text-white me-1 text-center">
              <?= $gvv_role ?>
            </div>
            <div>
              <?= $this->lang->line("gvv_sections_element") . ": " . dropdown_field('section', $this->session->userdata('section'), $this->session->userdata('section_selector'), 'class="" onchange="updateSection(this.value)"') ?>
            </div>
          </div>
          <script>
            function updateSection(value) {
              $.post('<?= site_url('user_roles_per_section/set_section') ?>', {
                section: value
              }, function() {
                window.location.reload();
              });
            }
          </script>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle  text-white" href="#" role="button" data-bs-toggle="dropdown">
              <i class="fa-solid fa-user fa-2xl" dusk="user_icon"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= controller_url("compta/mon_compte") ?>"><?= translation("gvv_menu_reports_my_bill") ?></a></li>
              <li><a class="dropdown-item" href="<?= controller_url("auth/change_password") ?>"><?= translation("gvv_menu_membres_password") ?></a></li>
              <?php if ($this->config->item('gestion_tickets')) : ?>
                <li><a class="dropdown-item" href="<?= controller_url("tickets/page") ?>"><?= translation("gvv_menu_reports_tickets_usage") ?></a></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="<?= controller_url("alarmes") ?>"><?= translation("gvv_menu_validities") ?></a></li>

              <li><a class="dropdown-item" href="<?= controller_url("auth/logout") ?>"><?= translation("gvv_button_exit") ?></a></li>
            </ul>
          </li>

        </form>
      <?php endif; ?>

    </div>

  </nav>