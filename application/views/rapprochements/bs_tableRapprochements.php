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
                    <form action="<?= "filter/" ?>" method="post" accept-charset="utf-8" name="saisie">
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

<!-- Onglets -->
<ul class="nav nav-tabs mt-3" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="openflyers-tab" data-bs-toggle="tab" data-bs-target="#openflyers"
            type="button" role="tab" aria-controls="openflyers" aria-selected="true">
            Relevé de banque
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="gvv-tab" data-bs-toggle="tab" data-bs-target="#gvv" type="button" role="tab"
            aria-controls="gvv" aria-selected="false">
            Ecritures GVV
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="saisie-tab" data-bs-toggle="tab" data-bs-target="#saisie" type="button" role="tab"
            aria-controls="saisie" aria-selected="false">
            Saisie assistée des écritures GVV
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

    // Gestion du rapprochement automatique pour les suggestions uniques
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('auto-reconcile-btn')) {
            e.preventDefault();

            const button = e.target;
            const stringReleve = button.getAttribute('data-string-releve');
            const ecritureId = button.getAttribute('data-ecriture-id');
            const line = button.getAttribute('data-line');

            // Désactiver le bouton pendant le traitement
            button.disabled = true;
            button.textContent = 'En cours...';

            // Effectuer la requête AJAX
            fetch('<?php echo base_url('rapprochements/rapprocher_unique'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'string_releve=' + encodeURIComponent(stringReleve) +
                        '&ecriture_id=' + encodeURIComponent(ecritureId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Succès - recharger la page pour que les impacts sur les autres opérations soient pris en compte
                        window.location.href = '<?php echo base_url('rapprochements/import_releve_from_file'); ?>';
                    } else {
                        // Erreur - remettre le bouton dans son état initial
                        button.disabled = false;
                        button.textContent = 'Rapprocher';
                        alert('Erreur lors du rapprochement: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    button.disabled = false;
                    button.textContent = 'Rapprocher';
                    alert('Erreur de communication avec le serveur');
                });
        }

        // Gestion de la suppression du rapprochement
        if (e.target.classList.contains('auto-unreconcile-btn')) {
            e.preventDefault();

            const button = e.target;
            const stringReleve = button.getAttribute('data-string-releve');
            const line = button.getAttribute('data-line');

            // Demander confirmation
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce rapprochement ?')) {
                return;
            }

            // Désactiver le bouton pendant le traitement
            button.disabled = true;
            button.textContent = 'Suppression...';

            // Effectuer la requête AJAX de suppression
            fetch('<?php echo base_url('rapprochements/supprimer_rapprochement_unique'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'string_releve=' + encodeURIComponent(stringReleve)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recharger la page pour que les impacts sur les autres opérations soient pris en compte
                        window.location.href = '<?php echo base_url('rapprochements/import_releve_from_file'); ?>';
                    } else {
                        // Erreur - remettre le bouton dans son état rapproché
                        button.disabled = false;
                        button.textContent = 'Rapproché';
                        alert('Erreur lors de la suppression: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    button.disabled = false;
                    button.textContent = 'Rapproché';
                    alert('Erreur de communication avec le serveur');
                });
        }

        // Gestion du rapprochement pour les choix multiples
        if (e.target.classList.contains('auto-reconcile-multiple-btn')) {
            e.preventDefault();

            const button = e.target;
            const stringReleve = button.getAttribute('data-string-releve');
            const line = button.getAttribute('data-line');

            // Récupérer la valeur sélectionnée dans le dropdown
            const dropdown = document.querySelector('select[name="op_' + line + '"]');
            if (!dropdown || !dropdown.value) {
                alert('Veuillez sélectionner une écriture dans la liste déroulante');
                return;
            }

            const ecritureId = dropdown.value;

            // Désactiver le bouton pendant le traitement
            button.disabled = true;
            button.textContent = 'En cours...';

            // Effectuer la requête AJAX
            fetch('<?php echo base_url('rapprochements/rapprocher_unique'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'string_releve=' + encodeURIComponent(stringReleve) +
                        '&ecriture_id=' + encodeURIComponent(ecritureId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Succès - rediriger vers import_releve_from_file pour recharger et propager les effets
                        window.location.href = '<?php echo base_url('rapprochements/import_releve_from_file'); ?>';
                    } else {
                        // Erreur - remettre le bouton dans son état initial
                        button.disabled = false;
                        button.textContent = 'Rapprocher';
                        alert('Erreur lors du rapprochement: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    button.disabled = false;
                    button.textContent = 'Rapprocher';
                    alert('Erreur de communication avec le serveur');
                });
        }

        // Gestion du rapprochement pour les combinaisons multiples
        if (e.target.classList.contains('auto-reconcile-combination-btn')) {
            e.preventDefault();

            const button = e.target;
            const stringReleve = button.getAttribute('data-string-releve');
            const line = button.getAttribute('data-line');
            const ecritureIdsJson = button.getAttribute('data-ecriture-ids');
            
            let ecritureIds;
            try {
                ecritureIds = JSON.parse(ecritureIdsJson);
            } catch (error) {
                alert('Erreur lors de la lecture des IDs des écritures');
                return;
            }

            if (!ecritureIds || ecritureIds.length === 0) {
                alert('Aucune écriture à rapprocher');
                return;
            }

            // Désactiver le bouton pendant le traitement
            button.disabled = true;
            button.textContent = 'En cours...';

            // Effectuer la requête AJAX avec tous les IDs d'écritures
            fetch('<?php echo base_url('rapprochements/rapprocher_multiple'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'string_releve=' + encodeURIComponent(stringReleve) + 
                      '&ecriture_ids=' + encodeURIComponent(ecritureIdsJson)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Succès - recharger la page
                    window.location.href = '<?php echo base_url('rapprochements/import_releve_from_file'); ?>';
                } else {
                    // Erreur - remettre le bouton dans son état initial
                    button.disabled = false;
                    button.textContent = 'Non rapproché';
                    let errorMessage = 'Erreur lors du rapprochement: ' + data.message;
                    if (data.errors && data.errors.length > 0) {
                        errorMessage += '\\n\\nDétails:\\n' + data.errors.join('\\n');
                    }
                    alert(errorMessage);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                button.disabled = false;
                button.textContent = 'Non rapproché';
                alert('Erreur de communication avec le serveur');
            });
        }
    });
</script>

<div class="tab-content" id="myTabContent">
    <!-- Onglet Relevé de banque -->
    <div class="tab-pane fade show active" id="openflyers" role="tabpanel" aria-labelledby="openflyers-tab">

        <!-- Delta et smart mode -->
        <div class="border rounded p-2 mb-3 mt-3">

            <div class="row mb-3">
                <div class="col-md-8 d-flex align-items-center">
                    <label for="maxDays" class="form-label me-2 mb-0">Nombre de jours maximum entre le relevé et l'opération</label>
                    <input type="number" class="form-control" id="maxDays" name="maxDays" min="0" style="width: 5em;" onchange="maxDaysChanged(this);" value="<?= $maxDays ?>">
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <label class="form-check-label" for="smartMode">Smart mode</label>
                        <input type="checkbox" class="form-check-input" id="smartMode" name="smartMode" onchange="smartModeChanged(this)" <?= $smartMode ? 'checked' : '' ?>>
                    </div>
                </div>
            </div>
        </div>

        <!-- Boutons de sélection -->
        <?php if ($count_selected): ?>

            <div class="actions mb-3 mt-3">
                <button type="button" class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
                <button type="button" class="btn btn-primary" onclick="selectUniques()">Sélectionnez uniques</button>
                <button type="button" class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
            </div>
        <?php endif; ?>
        <?php
        echo form_open_multipart('rapprochements/rapprochez');
        echo $html_tables;

        if (!$count_selected) {
            echo p("La selection est vide.");
        }
        ?>
        <!-- Boutons de sélection -->
        <?php if ($count_selected): ?>
            <div class="actions mb-3">
                <button type="button" class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
                <button type="button" class="btn btn-primary" onclick="selectUniques()">Sélectionnez uniques</button>
                <button type="button" class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
            </div>
        <?php endif; ?>

        <!-- Boutons d'actions -->
        <?php
        if ($section && $count_selected) {
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
        echo form_close();

        ?>
    </div>

    <!-- Onglet GVV -->
    <div class="tab-pane fade" id="gvv" role="tabpanel" aria-labelledby="gvv-tab">
        <?php
        echo form_open_multipart('rapprochements/delete_all');
        ?>
        <div class="actions mb-3 mt-3">
            <button type="button" class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
            <button type="button" class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
        </div>
        <?php

        echo '<div class="mt-3">';
        echo table_from_array($gvv_lines, array(
            'fields' => array('Id', 'Date', 'Montant', 'Description', 'Référence', 'Compte', 'Compte'),
            'align' => array('', 'right', 'right', 'left', 'left', 'left', 'left'),
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
                'value' => "Supprimez les rapprochements sélectionnés",
                'class' => 'btn btn-danger mb-4'
            ));

            echo form_input(array(
                'type' => 'submit',
                'name' => 'button',
                'value' => "Supprimez les écritures sélectionnées",
                'class' => 'btn btn-danger ms-2 mb-4'
            ));
        }
        ?>

        <?php
        echo form_close();
        ?>
    </div>

    <!-- Onglet Saisie assistée -->
    <div class="tab-pane fade" id="saisie" role="tabpanel" aria-labelledby="saisie-tab">
        <p class="mt-2">Saisie assistée des écritures GVV</p>
        <?php
        echo form_open_multipart('rapprochements/auto_create_operations');
        ?>
        <div class="actions mb-3 mt-3">
            <button type="button" class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
            <button type="button" class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
        </div>
        <?php
        echo form_open_multipart('rapprochements/delete_all');

        echo '<div class="mt-3">';
        echo table_from_array($gvv_lines, array(
            'fields' => array('Id', 'Date', 'Montant', 'Description', 'Référence', 'Compte', 'Compte'),
            'align' => array('', 'right', 'right', 'left', 'left', 'left', 'left'),
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
                'value' => "Générer les écritures",
                'class' => 'btn btn-primary mb-4'
            ));
        }
        echo form_close();
        ?>
        <p>Les écritures sont générées et rapprochées.</p>
    </div>



    <?php
    echo '</div>';

    ?>