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
 * @package vues
 * 
 * Extrait de comptes
 */
//$this->load->library('ButtonNew');
$this->load->library('DataTable');

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

echo '<div id="body" class="body ui-widget-content">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session);
echo heading("gvv_compta_title_entries", 3);

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
echo year_selector($controller, $year, $year_selector);

// Filtre
echo form_hidden('filter_active', $filter_active);

echo form_fieldset($this->lang->line("gvv_str_filter"), array(
    'class' => 'coolfieldset filtre',
    'title' => $this->lang->line("gvv_str_filter_tooltip")
));
echo "<div>";
echo form_open(controller_url($controller) . "/filterValidation/" . $compte, array('name' => 'saisie'));

$flt = $this->lang->line("gvv_date") . ": " . input_field('filter_date', $filter_date, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAA', 'class' => 'datepicker'));
$flt .= nbs() . $this->lang->line("gvv_until") .  ": " . input_field('date_end', $date_end, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAA', 'class' => 'datepicker'));
$flt .= nbs();
if ($navigation_allowed) {
    $flt .= $this->lang->line("gvv_compta_compte") . ": " . dropdown_field('id', $id, $compte_selector, "id='selector' onchange='compte_selection();'");
} else {
    $flt .= $this->lang->line("gvv_compta_compte") . ": " . input_field('id', $nom, array('type'  => 'text', 'readonly' => "readonly", 'size' => 30));
}
if ($this->dx_auth->is_role('tresorier')) {
    $flt .= br() . enumerate_radio_fields($this->lang->line("gvv_compta_type_ecriture"), 'filter_checked', $filter_checked);
}
$flt .= br() . $this->lang->line("gvv_compta_montant_min") . ": "
    . input_field('montant_min', $montant_min, array('type'  => 'text', 'size' => '8', 'title' => 'Montant minimal'))
    . nbs() . $this->lang->line("gvv_compta_montant_max") .  ": " . nbs()
    . input_field('montant_max', $montant_max, array('type'  => 'text', 'size' => '8', 'title' => 'Montant maximal'));

$flt .= nbs(2) . enumerate_radio_fields($this->lang->line("gvv_compta_selector_debit_credit"), 'filter_debit', $filter_debit);

echo $flt . br(2);
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_select")))
    . form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_display")));

echo form_close();
echo "</div>";
echo form_fieldset_close();

// Nom du club et du pilote pour facturation

if (isset($pilote_name)) {
    echo form_fieldset($this->lang->line("gvv_compta_fieldset_addresses") . nbs() . $pilote_name, array(
        'class' => 'coolfieldset startClosed',
        'title' => $this->lang->line("gvv_compta_filter_tooltip")
    ));
    echo "<div>";

    $nom_club     = $this->config->item('nom_club');
    $tel_club     = $this->config->item('tel_club');
    $email_club    = $this->config->item('email_club');
    $adresse_club = $this->config->item('adresse_club');
    $cp_club      = $this->config->item('cp_club');
    $ville_club   = $this->config->item('ville_club');

    echo "<table><tr><td>\n";
    // --------------------------------------------------------------------------
    // Le club
    $row = 0;
    $tab = nbs(6);
    $table = array();
    echo form_fieldset($this->lang->line("gvv_compta_fieldset_association"));
    $table[$row][] = $tab;
    $table[$row][] = $nom_club;
    $table[$row][] = $tab;
    //$table [$row][] = img($logo_club);
    $row++;

    $table[$row][] = $tab;
    $table[$row][] = $adresse_club;
    $row++;

    $table[$row][] = $tab;
    $table[$row][] = $cp_club . nbs(2) . $ville_club;
    $row++;

    $table[$row][] = $tab;
    $table[$row][] = $tel_club;
    $row++;

    $table[$row][] = $tab;
    $table[$row][] = $email_club;
    $row++;

    display_form_table($table);

    echo form_fieldset_close();
    echo "</td><td>";
    echo form_fieldset($this->lang->line("gvv_compta_fieldset_pilote"));
    $row = 0;
    $table = array();
    $table[$row][] = $tab;

    $table[$row][] = $pilote_name;
    if (array_key_exists('madresse', $pilote_info)) {
        # Test parce qu'on pourrait être connecté sous un nom d'utilisateur qui ne serait pas membre
        $row++;
        $table[$row][] = $tab;
        $table[$row][] = $pilote_info['madresse'];
        $row++;
        $table[$row][] = $tab;
        $table[$row][] =  sprintf("%05d", $pilote_info['cp']) . nbs() . $pilote_info['ville'];

        $row++;
        $table[$row][] = $tab;
        $table[$row][] = $pilote_info['memail'];
    }
    display_form_table($table);


    echo form_fieldset_close();
    echo "</td></tr></table>\n";
    echo "</div>";
    echo form_fieldset_close();
}

// Table account information
echo form_fieldset($this->lang->line("gvv_compta_fieldset_compte"));
$table = array();
$row = 0;

if ($compte != '') {
    $row++;
    $table[$row][] = $this->lang->line("gvv_compta_label_accounting_code") . ": ";
    $table[$row][] = input_field('codec', $codec, array('type'  => 'text', 'size' => '10', 'readonly' => "readonly"));

    $row++;
    $table[$row][] = $this->lang->line("gvv_compta_label_description") . ": ";
    $table[$row][] = input_field('desc', $desc, array('type'  => 'text', 'size' => '80', 'readonly' => "readonly"));

    $row++;
    $table[$row][] = " ";
    $row++;
    $table[$row][] = $this->lang->line("gvv_compta_label_balance_before") . "  $date_deb  "
        .  $this->lang->line("gvv_compta_label_debitor") . ": ";
    if ($solde_avant < 0) {
        $solde_deb = euro(abs($solde_avant));
        $solde_cred = "";
    } else {
        $solde_deb = "";
        $solde_cred = euro($solde_avant);
    }
    $table[$row][] = input_field('debit', $solde_deb, array('type'  => 'text', 'readonly' => "readonly"))
        . nbs(6) . $this->lang->line("gvv_compta_label_creditor") . ": "
        . input_field('credit', $solde_cred, array('type'  => 'text', 'readonly' => "readonly"));
}

display_form_table($table);
echo form_fieldset_close();

$fields = array('date_op', 'autre_compte', 'description', 'num_cheque', 'prix', 'quantite', 'debit', 'credit');

$fields[] = 'solde';
$fields[] = 'gel';

// Lignes d'écriture
$attrs = array(
    'controller' => $controller,
    'fields' =>     $fields,
    'actions' => array('edit', 'delete'),
    'page' => "journal_compte/$compte",
    'uri_segment' => 4,
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "sql_table"
);

if ($count > 400) {
    $attrs['count'] = $count;
    $attrs['first'] = $premier;
} else {
    if ($has_modification_rights) {
        $attrs['class'] .= " datedtable";
    } else {
        $attrs['class'] .= " datedtable_ro";
    }
}

// echo "rights=$has_modification_rights" . br(); 
echo $this->gvvmetadata->table("vue_journal", $attrs, "");


// Solde final
$table = array();
$row = 0;
$table[$row][] = $this->lang->line("gvv_compta_label_balance_at") . " $date_fin: "
    . $this->lang->line("gvv_compta_label_debitor") . ": ";
if ($solde_fin < 0) {
    $solde_deb = euro(abs($solde_fin));
    $solde_cred = "";
} else {
    $solde_deb = "";
    $solde_cred = euro($solde_fin);
}
$table[$row][] = input_field('debit', $solde_deb, array('type'  => 'text', 'readonly' => "readonly"))
    . nbs(6) . $this->lang->line("gvv_compta_label_creditor") . ": "
    . input_field('credit', $solde_cred, array('type'  => 'text', 'readonly' => "readonly"));

display_form_table($table);

// Achats
if ($codec == 411 && $navigation_allowed) {
    echo form_open(controller_url("achats") . "/formValidation/" . $action, array('name' => 'saisie'));

    // hidden contrller url for java script access
    echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
    echo form_hidden('saisie_par', $saisie_par, '');
    echo form_hidden('id', 0);
    echo form_hidden('action', $action);
    echo form_hidden('pilote', $pilote);

    echo form_fieldset($this->lang->line("gvv_compta_fieldset_achats"), array(
        'class' => 'coolfieldset startClosed',
        'title' => $this->lang->line("gvv_compta_filter_tooltip")
    ));
    echo "<div>";

    $table = array();
    $row = 0;
    $table[$row] = $this->lang->line("gvv_compta_purchase_headers");
    $row++;
    $table[$row][] = input_field('date', $date, array('type'  => 'text', 'size' => '10', 'class' => 'datepicker'));
    $table[$row][] = dropdown_field(
        'produit',
        $produit,
        $produit_selector,
        "id='product_selector' "
    );
    $table[$row][] = input_field('quantite', $quantite, array('type'  => 'text', 'size' => '10'));
    $table[$row][] = input_field('description', $description, array('type'  => 'text', 'size' => '50'));
    $table[$row][] = form_input(array('type' => 'submit', 'name' => 'button', 'value' => 'Validation', 'id' => 'validation_achat'));

    $table = new DataTable(array(
        'values' => $table,
        'class' => "fixed_datatable",
        'align' => array('right', 'left', 'right', 'right', 'right', 'left', 'left', 'right')
    ));

    $table->display();
    echo "</div>";
    echo form_fieldset_close();

    echo form_close();
}

if ($this->dx_auth->is_role('tresorier')) {
    echo button_bar2("$controller/export/$compte", array('Excel' => "button", 'Pdf' => "button", $this->lang->line("gvv_compta_button_freeze") => 'button'));
} else {
    echo button_bar2("$controller/export/$compte", array('Excel' => "button", 'Pdf' => "button"));
}

echo '</div>';
?>

<script language="JavaScript">
    <!--
    $(document).ready(function() {

        jQuery.extend(jQuery.fn.dataTableExt.oSort, {
            "date-uk-pre": function(a) {
                var ukDatea = a.split('/');
                return (ukDatea[2] * 400 + ukDatea[1] * 31 + ukDatea[0]) * 1;
            },

            "date-uk-asc": function(a, b) {
                return ((a < b) ? -1 : ((a > b) ? 1 : 0));
            },

            "date-uk-desc": function(a, b) {
                return ((a < b) ? 1 : ((a > b) ? -1 : 0));
            }
        });


        jQuery.fn.dataTableExt.aTypes.unshift(
            function(sData) {
                if (sData !== null && sData.match(/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[012])\/(19|20|21)\d\d$/)) {
                    return 'date-uk';
                }
                return null;
            }
        );

        $('.datedtable').dataTable({
            "bFilter": true,
            "bPaginate": true,
            "iDisplayLength": 25,
            "bSort": false,
            "bStateSave": false,
            "bInfo": true,
            "bJQueryUI": true,
            "bAutoWidth": true,
            "sPaginationType": "full_numbers",
            "aoColumns": [{
                    "sType": "date-uk"
                },
                {
                    "bSortable": true
                },
                {
                    "bSortable": true
                },
                {
                    "bSortable": true
                },
                {
                    "bSortable": true
                },
                {
                    "bSortable": false
                },
                {
                    "bSortable": false
                },
                {
                    "bSortable": false
                },
                {
                    "bSortable": false
                },
                {
                    "bSortable": false
                },
                {
                    "bSortable": false
                },
                {
                    "bSortable": false
                }
            ],
            "oLanguage": olanguage
        });

        $('.datedtable_ro').dataTable({
            "bFilter": true,
            "bPaginate": true,
            "iDisplayLength": 25,
            "bSort": false,
            "bInfo": true,
            "bJQueryUI": true,
            "bStateSave": false,
            "bAutoWidth": true,
            "sPaginationType": "full_numbers",
            "aoColumns": [{
                    "sType": "date-uk"
                },
                {
                    "bSortable": true
                },
                {
                    "bSortable": true
                },
                {
                    "bSortable": true
                },
                {
                    "bSortable": true
                },
                {
                    "bSortable": false
                },
                {
                    "bSortable": false
                },
                {
                    "bSortable": false
                },
                {
                    "bSortable": false
                },
                {
                    "bSortable": false
                }
            ],
            "oLanguage": olanguage
        });
    });

    //
    -->
</script>