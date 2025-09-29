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
 * Vue rapprochement manuel pour une StatementOperation unique
 * 
 * @package vues
 * @file bs_rapprochement_manuel.php
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('openflyers');
if (isset($gvv_bank_account)) {
    echo "<script>window.GVV_BANK_ACCOUNT = " . json_encode($gvv_bank_account) . ";</script>";
}
echo '<div id="body" class="body container-fluid">';

echo heading("Rapprochement manuel", 3);

if (isset($gvv_bank_account) && $gvv_bank_account) {
    $anchor = anchor_compte($gvv_bank_account);
    echo '<div class="mb-3">';
    echo '<strong>Compte bancaire : </strong>';
    echo $anchor;
    echo '</div>';
}

if ($status) {
    echo '<div class="border border-primary border-3 rounded p-2">';
    echo $status;
    echo '</div>';
}

if ($errors) {
    echo '<div class="border border-danger border-3 rounded p-2 mb-2">';
    foreach ($errors as $error) {
        echo '<div class="text-danger">' . $error . '</div>';
    }
    echo '</div>';
}

// Bouton de retour
echo '<div class="mb-3">';
echo '<a href="' . site_url('rapprochements/import_releve_from_file') . '" class="btn btn-secondary">';
echo '<i class="fas fa-arrow-left"></i> Retour au relevé';
echo '</a>';
echo '</div>';
?>

<!-- Filtre -->
<div class="accordion accordion-flush collapsed mb-3" id="accordionPanelsStayOpenExample">
    <div class="accordion-item">
        <h2 class="accordion-header" id="panelsStayOpen-headingOne">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                <?= $this->lang->line("gvv_str_filter") ?>
            </button>
        </h2>
        <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse  <?= $filter_active ? 'show' : '' ?>" aria-labelledby="panelsStayOpen-headingOne">
            <div class="accordion-body">
                <div>
                    <form action="<?= "filter/" ?>" method="post" accept-charset="utf-8" name="saisie">
                        <input type="hidden" name="return_url" value="<?= current_url() . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '') ?>">
                        <div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="startDate" class="form-label">Date début affichage</label>
                                    <input type="date" class="form-control" id="startDate" name="startDate" value="<?= isset($startDate) ? $startDate : '' ?>">
                                </div>
                                <div class="col">
                                    <label for="endDate" class="form-label">Date fin affichage</label>
                                    <input type="date" class="form-control" id="endDate" name="endDate" value="<?= isset($endDate) ? $endDate : '' ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Afficher</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_all" value="display_all" <?= (!isset($filter_type) || $filter_type == 'display_all') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_all">Tout</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_matched" value="filter_matched" <?= (isset($filter_type) && $filter_type == 'filter_matched') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_matched">Les écritures rapprochées</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_unmatched" value="filter_unmatched" <?= (isset($filter_type) && $filter_type == 'filter_unmatched') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_unmatched">Les écritures non rapprochées</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_unmatched_1" value="filter_unmatched_1" <?= (isset($filter_type) && $filter_type == 'filter_unmatched_1') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_unmatched_1">Non rapprochées, suggestion unique</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_unmatched_choices" value="filter_unmatched_choices" <?= (isset($filter_type) && $filter_type == 'filter_unmatched_choices') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_unmatched_choices">Non rapprochées, plusieurs choix de rapprochement</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_unmatched_multi" value="filter_unmatched_multi" <?= (isset($filter_type) && $filter_type == 'filter_unmatched_multi') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_unmatched_multi">Non rapprochées, suggestion de combinaisons</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_unmatched_O" value="filter_unmatched_0" <?= (isset($filter_type) && $filter_type == 'filter_unmatched_0') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_unmatched">Non rapprochées sans suggestions</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="type_selector" class="form-label">Type d'opération</label>
                                <?= $type_dropdown ?>
                            </div>
                        </div>
                        <div>

                            <div class="mb-2 mt-2">
                                <?= filter_buttons() ?>

                            </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
echo '<h4 class="mt-3">Rapprochement manuel de l\'opération</h4>';
?>

<script>
    // Configuration variables for the JavaScript module
    window.APP_BASE_URL = '<?php echo site_url(); ?>/';
    window.STRING_RELEVE = '<?php echo $string_releve; ?>';
    window.OPERATION_AMOUNT = <?php echo $amount ?? 0; ?>;
</script>
<script src="<?php echo base_url('assets/javascript/selectall.js'); ?>"></script>
<script src="<?php echo base_url('assets/javascript/rapprochement_manuel.js'); ?>"></script>

<div class="container-fluid">
    <!-- Affichage de l'opération à rapprocher -->
    <div class="row mb-4">
        <div class="col-12">
            <h5>Opération du relevé bancaire à rapprocher</h5>
            <?php echo $statement_operation->to_HTML(false); ?>
        </div>
    </div>

    <!-- Sélection de l'écriture GVV -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0">Sélectionner une ou plusieurs écritures GVV pour le rapprochement</p>
                <div id="amount-indicator" class="alert alert-info d-none" role="alert">
                    <small>
                        <strong>Montant opération:</strong> <span id="operation-amount"><?php echo number_format($amount, 2, ',', ' '); ?> €</span><br>
                        <strong>Montant sélectionné:</strong> <span id="selected-amount">0,00 €</span><br>
                        <strong>Écart:</strong> <span id="difference-amount">0,00 €</span>
                    </small>
                </div>
            </div>


            <?php
            echo form_open_multipart('rapprochements/rapprochez');

            // Inputs hidden pour le mode manuel
            echo '<input type="hidden" name="manual_mode" value="1">';
            echo '<input type="hidden" name="line" value="' . $line . '">';

            echo '<div class="mt-3">';

            // Créer une version modifiée du tableau pour le rapprochement manuel
            $modified_gvv_lines = array();
            foreach ($gvv_lines as $row) {
                $modified_row = array();
                foreach ($row as $index => $cell) {
                    if ($index === 0) {
                        // Remplacer cbdel_ par cb_ dans la première colonne (checkboxes)
                        // Extraire l'ID de l'écriture de la checkbox
                        if (preg_match('/cbdel_(\d+)/', $cell, $matches)) {
                            $ecriture_id = $matches[1];
                            $modified_cell = str_replace('cbdel_', 'cb_', $cell);
                            // Ajouter l'input hidden avec string_releve_{ecriture_id}
                            $modified_cell .= '<input type="hidden" name="string_releve_' . $ecriture_id . '" value="' . htmlspecialchars($string_releve) . '">';
                            $modified_row[] = $modified_cell;
                        } else {
                            $modified_row[] = $cell;
                        }
                    } else {
                        $modified_row[] = $cell;
                    }
                }
                $modified_gvv_lines[] = $modified_row;
            }

            echo table_from_array($modified_gvv_lines, array(
                'fields' => array('Id', 'Date', 'Montant', 'Description', 'Référence', 'Compte1', 'Compte2'),
                'align' => array('', 'right', 'right', 'left', 'left', 'left', 'left'),
                'class' => 'datatable_500 table'
            ));
            echo '</div>';
            ?>

        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="row mt-4">
        <div class="col-12">
            <?php
            echo form_input(array(
                'type' => 'submit',
                'name' => 'button',
                'value' => 'Effectuer le rapprochement',
                'id' => 'rapprocher-btn',
                'class' => 'btn btn-primary btn-lg'
            ));
            echo form_close();
            ?>
            <a href="<?php echo site_url('rapprochements/import_releve_from_file'); ?>" class="btn btn-secondary btn-lg ms-3">
                <i class="fas fa-times"></i> Annuler
            </a>
        </div>
    </div>
</div>

<?php
echo '</div>';
$this->load->view('bs_footer');
?>