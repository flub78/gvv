<nav class="ui-widget-header">

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

$menu = new menu();
$menubar = array();

$this->lang->load('gvv');

if ($this->dx_auth->is_logged_in())	{

    $planche_level = $this->config->item('auto_planchiste') ? 'membre' :'planchiste';

    ##################################################################################################
    $menu_membres = array (
        'label' => translation("gvv_menu_membres"),
        'class' => 'menuheader',
        'submenu' => array (
            array ('label' => translation("gvv_menu_membres_list"), 'url' => controller_url("membre/page")),
            array ('label' => translation("gvv_menu_membres_licences"), 'url' => controller_url("licences/per_year"), 'role' => 'ca'),
            array ('label' => translation("gvv_menu_membres_fiches"), 'url' => controller_url("membre/edit")),
            array ('label' => translation("gvv_menu_membres_password"), 'url' => controller_url("auth/change_password")),
            array ('label' => translation("gvv_menu_membres_email"), 'url' => controller_url("mails/page"), 'role' => 'ca'),
        )
    );

    ##################################################################################################
    $menu_formation_planeur = array (
        'label' => translation("gvv_menu_formation"),
        'class' => 'menuheader',
        'submenu' => array (
            array ('label' => translation("gvv_menu_formation_annuel"), 'url' => controller_url("event/stats")),
            array ('label' => translation("gvv_menu_formation_club"), 'url' => controller_url("event/formation")),
            array ('label' => translation("gvv_menu_formation_fai"), 'url' => controller_url("event/fai")),
            array ('label' => translation("gvv_menu_formation_pilote"), 'url' => controller_url("vols_planeur/par_pilote_machine"), 'role' => 'ca'),
        )
    );

    $menu_statistic_planeur = array (
        'label' => translation("gvv_menu_statistic"),
        'class' => 'menuheader',
        'submenu' => array (
            array ('label' => translation("gvv_menu_statistic_monthly"), 'url' => controller_url("vols_planeur/statistic")),
            array ('label' => translation("gvv_menu_statistic_yearly"), 'url' => controller_url("vols_planeur/cumuls")),
            array ('label' => translation("gvv_menu_statistic_history"), 'url' => controller_url("vols_planeur/histo")),
            array ('label' => translation("gvv_menu_statistic_age"), 'url' => controller_url("vols_planeur/age")),
        )
    );

    $menu_planeur = array (
        'label' => translation("gvv_menu_glider"),
        'class' => 'menuheader',
        'submenu' => array (
            array ('label' => translation("gvv_menu_glider_list"), 'url' => controller_url("vols_planeur/page")),
            array ('label' => translation("gvv_menu_glider_input"), 'url' => controller_url("vols_planeur/create"), 'role' =>  'planchiste' ),
            array ('label' => translation("gvv_menu_glider_input_automatic"), 'url' => controller_url("vols_planeur/plancheauto_select"), 'role' => 'planchiste'),
            array ('label' => translation("gvv_menu_glider_machines"), 'url' => controller_url("planeur/page")),
            	$menu_statistic_planeur,
        		$menu_formation_planeur,
        )
    );

    ##################################################################################################
    $menu_statistic_avion = array (
        'label' => translation("gvv_menu_statistic"),
        'class' => 'menuheader',
        'submenu' => array (
            array ('label' =>  translation("gvv_menu_statistic_monthly"), 'url' => controller_url("vols_avion/statistic")),
            array ('label' =>  translation("gvv_menu_statistic_yearly"), 'url' => controller_url("vols_avion/cumuls")),
        )
    );

    $menu_formation_avion = array (
    		'label' => translation("gvv_menu_formation"),
    		'class' => 'menuheader',
    		'submenu' => array (
    				array ('label' => translation("gvv_menu_formation_annuel"), 'url' => controller_url("event/stats/avion")),
    				array ('label' => translation("gvv_menu_formation_club"), 'url' => controller_url("event/formation/avion")),
    				array ('label' => translation("gvv_menu_formation_pilote"), 'url' => controller_url("vols_avion/par_pilote_machine"), 'role' => 'ca'),
    		)
    );

    $menu_avion = array (
        'label' => translation("gvv_menu_airplane"),
        'class' => 'menuheader',
        'submenu' => array (
            array ('label' => translation("gvv_menu_airplane_list"), 'url' => controller_url("vols_avion/page")),
            array ('label' => translation("gvv_menu_airplane_input"), 'url' => controller_url("vols_avion/create"), 'role' =>  $planche_level ),
            array ('label' => translation("gvv_menu_airplane_machines"), 'url' => controller_url("avion/page")),
            $menu_statistic_avion,
//         	$menu_formation_avion,
        )
    );

    ##################################################################################################
    // Rapports
    
    $submenu = array (
            array ('label' => translation("gvv_menu_reports_my_bill"), 'url' => controller_url("compta/mon_compte")),
             array ('label' => translation("gvv_menu_validities"), 'url' => controller_url("alarmes"))
    );
    if ($this->config->item('gestion_tickets')) {
    	$submenu[] = array ('label' => translation("gvv_menu_reports_tickets_usage"), 'url' => controller_url("tickets/page"));
    	$submenu[] = array ('label' => translation("gvv_menu_reports_remaining_tickets"),
            		'url' => controller_url("tickets/solde"), 'role' => 'bureau');
    }
    $submenu[] = array ('label' => translation("gvv_menu_reports_user_reports"), 'url' => controller_url("reports/page"), 'role' => 'ca');
    if ($this->config->item('gestion_planeur')) $submenu[] = array ('label' => translation("gvv_menu_reports_federal_report"), 'url' => controller_url("rapports/ffvv"), 'role' => 'ca');

    if ((ENVIRONMENT == 'development') && ($this->config->item('gestion_planeur')))
    	$submenu[] = array ('label' => translation("gvv_menu_reports_admin_report"), 'url' => controller_url("rapports/dgac"), 'role' => 'admin');

    if ($this->config->item('gesasso')) $submenu[] = array ('label' => translation("GESASSO"), 'url' => controller_url("vols_planeur/gesasso"), 'role' => 'ca');
    
    $menu_facture = array (
        'label' => translation("gvv_menu_reports"),
        'class' => 'menuheader',
        'submenu' => $submenu
    );

    ##################################################################################################
    // Compta
    
    $menu_compta = array (
        'label' => translation("gvv_menu_accounting"),
        'class' => 'menuheader',
        'role' => 'ca',
        'submenu' => array (
            array ('label' => translation("gvv_menu_accounting_journal"), 'url' => controller_url("compta/page"), 'role' => 'bureau'),
            array ('label' => translation("gvv_menu_accounting_balance"), 'url' => controller_url("comptes/general"), 'role' => 'bureau'),
            array ('label' => translation("gvv_menu_accounting_pilot_balance"), 'url' => controller_url("comptes/page/411"), 'role' => 'bureau'),
            array ('label' => translation("gvv_menu_accounting_results"), 'url' => controller_url("comptes/resultat")),
            array ('label' => translation("gvv_menu_accounting_bilan"), 'url' => controller_url("comptes/bilan")),
            array ('label' => translation("gvv_menu_accounting_sales"), 'url' => controller_url("achats/list_per_year")),
            array ('label' => translation("gvv_menu_accounting_cash"), 'url' => controller_url("comptes/tresorerie")),
        )
    );

    $menu_recettes = array (
        'label' => translation("gvv_menu_entries_income"),
        'class' => 'menuheader',
        'role' => 'tresorier',
    	'url' => controller_url("compta/recettes"),
        'submenu' => array (
            array ('label' => translation("gvv_menu_entries_income"), 'url' => controller_url("compta/recettes")),
            array ('label' => translation("gvv_menu_entries_pilot_payment"), 'url' => controller_url("compta/reglement_pilote")),
            array ('label' => translation("gvv_menu_entries_pilot_billing"), 'url' => controller_url("compta/factu_pilote")),
            array ('label' => translation("gvv_menu_entries_supplier_credit"), 'url' => controller_url("compta/avoir_fournisseur")),
            )
    );
    $menu_depenses = array (
        'label' => translation("gvv_menu_entries_expense"),
        'class' => 'menuheader',
        'role' => 'tresorier',
    	'url' => controller_url("compta/depenses"),
        'submenu' => array (
            array ('label' => translation("gvv_menu_entries_expense"), 'url' => controller_url("compta/depenses")),
            array ('label' => translation("gvv_menu_entries_expense_paid"), 'url' => controller_url("compta/credit_pilote")),
            array ('label' => translation("gvv_menu_entries_pilot_refund"), 'url' => controller_url("compta/debit_pilote")),
            array ('label' => translation("gvv_menu_entries_pay_with_supplier_credit"), 'url' => controller_url("compta/utilisation_avoir_fournisseur")),
        )
    );

    $menu_ecritures = array (
        'label' => translation("gvv_menu_entries"),
        'class' => 'menuheader',
        'role' => 'tresorier',
        'submenu' => array (
    		$menu_recettes, $menu_depenses,
            array ('label' => translation("gvv_menu_entries_wire_transfer"), 'url' => controller_url("compta/virement")),
        )
    );

    ##################################################################################################
    // Heva
    
    $menu_heva = array (
            'label' => 'HEVA',
            'class' => 'menuheader',
            'role' => 'ca',
            'submenu' => array (
                    array ('label' => 'Association', 'url' => controller_url("FFVV/association")),
                    array ('label' => 'Licenciés', 'url' => controller_url("FFVV/licences")),
                    array ('label' => 'Facturation club', 'url' => controller_url("FFVV/sales")),
                    array ('label' => 'Vente Licences', 'url' => controller_url("FFVV/players")),
                    array ('label' => 'Types de qualif', 'url' => controller_url("FFVV/qualif_types")),
                    array ('label' => 'Facturation', 'url' => controller_url("FFVV/facturation"), 'role' => 'tresorier'),
            )
    );

    $horizontal_menu = array($menu_membres);
    if ($this->config->item('gestion_planeur')) $horizontal_menu[] = $menu_planeur;
    if ($this->config->item('gestion_avion')) $horizontal_menu[] = $menu_avion;
    $horizontal_menu[] = $menu_facture;
    $horizontal_menu[] = $menu_compta;
    $horizontal_menu[] = $menu_ecritures;
    if ($this->config->item('ffvv_pwd')) $horizontal_menu[] = $menu_heva;

    $menubar = array(
        'class' => 'menubar',
        'submenu' => $horizontal_menu
    );
}

$club = $this->config->item('club');
$menu_file = 'menu_'.$club.".php";
$menu_path = join( DIRECTORY_SEPARATOR, array(
	getcwd(),
	'application', 'views', $menu_file
));
if (file_exists($menu_path)) {
	@include $menu_file;
}

echo '<div id="menu" class="menu ui-widget-header">';
echo $menu->html($menubar, 0, false, 'class="jbutton"');

echo '</div>';

?>

</nav>
