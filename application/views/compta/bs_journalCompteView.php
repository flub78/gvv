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

$title = $this->lang->line("gvv_compta_title_entries");
if ($section) {
    $title .= " section " . $section['nom'];
}
?>

<h3><?= $title ?></h3>

<input type="hidden" name="controller_url" value="<?= controller_url($controller) ?>" />

<div class='mb-3'>
    <?= year_selector($controller, $year, $year_selector) ?>
</div>
<input type="hidden" name="filter_active" value="<?= $filter_active ?>" />

<div class="accordion accordion-flush collapsed" id="panels">
    <!-- Filtre -->
    <div class="accordion-item">
        <h3 class="accordion-header" id="panel-filtre">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panel_filter_id" aria-expanded="true" aria-controls="panel_filter_id">
                <?= $this->lang->line("gvv_str_filter") ?>
            </button>
        </h3>
        <div id="panel_filter_id" class="accordion-collapse collapse  <?= $filter_active ? 'show' : '' ?>" aria-labelledby="panel-filtre">
            <div class="accordion-body">
                <form action="<?= controller_url($controller) . "/filterValidation/" . $compte ?>" method="post" accept-charset="utf-8" name="saisie">

                    <div class="d-md-flex flex-row mb-2">
                        <!-- date -->
                        <div class="me-3 mb-2">
                            <?= $this->lang->line("gvv_date") . ": " ?>
                            <input type="text" name="filter_date" value="<?= $filter_date ?>" size="15" title="JJ/MM/AAA" class="datepicker" />
                        </div>

                        <!-- jusqua -->
                        <div class="me-3 mb-2">
                            <?= $this->lang->line("gvv_until") . ": " ?>
                            <input type="text" name="date_end" value="<?= $date_end ?>" size="15" title="JJ/MM/AAA" class="datepicker" />
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
                        <?= filter_buttons() ?>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- Nom du club et du pilote pour facturation -->
    <?php if (isset($pilote_name)) : ?>

        <div class="accordion-item">
            <h3 class="accordion-header" id="panel-address">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                    <?= $this->lang->line("gvv_compta_fieldset_addresses") . nbs() . $pilote_name ?>
                </button>
            </h3>
            <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse" aria-labelledby="panel-address">
                <div class="accordion-body">

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
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Information du compte -->
    <?php if ($compte != '') : ?>
        <div class="accordion-item">
            <h3 class="accordion-header" id="panel-compte">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
                    <?= $this->lang->line("gvv_compta_fieldset_compte") ?>
                </button>
            </h3>
            <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse show" aria-labelledby="panel-compte">
                <div class="accordion-body">
                    <?php
                    if ($solde_avant < 0) {
                        $solde_deb = euro(abs($solde_avant));
                        $solde_cred = "";
                    } else {
                        $solde_deb = "";
                        $solde_cred = euro($solde_avant);
                    }
                    ?>

                    <div class="">
                        <!-- compte-->
                        <div class="me-3 mb-2">
                            <?= $this->lang->line("gvv_compta_compte") . ": " ?>
                            <?php if ($navigation_allowed) : ?>
                                <?= dropdown_field('id', $id, $compte_selector, "id='selector' class='big_select' style='width:300px' onchange='compte_selection();'") ?>
                            <?php else : ?>
                                <input type="text" name="id" ivalue="<?= $nom ?>" size="30" readonly="readonly" />
                            <?php endif; ?>

                        </div>
                        <div class="me-3 mb-2">
                            <?= $this->lang->line("gvv_compta_label_accounting_code") . ": " ?>
                            <input type="text" name="codec" value="<?= $codec ?>" size="10" readonly="readonly" />
                        </div>
                        <div class="me-3 mb-2">
                            <?= $this->lang->line("gvv_compta_label_section") . ": " ?>
                            <input type="text" name="section" value="<?= $section_name ?>" size="10" readonly="readonly" />
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
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>


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
    'mode' => ($has_modification_rights && $section) ? "rw" : "ro",
    'class' => "sql_table table"
);

if ($count > 400) {
    $attrs['count'] = $count;
    $attrs['first'] = $premier;
} else {
    if ($has_modification_rights && $section) {
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
if ($codec == 411 && $navigation_allowed && $section) {
?>

    <div class="accordion accordion-flush collapsed" id="achat_panel">

        <div class="accordion-item">
            <h3 class="accordion-header" id="panel-achats">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panel_purchase_id"
                    aria-expanded="true" aria-controls="panel_purchase_id">
                    <?= $this->lang->line("gvv_compta_fieldset_achats") ?>
                </button>
            </h3>
            <div id="panel_purchase_id" class="accordion-collapse collapse show" aria-labelledby="panel-achats">
                <div class="accordion-body">
                    <?= form_open(controller_url("achats") . "/formValidation/" . $action, array('name' => 'saisie')) ?>
                    <?= form_hidden('controller_url', controller_url($controller), '"id"="controller_url"') ?>
                    <?= form_hidden('saisie_par', $saisie_par, '') ?>
                    <?= form_hidden('id', 0) ?>
                    <?= form_hidden('action', $action) ?>
                    <?= form_hidden('pilote', $pilote) ?>

                    <?php if ($this->session->flashdata('popup')) {
                        echo p('<div class="error">' . $this->session->flashdata('popup') . '</div>');
                    }
                    ?>

                    <div class="d-flex flex-wrap align-items-end gap-3">
                        <div class="form-group">
                            <label for="date"><?= $this->lang->line("gvv_compta_purchase_headers")[0] ?></label>
                            <?= input_field('date', $date, array('type'  => 'text', 'size' => '10', 'class' => 'datepicker')) ?>
                        </div>

                        <div class="form-group">
                            <label for="produit"><?= $this->lang->line("gvv_compta_purchase_headers")[1] ?></label>
                            <?= dropdown_field(
                                'produit',
                                $produit,
                                $produit_selector,
                                "id='product_selector' class='big_select' style='width:300px' "
                            ) ?>
                        </div>

                        <div class="form-group">
                            <label for="quantite"><?= $this->lang->line("gvv_compta_purchase_headers")[2] ?></label>
                            <?= input_field('quantite', $quantite, array('type'  => 'text', 'size' => '10')) ?>
                        </div>

                        <div class="form-group">
                            <label for="description"><?= $this->lang->line("gvv_compta_purchase_headers")[3] ?></label>
                            <?= input_field('description', $description, array('type'  => 'text', 'size' => '50')) ?>
                        </div>

                        <?= form_input(array('type' => 'submit', 'name' => 'button', 'value' => 'Validation', 'id' => 'validation_achat', 'class' => 'btn btn-success')) ?>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php
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
            "iDisplayLength": 100,
            "bSort": true,
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
            "oLanguage": olanguage,

            // Add the page length menu options
            "aLengthMenu": [
                [10, 25, 50, 100, 500, 1000, -1],
                [10, 25, 50, 100, 500, 1000, "Tous les"]
            ]
        });

        $('.datedtable_ro').dataTable({
            "bFilter": true,
            "bPaginate": true,
            "iDisplayLength": 100,
            "bSort": true,
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
            "oLanguage": olanguage,

            // Add the page length menu options
            "aLengthMenu": [
                [10, 25, 50, 100, 500, 1000, -1],
                [10, 25, 50, 100, 500, 1000, "Tous les"]
            ]
        });
    });

    //
    -->
</script>