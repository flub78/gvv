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

echo heading("gvv_of_title_operations", 3);

if ($status) {
    echo '<div class="border border-primary border-3 rounded p-2">';
    echo $status;
    echo '</div>';
}

$client_list = $this->comptes_model->selector_with_all(["codec =" => "411"], TRUE);
$attrs = 'class="form-control big_select" ';
$compte_dropdown = form_dropdown('current_client', $client_list, $current_client, $attrs);
?>

<h5>
    <?=$titre?>
</h5>
<p>
    <?=$date_edition?>
</p>

<!-- Filtre -->
<div class="accordion accordion-flush collapsed mb-3" id="accordionPanelsStayOpenExample">
    <div class="accordion-item">
        <h2 class="accordion-header" id="panelsStayOpen-headingOne">
            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true"
                aria-controls="panelsStayOpen-collapseOne">
                <?= $this->lang->line("gvv_str_filter") ?>
            </button>
        </h2>
        <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse  <?= $filter_active ? 'show' : '' ?>"
            aria-labelledby="panelsStayOpen-headingOne">
            <div class="accordion-body">
                <div>
                    <form action="<?= " filter/" . $action ?>" method="post" accept-charset="utf-8" name="saisie">
                        <div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="startDate" class="form-label">Date début affichage</label>
                                    <input type="date" class="form-control" id="startDate" name="startDate"
                                        value="<?= isset($startDate) ? $startDate : '' ?>">
                                </div>
                                <div class="col">
                                    <label for="endDate" class="form-label">Date fin affichage</label>
                                    <input type="date" class="form-control" id="endDate" name="endDate"
                                        value="<?= isset($endDate) ? $endDate : '' ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Afficher</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_all"
                                        value="display_all" <?=(!isset($filter_type) || $filter_type=='display_all' )
                                        ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_all">Tout</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_matched"
                                        value="filter_matched" <?=(isset($filter_type) && $filter_type=='filter_matched'
                                        ) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_matched">Les opérations
                                        synchronisées</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type"
                                        id="filter_unmatched" value="filter_unmatched" <?=(isset($filter_type) &&
                                        $filter_type=='filter_unmatched' ) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_unmatched">Les opérations non
                                        synchronisées</label>
                                </div>

                            </div>
                            <div class="col-6">
                                <label for="type_selector" class="form-label">Compte client (411)</label>
                                <?= $compte_dropdown ?>
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

<div class="actions mb-3 mt-3">
    <button type="button" class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
    <button type="button" class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
</div>

<!-- Onglets -->
<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="openflyers-tab" data-bs-toggle="tab" data-bs-target="#openflyers"
            type="button" role="tab" aria-controls="openflyers" aria-selected="true">
            OpenFlyers
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="gvv-tab" data-bs-toggle="tab" data-bs-target="#gvv" type="button" role="tab"
            aria-controls="gvv" aria-selected="false">
            Ecritures GVV
        </button>
    </li>
</ul>

<script>
// Restore active tab on page load
document.addEventListener('DOMContentLoaded', function() {
    let activeTab = localStorage.getItem('activeTab');
    if (activeTab) {
        const tab = new bootstrap.Tab(document.querySelector(activeTab));
        tab.show();
    }
});

// Store active tab when changed
document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function(tab) {
    tab.addEventListener('shown.bs.tab', function(e) {
        localStorage.setItem('activeTab', '#' + e.target.id);
    });
});
</script>

<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="openflyers" role="tabpanel" aria-labelledby="openflyers-tab">
        <?php
            echo form_open_multipart('openflyers/create_operations');
            echo $comptes_html;
        ?>

        <div class="actions mb-3">
            <button type="button" class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
            <button type="button" class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
        </div>

        <?php
if ($section) echo form_input(array(
	'type' => 'submit',
	'name' => 'button',
	'value' => $this->lang->line("gvv_of_import_opérations"),
	'class' => 'btn btn-primary mb-4'
));
echo form_close();
        ?>

    </div>

    <div class="tab-pane fade" id="gvv" role="tabpanel" aria-labelledby="gvv-tab">

        <!-- Onglet GVV -->

        <?php   

            echo form_open_multipart('openflyers/delete_all_ecritures');

            echo '<div class="mt-3">';
                echo table_from_array ($gvv_lines, array(
                'fields' => array('', 'Date', 'Montant', 'Description', 'Référence', 'Compte', 'Compte'),
                'align' => array('', 'right','right', 'left', 'left', 'left', 'left'),
                    'class' => 'datatable table'
                ));
            echo '</div>';
        
        ?>

        <div class="actions mb-3 mt-3">
            <button type="button" class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
            <button type="button" class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
        </div>

         <?php
         if ($section) {

    echo form_input(array(
        'type' => 'submit',
        'name' => 'button',
        'value' => "Supprimez la selection",
        'class' => 'btn btn-danger mb-4'
    ));
}
echo form_close();
         ?>
        
        <p class="mt-2">Les écritures présentes dans GVV et absente de l'import OpenFlyers sont en rouge, exemple:
            <span class="bg-danger badge text-white rounded-pill">OpenFlyers : 33590</span>
        </p>
        <p class="mt-2">Une écriture est synchronisée si le champ "Référence" contient un numéro d'écriture qui existe dans OpenFlyers. Si des modifications sont apportées dans OpenFlyers après synchronisation, et des écritures supprimées, il faut les supprimer de GVV, sous faute de désynchroniser les soldes OpenFlyers et GVV.
        </p>

    </div>
</div>

<?php