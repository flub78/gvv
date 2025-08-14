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
 * Vue table pour les terrains
 * 
 * @package vues
 * @file bs_tableOperations.php
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('openflyers');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_rapprochements_title", 3);

if ($status) {
    echo '<div class="border border-primary border-3 rounded p-2">';
    echo $status;
    echo '</div>';
}

if ($errors) {
    echo '<div class="border border-danger border-3 rounded p-2">';
    foreach ($errors as $error) {
        echo '<div class="text-danger">' . $error . '</div>';
    }
    echo '</div>';
}

// Affiche l'en-tête
echo '<div class="border border-secondary border-3 rounded p-2 mb-3">';
echo table_from_array($header, ['class' => ' table border']);
echo '</div>';

echo '<h4>Opérations' . $this->lang->line("gvv_rapprochements_title_operations") . '</h4>';

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
                    <form action="<?= "filter/" . $action ?>" method="post" accept-charset="utf-8" name="saisie">
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
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_unmatched" value="filter_unmatched" <?= (isset($filter_type) && $filter_type == 'filter_unmatched') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_unmatched">Les écritures non rapprochées</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_matched" value="filter_matched" <?= (isset($filter_type) && $filter_type == 'filter_matched') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_matched">Les écritures rapprochées</label>
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

<div class="border rounded p-2 mb-3">

    <div class="row mb-3">
        <div class="col-md-8 d-flex align-items-center">
            <label for="maxDays" class="form-label me-2 mb-0">Nombre de jours maximum entre le relevé et l'opération</label>
            <input type="number" class="form-control" id="maxDays" name="maxDays" min="0" style="width: 5em;">
        </div>
        <div class="col-md-4">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="smartMode" name="smartMode">
                <label class="form-check-label" for="smartMode">Smart mode</label>
            </div>
        </div>
    </div>
</div>

<div class="actions mb-3 mt-3">
    <button type="button" class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
    <button type="button" class="btn btn-primary" onclick="selectUniques()">Sélectionnez uniques</button>
    <button type="button" class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
</div>
<?php

echo form_open_multipart('rapprochements/rapprochez');

echo $html_tables;


/**
 * Filtrage par
 *    toutes, écritures rapprochées, écritures non rapprochées
 *    type virement_recu, virement_emis, chèque, espèces
 *    date de début, date de fin
 */
?>
<div class="actions mb-3">
    <button type="button" class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
    <button type="button" class="btn btn-primary" onclick="selectUniques()">Sélectionnez uniques</button>
    <button type="button" class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
</div>
<?php


if ($section) {
    echo form_input(array(
        'type' => 'submit',
        'name' => 'button',
        'value' => $this->lang->line("gvv_rapproche"),
        'class' => 'btn btn-primary mb-4 me-2'
    ));
    echo form_input(array(
        'type' => 'submit',
        'name' => 'button',
        'value' => $this->lang->line("gvv_delete_rapproche"),
        'class' => 'btn btn-danger mb-4'
    ));
}
echo form_close('</div>');
echo '</div>';

?>
<script>
    // Callback function called when select changes
    function associateAccount(selectElement, str) {
        const cptGVV = selectElement.value;

        console.log("associateAccount, cpt GVV=" + cptGVV + ", str=" + str);

        // Call server to associate account
        fetch('<?= site_url() ?>/associations_releve/associate?string_releve=' + str + '&cptGVV=' + encodeURIComponent(cptGVV))
            .then(response => response.json())
            .then(data => console.log('Association response:', data))
            .catch(error => console.error('Error:', error));

        location.reload();
    }

    // Toggle row selection
    function toggleRowSelection(checkbox) {
        const row = checkbox.closest('tr');
        if (checkbox.checked) {
            row.classList.add('selected-row');
        } else {
            row.classList.remove('selected-row');
        }
    }

    // Select all rows
    function selectAll() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]:not(#smartMode)');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
            // toggleRowSelection(checkbox);
        });
        console.log('All rows selected');
    }

    // Deselect all rows
    function deselectAll() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]:not(#smartMode)');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            //toggleRowSelection(checkbox);
        });
        console.log('All rows deselected');
    }

    function selectUniques() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]:not(#smartMode)');
        checkboxes.forEach(checkbox => {
            if (checkbox.classList.contains('unique')) {
                checkbox.checked = true;
                // toggleRowSelection(checkbox);
            } else {
                checkbox.checked = false;
                // toggleRowSelection(checkbox);
            }
        });
        console.log('Unique rows selected');
    }
</script>