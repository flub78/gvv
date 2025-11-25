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

        // Initialize DataTables with server-side processing using older syntax
        $('#journal-table').dataTable({
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "<?= site_url('compta/datatable_journal_compte/' . $compte) ?>",
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
            "oLanguage": olanguage,
            "aLengthMenu": [
                [10, 25, 50, 100, 500, 1000, -1],
                [10, 25, 50, 100, 500, 1000, "Tous les"]
            ]
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
                            // Entry is now frozen - disable buttons
                            $editBtn.addClass('disabled').attr({
                                'disabled': true,
                                'tabindex': '-1',
                                'aria-disabled': 'true',
                                'title': 'Écriture gelée'
                            });
                            $deleteBtn.addClass('disabled').attr({
                                'disabled': true,
                                'tabindex': '-1',
                                'aria-disabled': 'true',
                                'title': 'Écriture gelée'
                            }).off('click');
                        } else {
                            // Entry is now unfrozen - enable buttons
                            $editBtn.removeClass('disabled').removeAttr('disabled tabindex aria-disabled').attr('title', 'Modifier');
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

<!-- Attachments Modal -->
<div class="modal fade" id="attachmentsModal" tabindex="-1" aria-labelledby="attachmentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attachmentsModalLabel">
                    <i class="fas fa-paperclip"></i> Justificatifs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="attachmentsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle click on attachment paperclip icon
$(document).on('click', '.attachment-icon', function() {
    var ecritureId = $(this).data('ecriture-id');
    var date = $(this).data('date');
    var description = $(this).data('description');
    var debit = $(this).data('debit');
    var credit = $(this).data('credit');

    // Format date to locale
    var date_op = new Date(date);
    var formattedDate = date_op.toLocaleDateString();

    var amount = debit ? debit : credit;
    var modalTitle = 'Justificatifs ' + formattedDate + ' : ' + description + ' (' + amount + ' €)';
    $('#attachmentsModalLabel').html('<i class="fas fa-paperclip"></i> ' + modalTitle);

    // Open modal
    var modal = new bootstrap.Modal(document.getElementById('attachmentsModal'));
    modal.show();

    // Load attachments content
    loadAttachments(ecritureId);
});

function loadAttachments(ecritureId) {
    $('#attachmentsContent').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

    // Store ecriture_id in modal for later use
    $('#attachmentsModal').data('ecriture-id', ecritureId);

    $.ajax({
        url: '<?= site_url('compta/get_attachments_section') ?>/' + ecritureId,
        method: 'GET',
        success: function(response) {
            $('#attachmentsContent').html(response);
            initializeAttachmentHandlers();
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            console.error('Response:', xhr.responseText);
            var errorMsg = '<div class="alert alert-danger">';
            errorMsg += '<strong>Erreur lors du chargement des justificatifs.</strong><br>';
            errorMsg += 'Status: ' + status + '<br>';
            if (xhr.responseText) {
                errorMsg += 'Détails: ' + xhr.responseText;
            }
            errorMsg += '</div>';
            $('#attachmentsContent').html(errorMsg);
        }
    });
}

// Initialize inline editing handlers
function initializeAttachmentHandlers() {
    // Edit button click
    $(document).off('click', '.edit-attachment-btn').on('click', '.edit-attachment-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.view-mode').hide();
        $row.find('.edit-mode').show();
    });

    // Cancel button click
    $(document).off('click', '.cancel-edit-btn').on('click', '.cancel-edit-btn', function() {
        var $row = $(this).closest('tr');
        $row.find('.edit-mode').hide();
        $row.find('.view-mode').show();
        $row.find('.error-message').hide().text('');
    });

    // Save button click
    $(document).off('click', '.save-attachment-btn').on('click', '.save-attachment-btn', function() {
        var $btn = $(this);
        var $row = $btn.closest('tr');

        // Try multiple ways to get attachment ID
        var attachmentId = $row.data('attachment-id');
        if (!attachmentId) {
            attachmentId = $row.attr('data-attachment-id');
        }
        if (!attachmentId) {
            attachmentId = $row[0].getAttribute('data-attachment-id');
        }

        var description = $row.find('.description-input').val();
        var fileInput = $row.find('.file-input')[0];

        // Debug logging
        console.log('Save button clicked');
        console.log('Row element:', $row[0]);
        console.log('Row HTML:', $row[0].outerHTML.substring(0, 200));
        console.log('All data attributes:', $row[0].dataset);
        console.log('data() method:', $row.data('attachment-id'));
        console.log('attr() method:', $row.attr('data-attachment-id'));
        console.log('getAttribute():', $row[0].getAttribute('data-attachment-id'));
        console.log('Final attachmentId:', attachmentId);
        console.log('Description:', description);
        console.log('File input:', fileInput);
        console.log('Files selected:', fileInput ? fileInput.files.length : 0);

        // Validate attachment ID
        if (!attachmentId) {
            console.error('ERROR: Could not find attachment ID');
            $row.find('.error-message').show().text('Erreur: ID de justificatif manquant dans la page');
            return;
        }

        // Clear previous errors
        $row.find('.error-message').hide().text('');

        // Prepare form data
        var formData = new FormData();
        formData.append('attachment_id', attachmentId);
        formData.append('description', description);
        if (fileInput.files.length > 0) {
            formData.append('file', fileInput.files[0]);
        }

        // Debug: log FormData contents
        console.log('FormData contents:');
        for (var pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Disable button and show spinner
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        var ajaxUrl = '<?= base_url() ?>index.php/compta/update_attachment';
        console.log('AJAX URL:', ajaxUrl);
        console.log('jQuery version:', $.fn.jquery);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',  // Use 'type' instead of 'method' for older jQuery
            method: 'POST',  // Keep both for compatibility
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr, settings) {
                console.log('Before send - Type:', settings.type);
                console.log('Before send - URL:', settings.url);
                console.log('Before send - Data:', settings.data);
            },
            success: function(response) {
                if (response.success) {
                    // Update view mode with new values
                    $row.find('.description-text').text(response.description);
                    if (response.file_name) {
                        $row.find('.view-mode a').attr('href', response.file_url).text(response.file_name);
                    }

                    // Switch back to view mode
                    $row.find('.edit-mode').hide();
                    $row.find('.view-mode').show();

                    // Show success message
                    showSuccessToast('Justificatif mis à jour avec succès');
                } else {
                    // Show error
                    $row.find('.description-input').siblings('.error-message').text(response.error).show();
                }
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
            },
            error: function(xhr) {
                var errorMsg = 'Erreur lors de la mise à jour';
                try {
                    var response = JSON.parse(xhr.responseText);
                    errorMsg = response.error || errorMsg;
                } catch(e) {}
                $row.find('.description-input').siblings('.error-message').text(errorMsg).show();
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
            }
        });
    });

    // Delete button click
    $(document).off('click', '.delete-attachment-btn').on('click', '.delete-attachment-btn', function() {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce justificatif ?')) {
            return;
        }

        var $btn = $(this);
        var $row = $btn.closest('tr');
        var attachmentId = $row.data('attachment-id');

        // Disable button and show spinner
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '<?= base_url() ?>index.php/compta/delete_attachment',
            type: 'POST',  // Use 'type' instead of 'method' for older jQuery
            method: 'POST',  // Keep both for compatibility
            data: { attachment_id: attachmentId },
            success: function(response) {
                if (response.success) {
                    // Remove row with animation
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        // Check if table is now empty
                        if ($('#attachmentsTable tbody tr').length === 0) {
                            $('#attachmentsTable').replaceWith('<div class="alert alert-info">Aucun justificatif</div>');
                        }
                    });
                    showSuccessToast('Justificatif supprimé avec succès');
                } else {
                    alert('Erreur: ' + response.error);
                    $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Erreur lors de la suppression';
                try {
                    var response = JSON.parse(xhr.responseText);
                    errorMsg = response.error || errorMsg;
                } catch(e) {}
                alert(errorMsg);
                $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
            }
        });
    });
}

// Success toast notification
function showSuccessToast(message) {
    var toast = $('<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">')
        .html(message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    $('body').append(toast);
    setTimeout(function() {
        toast.fadeOut(300, function() { $(this).remove(); });
    }, 3000);
}

// Handle create attachment inline form
$(document).on('click', '#showCreateForm', function() {
    $('#createAttachmentCard').slideDown();
    $(this).hide();
});

$(document).on('click', '#cancelNewAttachment', function() {
    $('#createAttachmentCard').slideUp();
    $('#showCreateForm').show();
    $('#newDescription').val('');
    $('#newFile').val('');
    $('#createErrorMessage').hide().text('');
});

$(document).on('click', '#saveNewAttachment', function() {
    var $btn = $(this);
    var description = $('#newDescription').val();
    var fileInput = $('#newFile')[0];
    var ecritureId = $('#attachmentsModal').data('ecriture-id');

    // Debug logging
    console.log('Creating attachment for ecriture_id:', ecritureId);
    console.log('Description:', description);

    // Clear previous errors
    $('#createErrorMessage').hide().text('');

    // Validate
    if (!description) {
        $('#createErrorMessage').show().text('La description est requise');
        return;
    }
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        $('#createErrorMessage').show().text('Le fichier est requis');
        return;
    }

    // Prepare form data
    var formData = new FormData();
    formData.append('ecriture_id', ecritureId);
    formData.append('description', description);
    formData.append('file', fileInput.files[0]);

    // Disable button and show spinner
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

    $.ajax({
        url: '<?= base_url() ?>index.php/compta/create_attachment',
        type: 'POST',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Add new row to table
                var newRow = '<tr id="attachment-row-' + response.attachment_id + '" data-attachment-id="' + response.attachment_id + '">';
                newRow += '<td class="attachment-cell">';
                newRow += '<div class="view-mode"><span class="description-text">' + response.description + '</span></div>';
                newRow += '<div class="edit-mode" style="display: none;">';
                newRow += '<input type="text" class="form-control form-control-sm description-input" value="' + response.description + '">';
                newRow += '<div class="text-danger mt-1 error-message" style="display: none;"></div>';
                newRow += '</div></td>';
                newRow += '<td class="attachment-cell">';
                newRow += '<div class="view-mode"><a href="' + response.file_url + '" target="_blank">' + response.file_name + '</a></div>';
                newRow += '<div class="edit-mode" style="display: none;">';
                newRow += '<input type="file" class="form-control form-control-sm file-input" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">';
                newRow += '<small class="text-muted">Laissez vide pour conserver le fichier actuel</small>';
                newRow += '<div class="text-danger mt-1 error-message" style="display: none;"></div>';
                newRow += '</div></td>';
                newRow += '<td class="attachment-cell">';
                newRow += '<div class="view-mode">';
                newRow += '<button class="btn btn-sm btn-primary edit-attachment-btn" title="Modifier"><i class="fas fa-edit"></i></button> ';
                newRow += '<button class="btn btn-sm btn-danger delete-attachment-btn" title="Supprimer"><i class="fas fa-trash"></i></button>';
                newRow += '</div>';
                newRow += '<div class="edit-mode" style="display: none;">';
                newRow += '<button class="btn btn-sm btn-success save-attachment-btn" title="Enregistrer"><i class="fas fa-check"></i></button> ';
                newRow += '<button class="btn btn-sm btn-secondary cancel-edit-btn" title="Annuler"><i class="fas fa-times"></i></button>';
                newRow += '</div></td>';
                newRow += '</tr>';

                // Check if table exists
                if ($('#attachmentsTable').length === 0) {
                    // Create table
                    var tableHtml = '<table class="table table-striped table-sm" id="attachmentsTable">';
                    tableHtml += '<thead><tr><th style="width: 40%;">Description</th><th style="width: 35%;">Fichier</th><th style="width: 25%;">Actions</th></tr></thead>';
                    tableHtml += '<tbody>' + newRow + '</tbody></table>';
                    $('#showCreateForm').before(tableHtml);
                } else {
                    $('#attachmentsTable tbody').append(newRow);
                }

                // Reset form
                $('#newDescription').val('');
                $('#newFile').val('');
                $('#createAttachmentCard').slideUp();
                $('#showCreateForm').show();

                showSuccessToast('Justificatif créé avec succès');

                // Update paperclip icon count
                var $icon = $('.attachment-icon[data-ecriture-id="' + ecritureId + '"]');
                var currentCount = parseInt($icon.data('attachment-count')) || 0;
                var newCount = currentCount + 1;
                $icon.data('attachment-count', newCount);
                $icon.attr('data-attachment-count', newCount);
                $icon.attr('title', newCount + ' justificatif(s)');
                $icon.removeClass('text-muted').addClass('text-success fw-bold');
            } else {
                $('#createErrorMessage').show().text(response.error || 'Erreur lors de la création');
            }
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
        },
        error: function(xhr) {
            var errorMsg = 'Erreur lors de la création';
            try {
                var response = JSON.parse(xhr.responseText);
                errorMsg = response.error || errorMsg;
            } catch(e) {}
            $('#createErrorMessage').show().text(errorMsg);
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Enregistrer');
        }
    });
});
</script>