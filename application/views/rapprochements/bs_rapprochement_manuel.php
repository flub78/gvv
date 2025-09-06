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
    window.APP_BASE_URL = '<?php echo site_url(); ?>/';
</script>

<style>
.cursor-pointer {
    cursor: pointer;
}

.selected-ecriture {
    background-color: #d1ecf1;
    border: 2px solid #bee5eb;
}

.ecriture-row:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}
</style>

<script>
    // Variables pour gérer la sélection
    let selectedEcritureId = null;
    let selectedEcritureRow = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Gestion du clic sur les lignes d'écritures
        document.querySelectorAll('.ecriture-row').forEach(function(row) {
            row.addEventListener('click', function() {
                // Déselectionner la ligne précédente
                if (selectedEcritureRow) {
                    selectedEcritureRow.classList.remove('selected-ecriture');
                }
                
                // Sélectionner la nouvelle ligne
                this.classList.add('selected-ecriture');
                selectedEcritureRow = this;
                selectedEcritureId = this.getAttribute('data-ecriture-id');
                
                // Activer le bouton de rapprochement
                document.getElementById('rapprocher-btn').disabled = false;
            });
        });

        // Gestion du bouton de rapprochement
        document.getElementById('rapprocher-btn').addEventListener('click', function() {
            if (!selectedEcritureId) {
                alert('Veuillez sélectionner une écriture');
                return;
            }

            const stringReleve = '<?php echo $string_releve; ?>';
            
            // Demander confirmation
            if (!confirm('Êtes-vous sûr de vouloir rapprocher cette opération avec l\'écriture ' + selectedEcritureId + ' ?')) {
                return;
            }

            // Désactiver le bouton pendant le traitement
            this.disabled = true;
            this.textContent = 'Rapprochement en cours...';

            // Effectuer la requête AJAX
            fetch('<?php echo site_url('rapprochements/rapprocher_unique'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'string_releve=' + encodeURIComponent(stringReleve) +
                      '&ecriture_id=' + encodeURIComponent(selectedEcritureId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Succès - rediriger vers la page principale
                    alert('Rapprochement effectué avec succès !');
                    window.location.href = '<?php echo site_url('rapprochements/import_releve_from_file'); ?>';
                } else {
                    // Erreur - remettre le bouton dans son état initial
                    this.disabled = false;
                    this.textContent = 'Effectuer le rapprochement';
                    alert('Erreur lors du rapprochement: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                this.disabled = false;
                this.textContent = 'Effectuer le rapprochement';
                alert('Erreur de communication avec le serveur');
            });
        });
    });
</script>

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
            <h5>Sélectionner une écriture GVV pour le rapprochement</h5>
            <p class="text-muted">Cliquez sur une ligne pour sélectionner l'écriture à rapprocher avec l'opération bancaire ci-dessus.</p>
            
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
                    <label class="form-check-label" for="filter-montant">Montant similaire (±<?php echo $amount; ?>€)</label>
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

<script>
    // Filtrage des écritures
    document.querySelectorAll('input[name="ecriture-filter"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const filterValue = this.value;
            const operationAmount = <?php echo $amount ?? 0; ?>;
            
            document.querySelectorAll('.ecriture-row').forEach(function(row) {
                let show = true;
                
                if (filterValue === 'non-rapprochees') {
                    show = row.getAttribute('data-rapproche') === 'false';
                } else if (filterValue === 'montant') {
                    const ecritureMontant = parseFloat(row.getAttribute('data-montant'));
                    const tolerance = operationAmount * 0.1; // 10% de tolerance
                    show = Math.abs(ecritureMontant - operationAmount) <= tolerance;
                }
                
                row.style.display = show ? '' : 'none';
            });
        });
    });
</script>

<?php
echo '</div>';
$this->load->view('bs_footer');
?>
