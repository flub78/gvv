<!-- VIEW: application/views/licences/bs_TablePerYear.php -->
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
 * Vue table pour les licences
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->load->library('DataTable');

echo '<div id="body" class="body container-fluid">';

echo heading("Licences", 3);

// Sélecteur de type de licence
echo licence_selector($controller, $type);

// Filtres dans un accordion Bootstrap
?>
<div class="row mb-3 mt-3">
    <div class="col-md-12">
        <div class="accordion" id="filtersAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFilters">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilters" aria-expanded="true" aria-controls="collapseFilters">
                        Filtres
                    </button>
                </h2>
                <div id="collapseFilters" class="accordion-collapse collapse show" aria-labelledby="headingFilters" data-bs-parent="#filtersAccordion">
                    <div class="accordion-body">
                        <!-- Filtre statut des membres -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Membres:</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="member_status" id="status_all"
                                           value="all" <?php echo ($member_status === 'all') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status_all">Tous</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="member_status" id="status_inactive"
                                           value="inactive" <?php echo ($member_status === 'inactive') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status_inactive">Non actif</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="member_status" id="status_active"
                                           value="active" <?php echo ($member_status === 'active') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status_active">Actif</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="section_selector" class="form-label">Section:</label>
                                <select class="form-select" id="section_selector" name="section_id">
                                    <option value="all" <?php echo ($section_id === 'all') ? 'selected' : ''; ?>>Toutes les sections</option>
                                    <?php foreach ($sections as $section): ?>
                                        <option value="<?php echo $section['id']; ?>" <?php echo ($section_id == $section['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($section['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Sliders pour la plage d'années -->
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Plage d'années</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="year_min_slider" class="form-label">
                                    Année de début: <span id="year_min_value"><?php echo $year_min; ?></span>
                                </label>
                                <input type="range" class="form-range" id="year_min_slider"
                                       min="<?php echo $min_year_data; ?>"
                                       max="<?php echo $current_year; ?>"
                                       value="<?php echo $year_min; ?>"
                                       step="1">
                            </div>
                            <div class="col-md-6">
                                <label for="year_max_slider" class="form-label">
                                    Année de fin: <span id="year_max_value"><?php echo $year_max; ?></span>
                                </label>
                                <input type="range" class="form-range" id="year_max_slider"
                                       min="<?php echo $min_year_data; ?>"
                                       max="<?php echo $current_year; ?>"
                                       value="<?php echo $year_max; ?>"
                                       step="1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php

echo br(1);
$table = new DataTable(array(
	'title' => "",
	'values' => $table,
	'controller' => '',
	'class' => "datatable table table-striped",
	'create' => "",
    'first' => 0));

$table->display();

// Afficher la ligne de total en dehors du DataTable
echo '<div class="row mt-2">';
echo '<div class="col-md-12">';
echo '<table class="table table-bordered table-sm" id="total-table">';
echo '<thead class="table-secondary">';
echo '<tr id="total-row">';
$col_index = 0;
foreach ($total as $value) {
    echo '<th class="text-center" data-col-index="' . $col_index . '">' . $value . '</th>';
    $col_index++;
}
echo '</tr>';
echo '</thead>';
echo '</table>';
echo '</div>';
echo '</div>';

?>
<script>
$(document).ready(function() {
    var yearMinSlider = $('#year_min_slider');
    var yearMaxSlider = $('#year_max_slider');
    var yearMinValue = $('#year_min_value');
    var yearMaxValue = $('#year_max_value');
    var updateTimeout = null;

    // Mettre à jour l'affichage des valeurs
    function updateYearDisplay() {
        var minVal = parseInt(yearMinSlider.val());
        var maxVal = parseInt(yearMaxSlider.val());

        // Empêcher le croisement
        if (minVal > maxVal) {
            yearMinSlider.val(maxVal);
            minVal = maxVal;
        }

        yearMinValue.text(minVal);
        yearMaxValue.text(maxVal);
    }

    // Gérer les changements de slider avec debounce
    function handleSliderChange() {
        updateYearDisplay();

        // Annuler le timeout précédent
        if (updateTimeout) {
            clearTimeout(updateTimeout);
        }

        // Attendre 500ms après le dernier changement avant de recharger
        updateTimeout = setTimeout(function() {
            var minVal = parseInt(yearMinSlider.val());
            var maxVal = parseInt(yearMaxSlider.val());

            // Envoyer la requête AJAX pour mettre à jour la plage
            $.ajax({
                url: '<?php echo site_url('licences/set_year_range'); ?>/' + minVal + '/' + maxVal,
                type: 'GET',
                dataType: 'json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                },
                success: function(response) {
                    if (response.success) {
                        // Recharger la page pour afficher la nouvelle plage
                        window.location.reload();
                    } else {
                        console.error('Erreur lors de la mise à jour de la plage d\'années');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX:', error);
                }
            });
        }, 500);
    }

    // Écouter les changements sur les sliders
    yearMinSlider.on('input', updateYearDisplay);
    yearMaxSlider.on('input', updateYearDisplay);
    yearMinSlider.on('change', handleSliderChange);
    yearMaxSlider.on('change', handleSliderChange);

    // Gestionnaire pour les changements de statut de membre
    $('input[name="member_status"]').on('change', function() {
        var status = $(this).val();

        // Envoyer la requête AJAX pour mettre à jour le statut
        $.ajax({
            url: '<?php echo site_url('licences/set_member_status'); ?>/' + status,
            type: 'GET',
            dataType: 'json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                if (response.success) {
                    // Recharger la page pour afficher les nouveaux membres
                    window.location.reload();
                } else {
                    console.error('Erreur lors de la mise à jour du statut');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
            }
        });
    });

    // Gestionnaire pour les changements de section
    $('#section_selector').on('change', function() {
        var sectionId = $(this).val();

        // Envoyer la requête AJAX pour mettre à jour la section
        $.ajax({
            url: '<?php echo site_url('licences/set_section'); ?>/' + sectionId,
            type: 'GET',
            dataType: 'json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                if (response.success) {
                    // Recharger la page pour afficher les membres de la section
                    window.location.reload();
                } else {
                    console.error('Erreur lors de la mise à jour de la section');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
            }
        });
    });

    // Fonction pour mettre à jour les totaux
    function updateTotals() {
        // Pour chaque colonne d'année
        $('#total-row th[data-col-index]').each(function() {
            var colIndex = parseInt($(this).data('col-index'));

            // La première colonne est "Total", on la saute
            if (colIndex === 0) {
                return;
            }

            // Compter les checkboxes cochées dans cette colonne
            var year = $(this).data('year');
            var count = 0;

            // Trouver toutes les checkboxes pour cette année
            $('.licence-checkbox').each(function() {
                var checkboxYear = $(this).data('year');
                if (checkboxYear && parseInt(checkboxYear) === year && $(this).is(':checked')) {
                    count++;
                }
            });

            // Mettre à jour le total affiché
            $(this).text(count);
        });
    }

    // Stocker l'année dans chaque cellule de total pour faciliter le comptage
    $('#total-row th[data-col-index]').each(function(index) {
        if (index > 0) { // Ignorer la première cellule "Total"
            // Récupérer l'année depuis l'en-tête du DataTable
            var yearHeader = $('.datatable thead th').eq(index);
            var year = yearHeader.text().trim();
            if (year && !isNaN(year)) {
                $(this).attr('data-year', year);
            }
        }
    });

    // Gestionnaire pour les changements de checkboxes
    $('.licence-checkbox').on('change', function() {
        var checkbox = $(this);
        var pilote = checkbox.data('pilote');
        var year = checkbox.data('year');
        var type = checkbox.data('type');
        var isChecked = checkbox.is(':checked');

        // Désactiver la checkbox pendant le traitement
        checkbox.prop('disabled', true);

        // Déterminer l'URL en fonction de l'état de la checkbox
        var url;
        if (isChecked) {
            // Cocher = créer la licence
            url = '<?php echo site_url('licences/set'); ?>/' + pilote + '/' + year + '/' + type;
        } else {
            // Décocher = supprimer la licence
            url = '<?php echo site_url('licences/switch_it'); ?>/' + pilote + '/' + year + '/' + type;
        }

        // Envoyer la requête AJAX
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                // Réactiver la checkbox
                checkbox.prop('disabled', false);

                // Vérifier si l'opération a réussi
                if (response.success) {
                    // Succès silencieux - pas de message
                    // Mettre à jour les totaux
                    updateTotals();
                } else {
                    console.error('Licence error:', response.error);
                    alert('Erreur: ' + response.error);
                    checkbox.prop('checked', !isChecked);
                }
            },
            error: function(xhr, status, error) {
                console.error('Licence AJAX error:', error);
                // En cas d'erreur, remettre la checkbox dans son état précédent
                checkbox.prop('checked', !isChecked);
                checkbox.prop('disabled', false);

                // Afficher un message d'erreur
                var errorMsg = 'Erreur lors de la mise à jour de la licence: ' + error;
                if (xhr.responseText && xhr.responseText.length < 500) {
                    errorMsg += '\n\nRéponse: ' + xhr.responseText;
                }
                alert(errorMsg);
            }
        });
    });
});
</script>
<?php

echo '</div>';

?>
