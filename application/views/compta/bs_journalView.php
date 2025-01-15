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
 * Grand journal
 */
$this->load->library('ButtonNew');

$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');
$this->lang->load('compta');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session);


echo heading('gvv_comptes_title_journal', 3);

// hidden controller url for java script access
echo '<div class="mb-3">';
echo year_selector($controller, $year, $year_selector);
echo '</div>';

// Filtre
echo form_hidden('filter_active', $filter_active);
echo form_fieldset($this->lang->line("gvv_str_filter"), array(
    'class' => 'coolfieldset filtre',
    'title' => $this->lang->line("gvv_str_filter_tooltip")
));

echo "<div>";
echo form_open(controller_url($controller) . "/JournalFilterValidation/" . $compte, array('name' => 'saisie'));

$table = array();
$row = 0;
$table[$row][] = $this->lang->line('gvv_compta_comptes') . ": ";
$table[$row][] = dropdown_field('id', $id, $compte_selector, "id='selector' class='big_select' onchange='compte_selection();'");
$row++;
$table[$row][] =  $this->lang->line('gvv_compta_date') . ": ";

$table[$row][] = input_field('filter_date', $filter_date, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAA', 'class' => 'datepicker'))
    . $this->lang->line('gvv_compta_jusqua') . ": "
    . input_field('date_end', $date_end, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAA', 'class' => 'datepicker'));
if ($this->dx_auth->is_role('tresorier')) {
    $table[$row][] = nbs() . enumerate_radio_fields($this->lang->line('gvv_compta_type_ecriture'), 'filter_checked', $filter_checked);
}
$table[$row][] = "";
$row++;
$query_selector = $this->lang->line('gvv_vue_journal_selector');

$table[$row][] = "";
$table[$row][] = dropdown_field('query', $query, $query_selector, "id='query_selector' onchange='query_selection();'");
$table[$row][] = $this->lang->line('gvv_compta_emploi') . ": " . input_field('filter_code1', $filter_code1, array('type'  => 'text', 'size' => '6', 'title' => 'Code comptable'))
    . " - "
    . input_field('code1_end', $code1_end, array('type'  => 'text', 'size' => '6', 'title' => 'Code comptable'));
$table[$row][] = $this->lang->line('gvv_compta_resource') . ": "
    . input_field('filter_code2', $filter_code2, array('type'  => 'text', 'size' => '6', 'title' => 'Code comptable'))
    . " - "
    . input_field('code2_end', $code2_end, array('type'  => 'text', 'size' => '6', 'title' => 'Code comptable'));
$table[$row][] = "";
$table[$row][] = "";
$row++;
$table[$row][] = $this->lang->line('gvv_compta_montant_min') . ": ";
$table[$row][] = input_field('montant_min', $montant_min, array('type'  => 'text', 'size' => '8', 'title' => 'Montant minimal'));
$table[$row][] =  $this->lang->line('gvv_compta_montant_max') . ": " . nbs()
    . input_field('montant_max', $montant_max, array('type'  => 'text', 'size' => '8', 'title' => 'Montant maximal'));
$row++;
$table[$row][] = form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_select")));
$table[$row][] = form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_display")));

display_form_table($table);
echo form_close();
echo "</div>";
echo form_fieldset_close();

// Lignes d'écritures
echo br();
$ajax = $this->config->item('ajax');
if ($ajax) {
    $classes = "datatable_style datatable_mini_serverside  table table-striped";
} else {
    $classes = "datatable_style datatable_mini  table table-striped";
}

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'count' => $count,
    'first' => $premier,
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => $classes
);

if ($ajax) {
    echo $this->gvvmetadata->empty_table("vue_journal", $attrs);
} else {
    echo $this->gvvmetadata->table("vue_journal", $attrs, "");
}

echo button_bar2("$controller/export_journal", array('Excel' => "button", 'Pdf' => "button", $this->lang->line("gvv_compta_button_freeze") => 'button'));

echo '</div>';
?>
<script language="JavaScript">
    <!--
    $(document).ready(function() {

        $('.datatable_mini').dataTable({
            "bFilter": false,
            "bPaginate": false,
            "bStateSave": false,
            "bJQueryUI": true,
            "bSort": false,
            "bInfo": true,
            "bAutoWidth": true,
            "sPaginationType": "full_numbers",
            "oLanguage": olanguage
        });

        $('.datatable_mini_serverside').dataTable({
            "bServerSide": true,
            "sAjaxSource": "ajax_page",
            "bFilter": true,
            "iDisplayLength": 25,
            "bPaginate": true,
            "bStateSave": false,
            "bJQueryUI": true,
            "bSort": false,
            "bInfo": true,
            "bAutoWidth": true,
            "sPaginationType": "full_numbers",
            "oLanguage": olanguage
        });

    });


    //
    -->
</script>
<?php
?>