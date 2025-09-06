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

echo '<div id="body" class="body container-fluid">';

echo heading("Rapprochement manuel", 3);

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

echo '<h4>Rapprochement manuel de l\'opération</h4>';

?>

<script>
    // Configuration variables for the JavaScript module
    window.APP_BASE_URL = '<?php echo site_url(); ?>/';
    window.STRING_RELEVE = '<?php echo $string_releve; ?>';
    window.OPERATION_AMOUNT = <?php echo $amount ?? 0; ?>;
</script>
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
            <p class="text-muted">Sélectionner une ou plusieurs écritures GVV pour le rapprochement</p>
            
            <!-- Filtres pour les écritures -->
            <div class="mb-3">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="ecriture-filter" id="filter-all" value="all" checked>
                    <label class="form-check-label" for="filter-all">Toutes les écritures</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="ecriture-filter" id="filter-non-rapprochees" value="non-rapprochees">
                    <label class="form-check-label" for="filter-non-rapprochees">Non rapprochées uniquement</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="ecriture-filter" id="filter-montant" value="montant">
                    <label class="form-check-label" for="filter-montant">Montant exact</label>
                </div>
            </div>

<?php
        echo '<div class="mt-3">';
        echo table_from_array($gvv_lines, array(
            'fields' => array('Id', 'Date', 'Montant', 'Description', 'Référence', 'Compte', 'Compte'),
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
            <button type="button" id="rapprocher-btn" class="btn btn-primary btn-lg" disabled>
                <i class="fas fa-link"></i> Effectuer le rapprochement
            </button>
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
