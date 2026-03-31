<!-- VIEW: application/views/compta/bs_journalCompteView.php -->
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
                <form action="<?= controller_url($controller) . '/filterValidation/' . $compte . ($section ? '/' . $section['id'] : '') ?>" method="post" accept-charset="utf-8" name="saisie">

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
                            <?php if ($section_name): ?>
                                <div class="ms-3"><strong><?= $this->lang->line("gvv_compta_label_section") ?>:</strong> <?= $section_name ?></div>
                            <?php endif; ?>
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
                                <?= dropdown_field('id', $id, $compte_selector, "id='selector' class='big_select' style='width:400px' onchange='compte_selection();'") ?>
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

// Always use DataTables with server-side processing for all table sizes
echo '<div class="mt-3">';

// Create table structure directly for server-side DataTables
echo '<table id="journal-table" class="sql_table table table-striped table-hover">';
echo '<thead>';
echo '<tr>';
if ($has_modification_rights && $section) {
    echo '<th>Actions</th>';
}
echo '<th>Date</th>';
echo '<th>Autre compte</th>';
echo '<th>Description</th>';
echo '<th>N° chèque</th>';
echo '<th>Prix</th>';
echo '<th>Quantité</th>';
echo '<th>Débit</th>';
echo '<th>Crédit</th>';
echo '<th>Solde</th>';
echo '<th>Gel</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';
echo '</tbody>';
echo '</table>';

echo '</div>';

// Add custom CSS for action buttons styling
echo '<style>
/* Style action buttons - Edit icon blue, Delete icon red */
.sql_table .btn-primary {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.sql_table .btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}

.sql_table .btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.sql_table .btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

/* Links to accounts - simple blue links without button styling */
.sql_table tbody td a:not(.btn) {
    color: #007bff;
    text-decoration: none;
    background: none !important;
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
    display: inline !important;
}

.sql_table tbody td a:not(.btn):hover {
    color: #0056b3;
    text-decoration: underline;
}

/* Disabled button styles for frozen entries */
.sql_table .btn.disabled,
.sql_table .btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

.sql_table .btn.disabled:hover,
.sql_table .btn:disabled:hover {
    opacity: 0.4;
}

/* Highlight search terms */
.highlight {
    background-color: #ffff00;
    font-weight: bold;
    padding: 0 2px;
}
</style>';
?>

<div class="mt-4 me-3 mb-2 d-md-flex flex-row">
    <div class="me-3 mb-2"><?= $this->lang->line("gvv_compta_label_balance_at") . "  $date_fin  " ?></div>
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_compta_label_debitor") . ": " ?>
        <input type="text" name="current_debit" value="<?= $solde_fin < 0 ? euro(abs($solde_fin)) : '' ?>" readonly="readonly" />
    </div>
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_compta_label_creditor") . ": " ?>
        <input type="text" name="current_credit" value="<?= $solde_fin >= 0 ? euro($solde_fin) : '' ?>" readonly="readonly" />
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
                                "id='product_selector' class='big_select' style='width:400px' "
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

// Lien paiement bar par débit de solde (UC5) — visible uniquement si has_bar = true pour la section
if ($codec == 411 && isset($has_bar) && $has_bar && $section) {
?>
    <div class="mt-3 mb-2">
        <a href="<?= site_url('paiements_en_ligne/bar_debit_solde') ?>" class="btn btn-outline-primary">
            <?= $this->lang->line('gvv_bar_button_link') ?>
        </a>
    </div>
<?php
}

// Lien provisionnement en ligne (EF3) — visible si HelloAsso activé pour la section
if ($codec == 411 && !empty($helloasso_enabled) && $section) {
?>
    <div class="mt-2 mb-2">
        <a href="<?= site_url('paiements_en_ligne/demande') ?>" class="btn btn-outline-success">
            <?= $this->lang->line('gvv_provision_button_link') ?>
        </a>
    </div>
<?php
}

if ($this->dx_auth->is_role('tresorier')) {
    echo button_bar2("$controller/export/$compte" . ($section ? "/$section[id]" : ''), array('Excel' => "button", 'Pdf' => "button", $this->lang->line("gvv_compta_button_freeze") => 'button'));
} else {
    echo button_bar2("$controller/export/$compte" . ($section ? "/$section[id]" : ''), array('Excel' => "button", 'Pdf' => "button"));
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

        // Initialize DataTables with server-side processing using older syntax
        <?php $ajax_url = site_url('compta/datatable_journal_compte/' . $compte . ($section ? '/' . $section['id'] : '')); ?>
        console.log('Initializing DataTables with URL: <?= $ajax_url ?>');
        console.log('has_modification_rights: <?= $has_modification_rights ? "true" : "false" ?>');
        console.log('section: <?= $section ? "true" : "false" ?>');
        
        $('#journal-table').dataTable({
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "<?= $ajax_url ?>",
            "bFilter": true,
            "bPaginate": true,
            "iDisplayLength": 100,
            "bSort": false,  // Tri désactivé car les soldes doivent rester chronologiques
            "bStateSave": true,  // Sauvegarde l'état (pagination, recherche) dans localStorage
            "bInfo": true,
            "bJQueryUI": true,
            "bAutoWidth": true,
            "sPaginationType": "full_numbers",
            "aoColumns": [
                <?php if ($has_modification_rights && $section): ?>
                { "bSortable": false },                // Actions
                <?php endif; ?>
                { "sType": "date-fr", "bSortable": false },  // Date - tri désactivé car les soldes doivent rester chronologiques
                { "bSortable": false },                // Autre compte
                { "bSortable": false },                // Description
                { "bSortable": false },                // N° chèque
                { "bSortable": false },                // Prix
                { "bSortable": false },                // Quantité
                { "bSortable": false },                // Débit
                { "bSortable": false },                // Crédit
                { "bSortable": false },                // Solde
                { "bSortable": false }                 // Gel
            ],
            "fnServerData": function(sSource, aoData, fnCallback) {
                console.log('DataTables requesting data from:', sSource);
                console.log('Request parameters:', aoData);
                
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": sSource,
                    "data": aoData,
                    "success": function(json) {
                        console.log('DataTables received response:', json);
                        console.log('Number of columns in first row:', json.aaData && json.aaData.length > 0 ? json.aaData[0].length : 'no data');
                        fnCallback(json);
                    },
                    "error": function(xhr, error, thrown) {
                        console.error('DataTables AJAX error:', error, thrown);
                        console.error('Response text:', xhr.responseText);
                        console.error('Status:', xhr.status);
                    }
                });
            },
            "oLanguage": olanguage,
            "aLengthMenu": [
                [10, 25, 50, 100, 500, 1000, -1],
                [10, 25, 50, 100, 500, 1000, "Tous les"]
            ],
            "fnDrawCallback": function() {
                var oSettings = this.fnSettings();
                var searchTerm = oSettings.oPreviousSearch.sSearch;

                $('tbody td', this).each(function() {
                    var $cell = $(this);
                    // Skip cells with buttons/links to avoid breaking them
                    if ($cell.find('a, button, input').length > 0) {
                        return;
                    }

                    // Remove existing highlights first
                    var html = $cell.html();
                    if (html) {
                        html = html.replace(/<span class="highlight">(.*?)<\/span>/gi, '$1');
                        $cell.html(html);
                    }

                    // If there's a search term, highlight it
                    if (searchTerm) {
                        var text = $cell.text();
                        var regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                        var highlighted = text.replace(regex, '<span class="highlight">$1</span>');
                        if (highlighted !== text) {
                            $cell.html(highlighted);
                        }
                    }
                });
            }
        });
        
        // Handle gel checkbox clicks
        $(document).on('change', '.gel-checkbox', function() {
            var checkbox = $(this);
            var ecritureId = checkbox.data('ecriture-id');
            var isChecked = checkbox.is(':checked') ? 1 : 0;
            
            // Disable checkbox during AJAX request
            checkbox.prop('disabled', true);
            
            $.ajax({
                url: '<?= site_url("compta/toggle_gel") ?>',
                type: 'POST',
                data: {
                    id: ecritureId,
                    gel: isChecked
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Success - checkbox state already reflects the change
                        console.log('Gel status updated successfully');

                        // Find the action buttons for this entry and enable/disable them
                        var $row = checkbox.closest('tr');
                        var $editBtn = $row.find('.edit-entry-btn[data-ecriture-id="' + ecritureId + '"]');
                        var $deleteBtn = $row.find('.delete-entry-btn[data-ecriture-id="' + ecritureId + '"]');

                        if (isChecked) {
                            // Entry is now frozen - switch edit button to view mode (eye icon, same blue color)
                            $editBtn.addClass('view-mode')
                                .attr({
                                    'title': 'Visualiser',
                                    'data-frozen': '1'
                                })
                                .find('i')
                                .removeClass('fa-edit')
                                .addClass('fa-eye');

                            // Disable delete button
                            $deleteBtn.addClass('disabled').attr({
                                'disabled': true,
                                'tabindex': '-1',
                                'aria-disabled': 'true',
                                'title': 'Écriture gelée'
                            }).off('click');
                        } else {
                            // Entry is now unfrozen - switch back to edit mode
                            $editBtn.removeClass('view-mode')
                                .attr({
                                    'title': 'Modifier',
                                    'data-frozen': '0'
                                })
                                .find('i')
                                .removeClass('fa-eye')
                                .addClass('fa-edit');

                            // Enable delete button
                            $deleteBtn.removeClass('disabled').removeAttr('disabled tabindex aria-disabled').attr('title', 'Supprimer')
                                .on('click', function() {
                                    return confirm('Êtes-vous sûr de vouloir supprimer cette écriture ?');
                                });
                        }
                    } else {
                        // Error - revert checkbox state
                        checkbox.prop('checked', !isChecked);
                        alert('Erreur lors de la mise à jour: ' + (response.message || 'Erreur inconnue'));
                    }
                },
                error: function(xhr, status, error) {
                    // Network error - revert checkbox state
                    checkbox.prop('checked', !isChecked);
                    alert('Erreur de communication avec le serveur');
                    console.error('AJAX error:', error);
                },
                complete: function() {
                    // Re-enable checkbox
                    checkbox.prop('disabled', false);
                }
            });
        });
    });

    //
    -->
</script>

<?php $this->load->view('compta/bs_attachments_modal'); ?>
