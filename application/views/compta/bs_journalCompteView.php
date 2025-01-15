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

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session);
?>

<h3><?= $this->lang->line("gvv_compta_title_entries") ?></h3>

<input type="hidden" name="gvv_role" value="<?= $role ?>" />
<input type="hidden" name="controller_url" value="<?= controller_url($controller) ?>" />

<div class='mb-3'>
    <?= year_selector($controller, $year, $year_selector) ?>
</div>
<input type="hidden" name="filter_active" value="<?= $filter_active ?>" />

<!-- Filtre -->
<fieldset class="coolfieldset filtre mb-3 p-2" title="<?= $this->lang->line("gvv_str_filter_tooltip") ?>">
    <legend><?= $this->lang->line("gvv_str_filter") ?></legend>

    <div>
        <form action="<?= controller_url($controller) . "/filterValidation/" . $compte ?>" method="post" accept-charset="utf-8" name="saisie">

            <div class="d-md-flex flex-row mb-2">
                <!-- date, jusqua, compte-->
                <div class="me-3 mb-2">
                    <?= $this->lang->line("gvv_date") . ": " ?>
                    <input type="text" name="filter_date" value="<?= $filter_date ?>" size="15" title="JJ/MM/AAA" class="datepicker" />
                </div>

                <div class="me-3 mb-2">
                    <?= $this->lang->line("gvv_until") . ": " ?>
                    <input type="text" name="date_end" value="<?= $date_end ?>" size="15" title="JJ/MM/AAA" class="datepicker" />
                </div>

                <div class="me-3 mb-2">
                    <?= $flt .= $this->lang->line("gvv_compta_compte") . ": " ?>
                    <?php if ($navigation_allowed) : ?>
                        <?= dropdown_field('id', $id, $compte_selector, "id='selector' class='big_select' onchange='compte_selection();'") ?>
                    <?php else : ?>
                        <input type="text" name="id" ivalue="<?= $nom ?>" size="30" readonly="readonly" />
                    <?php endif; ?>

                </div>
            </div>

            <div class="d-md-flex flex-row  mb-2">
                <!-- Tout, Vérifié, non vérifié -->
                <?php if (has_role('tresorier')) : ?>
                    <?= enumerate_radio_fields($this->lang->line("gvv_compta_type_ecriture"), 'filter_checked', $filter_checked) ?>
                <?php endif; ?>
            </div>

            <div class="d-md-flex flex-row  mb-2">
                <!-- Montant min, montant max -->
                <div class="me-3 mb-2">
                    <?= $this->lang->line("gvv_compta_montant_min") . ": " ?>
                    <input type="text" name="montant_min" value="<?= $montant_min ?>" size="8" title="Montant minimal" />
                </div>

                <div class="me-3 mb-2">
                    <?= $this->lang->line("gvv_compta_montant_max") .  ": " ?>
                    <input type="text" name="montant_max" value="<?= $montant_max ?>" size="8" title="Montant maximal" />
                </div>

                <div class="me-3 mb-2">
                    <?= enumerate_radio_fields($this->lang->line("gvv_compta_selector_debit_credit"), 'filter_debit', $filter_debit) ?>
                </div>
            </div>

            <div class="d-md-flex flex-row">
                <!-- Bouttons filtrer, afficher tout -->
                <input type="submit" name="button" value="<?= $this->lang->line("gvv_str_select") ?>" />
                <input type="submit" name="button" value="<?= $this->lang->line("gvv_str_display") ?>" />
            </div>

        </form>
    </div>
</fieldset>

<!-- Nom du club et du pilote pour facturation -->
<?php if (isset($pilote_name)) : ?>
    <fieldset class="coolfieldset filtre mb-3 p-2" title="<?= $this->lang->line("gvv_compta_filter_tooltip") ?>">
        <legend><?= $this->lang->line("gvv_compta_fieldset_addresses") . nbs() . $pilote_name ?></legend>
        <div class="d-md-flex flex-row">
            <div class="me-3 mb-3">
                <h4 class="fw-bold"><?= $this->lang->line("gvv_compta_fieldset_association") ?></h4>
                <div class="ms-3"><?= $this->config->item('nom_club') ?></div>
                <div class="ms-3"><?= $this->config->item('adresse_club') ?></div>
                <div class="ms-3"><?= $this->config->item('cp_club') . nbs(2) . $this->config->item('ville_club') ?></div>
                <div class="ms-3"><?= $this->config->item('tel_club') ?></div>
                <div class="ms-3"><?= $this->config->item('email_club') ?></div>
            </div>

            <?php if (array_key_exists('madresse', $pilote_info)) : ?>
                <div>
                    <h4 class="fw-bold"><?= $this->lang->line("gvv_compta_fieldset_pilote") ?></h4>
                    <div class="ms-3"><?= $pilote_name ?></div>
                    <div class="ms-3"><?= $pilote_info['madresse'] ?></div>
                    <div class="ms-3"><?= sprintf("%05d", $pilote_info['cp']) . nbs() . $pilote_info['ville'] ?></div>
                    <div class="ms-3"><?= $pilote_info['memail'] ?></div>
                </div>
            <?php endif; ?>

            <div>

            </div>
        </div>
    </fieldset>
<?php endif; ?>

<!-- Information du compte -->
<?php
if ($solde_avant < 0) {
    $solde_deb = euro(abs($solde_avant));
    $solde_cred = "";
} else {
    $solde_deb = "";
    $solde_cred = euro($solde_avant);
}
?>
<?php if ($compte != '') : ?>
    <fieldset class="coolfieldset filtre mb-3 p-2" title="<?= $this->lang->line("gvv_compta_filter_tooltip") ?>">
        <legend><?= $this->lang->line("gvv_compta_fieldset_compte") ?></legend>
        <div class="">
            <div class="me-3 mb-2">
                <?= $this->lang->line("gvv_compta_label_accounting_code") . ": " ?>
                <input type="text" name="codec" value="<?= $codec ?>" size="10" readonly="readonly" />
            </div>
            <div class="me-3 mb-2">
                <?= $this->lang->line("gvv_compta_label_description") . ": " ?>
                <input type="text" name="desc" value="<?= $desc ?>" size="80" readonly="readonly" />
            </div>
            <div class="me-3 mb-2 d-md-flex flex-row">
                <div class="me-3 mb-2"><?= $this->lang->line("gvv_compta_label_balance_before") . "  $date_deb  " ?></div>
                <div class="me-3 mb-2">
                    <?= $this->lang->line("gvv_compta_label_debitor") . ": " ?>
                    <input type="text" name="previous_debit" value="<?= $solde_deb ?>" readonly="readonly" />
                </div>
                <div class="me-3 mb-2">
                    <?= $this->lang->line("gvv_compta_label_creditor") . ": " ?>
                    <input type="text" name="previous_credit" value="<?= $solde_cred ?>" readonly="readonly" />
                </div>
            </div>
        </div>
    </fieldset>
<?php endif; ?>

<?php

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
    'class' => "sql_table table"
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
echo '<div class="mt-3">';
echo $this->gvvmetadata->table("vue_journal", $attrs, "");
echo '</div>';

// Solde final
if ($solde_fin < 0) {
    $solde_deb = euro(abs($solde_fin));
    $solde_cred = "";
} else {
    $solde_deb = "";
    $solde_cred = euro($solde_fin);
}

?>
<div class="me-3 mb-2 d-md-flex flex-row">
    <div class="me-3 mb-2"><?= $this->lang->line("gvv_compta_label_balance_at") . "  $date_fin  " ?></div>
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_compta_label_debitor") . ": " ?>
        <input type="text" name="current_debit" value="<?= $solde_deb ?>" readonly="readonly" />
    </div>
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_compta_label_creditor") . ": " ?>
        <input type="text" name="current_credit" value="<?= $solde_cred ?>" readonly="readonly" />
    </div>
</div>
<?php

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

    echo '<div class="me-3 mb-2 d-md-flex">';


    echo '<div class="form-group me-3 mb-2">' .
        '<label for="date">' . $this->lang->line("gvv_compta_purchase_headers")[0] . nbs(2) . '</label>';
    echo input_field('date', $date, array('type'  => 'text', 'size' => '10', 'class' => 'datepicker')) . "</div>";

    echo '<div class="me-3 mb-2">' . $this->lang->line("gvv_compta_purchase_headers")[1] . nbs(2);
    echo dropdown_field(
        'produit',
        $produit,
        $produit_selector,
        "id='product_selector' "
    ) . "</div>";

    echo '<div class="me-3 mb-2">' . $this->lang->line("gvv_compta_purchase_headers")[2] . nbs(2);
    echo input_field('quantite', $quantite, array('type'  => 'text', 'size' => '10')) . "</div>";

    echo '<div class="me-3 mb-2">' . $this->lang->line("gvv_compta_purchase_headers")[3] . nbs(2);
    echo input_field('description', $description, array('type'  => 'text', 'size' => '50')) . "</div>";

    echo  form_input(array('type' => 'submit', 'name' => 'button', 'value' => 'Validation', 'id' => 'validation_achat', 'class' => 'btn btn-success')) . "</div>";


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
                    "sType": "date-fr"
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
                    "sType": "date-fr"
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