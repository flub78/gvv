<!-- VIEW: application/views/formation_seances/form.php -->
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
 * Formulaire de création/modification de séance de formation
 * Supporte le mode inscription et le mode libre
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$is_edit = ($action === 'edit');
$form_url = $is_edit
    ? controller_url($controller) . '/update/' . $seance['id']
    : controller_url($controller) . '/store';

// Determine current mode
$current_mode = $is_libre ? 'libre' : 'inscription';

// Decode meteo from seance
$selected_meteo = array();
if (!empty($seance['meteo'])) {
    $decoded = json_decode($seance['meteo'], true);
    if (is_array($decoded)) {
        $selected_meteo = $decoded;
    }
}

// Build existing evaluations index (sujet_id => eval)
$eval_index = array();
if (!empty($existing_evaluations)) {
    foreach ($existing_evaluations as $ev) {
        $eval_index[$ev['sujet_id']] = $ev;
    }
}
?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
            <?= $is_edit ? $this->lang->line("formation_seances_edit") : $this->lang->line("formation_seances_create") ?>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("formation_seances_back") ?>
            </a>
        </div>
    </div>

    <?php
    // Display validation errors
    if (validation_errors()) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<strong><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Erreurs de validation</strong><br>';
        echo validation_errors();
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }

    if ($this->session->flashdata('error')) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i> ' . $this->session->flashdata('error');
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    ?>

    <?= form_open($form_url, array('id' => 'seance-form', 'class' => 'needs-validation', 'novalidate' => '')) ?>

        <!-- Section 1: Mode de séance -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                    <?= $this->lang->line("formation_seance_type") ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="btn-group" role="group" aria-label="Mode de séance">
                    <input type="radio" class="btn-check" name="mode_seance" id="mode_inscription" value="inscription"
                           <?= $current_mode === 'inscription' ? 'checked' : '' ?> autocomplete="off">
                    <label class="btn btn-outline-primary" for="mode_inscription">
                        <i class="fas fa-user-graduate" aria-hidden="true"></i>
                        <?= $this->lang->line("formation_seance_mode_inscription") ?>
                    </label>

                    <input type="radio" class="btn-check" name="mode_seance" id="mode_libre" value="libre"
                           <?= $current_mode === 'libre' ? 'checked' : '' ?> autocomplete="off">
                    <label class="btn btn-outline-secondary" for="mode_libre">
                        <i class="fas fa-plane" aria-hidden="true"></i>
                        <?= $this->lang->line("formation_seance_mode_libre") ?>
                    </label>
                </div>

                <!-- Info message for libre mode -->
                <div id="libre-info" class="alert alert-info mt-3 mb-0" style="display: <?= $current_mode === 'libre' ? 'block' : 'none' ?>;">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <?= $this->lang->line("formation_seance_libre_info") ?>
                </div>
            </div>
        </div>

        <!-- Section 2: Informations générales -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <?= $this->lang->line("gvv_str_informations") ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Mode inscription: select inscription -->
                    <div id="inscription-fields" style="display: <?= $current_mode === 'inscription' ? 'block' : 'none' ?>;">
                        <?php if ($inscription): ?>
                            <!-- Fixed inscription -->
                            <input type="hidden" name="inscription_id" id="inscription_id" value="<?= $inscription['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label"><?= $this->lang->line("formation_seance_inscription") ?></label>
                                <div class="form-control-plaintext">
                                    <strong><?= htmlspecialchars($inscription['pilote_prenom'] . ' ' . $inscription['pilote_nom']) ?></strong> -
                                    <?= htmlspecialchars($inscription['programme_code'] . ' - ' . $inscription['programme_titre']) ?>
                                    <?php echo get_statut_badge_seance($inscription['statut']); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Dynamic: select pilote then inscription -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="insc_pilote_id" class="form-label">
                                        <?= $this->lang->line("formation_seance_pilote") ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="insc_pilote_id" name="insc_pilote_id">
                                        <option value="">-- Sélectionnez un pilote --</option>
                                        <?php foreach ($pilotes as $id => $nom): ?>
                                            <?php if ($id): ?>
                                                <option value="<?= $id ?>"><?= htmlspecialchars($nom) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="inscription_id" class="form-label">
                                        <?= $this->lang->line("formation_seance_inscription") ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="inscription_id" name="inscription_id">
                                        <option value=""><?= $this->lang->line("formation_seance_select_inscription") ?></option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mode libre: select pilote and programme -->
                    <div id="libre-fields" style="display: <?= $current_mode === 'libre' ? 'block' : 'none' ?>;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="pilote_id" class="form-label">
                                    <?= $this->lang->line("formation_seance_pilote") ?>
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="pilote_id" name="pilote_id">
                                    <option value="">-- Sélectionnez un pilote --</option>
                                    <?php foreach ($pilotes as $id => $nom): ?>
                                        <?php if ($id): ?>
                                            <option value="<?= $id ?>"
                                                <?= (isset($seance['pilote_id']) && $seance['pilote_id'] == $id) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($nom) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="programme_id" class="form-label">
                                    <?= $this->lang->line("formation_seance_programme") ?>
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="programme_id" name="programme_id">
                                    <option value="">-- Sélectionnez un programme --</option>
                                    <?php foreach ($programmes as $id => $titre): ?>
                                        <?php if ($id): ?>
                                            <option value="<?= $id ?>"
                                                <?= (isset($seance['programme_id']) && $seance['programme_id'] == $id) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($titre) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Common fields -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="date_seance" class="form-label">
                                <?= $this->lang->line("formation_seance_date") ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="date_seance" name="date_seance"
                                   value="<?= set_value('date_seance', $seance['date_seance'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="instructeur_id" class="form-label">
                                <?= $this->lang->line("formation_seance_instructeur") ?>
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="instructeur_id" name="instructeur_id" required>
                                <option value="">-- Instructeur --</option>
                                <?php foreach ($instructeurs as $id => $nom): ?>
                                    <?php if ($id): ?>
                                        <option value="<?= $id ?>"
                                            <?= (isset($seance['instructeur_id']) && $seance['instructeur_id'] == $id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($nom) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="machine_id" class="form-label">
                                <?= $this->lang->line("formation_seance_machine") ?>
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="machine_id" name="machine_id" required>
                                <option value="">-- Aéronef --</option>
                                <?php foreach ($machines as $id => $nom): ?>
                                    <?php if ($id): ?>
                                        <option value="<?= $id ?>"
                                            <?= (isset($seance['machine_id']) && $seance['machine_id'] == $id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($nom) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="duree" class="form-label">
                                <?= $this->lang->line("formation_seance_duree") ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="duree" name="duree"
                                   placeholder="HH:MM"
                                   value="<?= set_value('duree', isset($seance['duree']) ? substr($seance['duree'], 0, 5) : '') ?>"
                                   required pattern="[0-9]{1,2}:[0-9]{2}">
                            <div class="form-text"><?= $this->lang->line("formation_seance_duree_help") ?></div>
                        </div>
                        <div class="col-md-2">
                            <label for="nb_atterrissages" class="form-label">
                                <?= $this->lang->line("formation_seance_nb_atterrissages") ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="nb_atterrissages" name="nb_atterrissages"
                                   min="1" value="<?= set_value('nb_atterrissages', $seance['nb_atterrissages'] ?? 1) ?>"
                                   required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Conditions météo -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cloud-sun" aria-hidden="true"></i>
                    <?= $this->lang->line("formation_seance_meteo") ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($meteo_options as $option): ?>
                        <div class="col-md-3 col-sm-4 col-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       id="meteo_<?= $option ?>" name="meteo_<?= $option ?>" value="1"
                                       <?= in_array($option, $selected_meteo) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="meteo_<?= $option ?>">
                                    <?= $this->lang->line("formation_seance_meteo_" . $option) ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Section 4: Évaluations -->
        <div class="card mb-3">
            <div class="card-header bg-warning">
                <h5 class="mb-0">
                    <i class="fas fa-star" aria-hidden="true"></i>
                    <?= $this->lang->line("formation_evaluations") ?>
                </h5>
            </div>
            <div class="card-body" id="evaluations-container">
                <?php if (!empty($lecons)): ?>
                    <!-- Static evaluations from known programme -->
                    <?php foreach ($lecons as $lecon): ?>
                        <div class="mb-3">
                            <h6 class="text-primary">
                                <i class="fas fa-book" aria-hidden="true"></i>
                                <?= $this->lang->line("formation_lecon") ?> <?= htmlspecialchars($lecon['numero']) ?>: <?= htmlspecialchars($lecon['titre']) ?>
                            </h6>
                            <?php if (!empty($lecon['sujets'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:60px">#</th>
                                                <th><?= $this->lang->line("formation_evaluation_sujet") ?></th>
                                                <th style="width:200px"><?= $this->lang->line("formation_evaluation_niveau") ?></th>
                                                <th style="width:250px"><?= $this->lang->line("formation_evaluation_commentaire") ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lecon['sujets'] as $sujet): ?>
                                                <?php
                                                $current_niveau = isset($eval_index[$sujet['id']]) ? $eval_index[$sujet['id']]['niveau'] : '-';
                                                $current_comment = isset($eval_index[$sujet['id']]) ? $eval_index[$sujet['id']]['commentaire'] : '';
                                                ?>
                                                <tr>
                                                    <td class="text-muted"><?= htmlspecialchars($sujet['numero']) ?></td>
                                                    <td><?= htmlspecialchars($sujet['titre']) ?></td>
                                                    <td>
                                                        <select class="form-select form-select-sm eval-niveau"
                                                                name="eval[<?= $sujet['id'] ?>][niveau]">
                                                            <option value="-" <?= $current_niveau === '-' ? 'selected' : '' ?>><?= $this->lang->line("formation_evaluation_niveau_non_aborde") ?></option>
                                                            <option value="A" <?= $current_niveau === 'A' ? 'selected' : '' ?>><?= $this->lang->line("formation_evaluation_niveau_aborde") ?></option>
                                                            <option value="R" <?= $current_niveau === 'R' ? 'selected' : '' ?>><?= $this->lang->line("formation_evaluation_niveau_a_revoir") ?></option>
                                                            <option value="Q" <?= $current_niveau === 'Q' ? 'selected' : '' ?>><?= $this->lang->line("formation_evaluation_niveau_acquis") ?></option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm"
                                                               name="eval[<?= $sujet['id'] ?>][commentaire]"
                                                               value="<?= htmlspecialchars($current_comment) ?>"
                                                               placeholder="<?= $this->lang->line("formation_evaluation_commentaire") ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Dynamic evaluations: loaded via AJAX when programme is selected -->
                    <div id="evaluations-placeholder">
                        <p class="text-muted">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            Sélectionnez un programme ou une inscription pour afficher les sujets à évaluer.
                        </p>
                    </div>
                    <div id="evaluations-dynamic"></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section 5: Commentaires -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <?= $this->lang->line("formation_seance_commentaire") ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="commentaires" class="form-label"><?= $this->lang->line("formation_seance_commentaire") ?></label>
                    <textarea class="form-control" id="commentaires" name="commentaires"
                              rows="3"><?= set_value('commentaires', $seance['commentaires'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="prochaines_lecons" class="form-label"><?= $this->lang->line("formation_seance_prochaines_lecons") ?></label>
                    <input type="text" class="form-control" id="prochaines_lecons" name="prochaines_lecons"
                           value="<?= set_value('prochaines_lecons', $seance['prochaines_lecons'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Submit buttons -->
        <div class="mb-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save" aria-hidden="true"></i> <?= $this->lang->line("formation_form_save") ?>
            </button>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line("formation_form_cancel") ?>
            </a>
        </div>

    <?= form_close() ?>
</div>

<?php
// Helper function for status badge
function get_statut_badge_seance($statut) {
    $badges = array(
        'ouverte' => '<span class="badge bg-success">Ouverte</span>',
        'suspendue' => '<span class="badge bg-warning">Suspendue</span>',
        'cloturee' => '<span class="badge bg-primary">Clôturée</span>',
        'abandonnee' => '<span class="badge bg-danger">Abandonnée</span>'
    );
    return $badges[$statut] ?? '<span class="badge bg-secondary">' . htmlspecialchars($statut) . '</span>';
}
?>

<script>
$(document).ready(function() {
    // Toggle between inscription and libre modes
    $('input[name="mode_seance"]').on('change', function() {
        var mode = $(this).val();
        if (mode === 'libre') {
            $('#inscription-fields').hide();
            $('#libre-fields').show();
            $('#libre-info').show();
            // Clear inscription field requirement
            $('#inscription_id').prop('required', false);
            $('#pilote_id').prop('required', true);
            $('#programme_id').prop('required', true);
        } else {
            $('#inscription-fields').show();
            $('#libre-fields').hide();
            $('#libre-info').hide();
            $('#inscription_id').prop('required', true);
            $('#pilote_id').prop('required', false);
            $('#programme_id').prop('required', false);
        }
    });

    // Load inscriptions when pilot is selected (inscription mode)
    $('#insc_pilote_id').on('change', function() {
        var piloteId = $(this).val();
        var $inscSelect = $('#inscription_id');
        $inscSelect.html('<option value=""><?= $this->lang->line("formation_seance_select_inscription") ?></option>');

        if (!piloteId) return;

        $.getJSON('<?= controller_url($controller) ?>/ajax_inscriptions_pilote', {pilote_id: piloteId}, function(data) {
            $.each(data, function(i, insc) {
                $inscSelect.append(
                    $('<option>').val(insc.id).text(insc.label).data('programme_id', insc.programme_id)
                );
            });
        });
    });

    // Load programme structure when inscription is selected
    $('#inscription_id').on('change', function() {
        var $selected = $(this).find(':selected');
        var programmeId = $selected.data('programme_id');
        if (programmeId) {
            loadProgrammeStructure(programmeId);
            loadMachinesForProgramme(programmeId);
        }
    });

    // Load programme structure when programme is selected (libre mode)
    $('#programme_id').on('change', function() {
        var programmeId = $(this).val();
        if (programmeId) {
            loadProgrammeStructure(programmeId);
            loadMachinesForProgramme(programmeId);
        }
    });

    // Reload machine selector based on programme type_aeronef
    function loadMachinesForProgramme(programmeId) {
        var $machineSelect = $('#machine_id');
        var currentVal = $machineSelect.val();
        $machineSelect.html('<option value="">-- Aéronef --</option>');

        if (!programmeId) return;

        $.getJSON('<?= controller_url($controller) ?>/ajax_machines_programme', {programme_id: programmeId}, function(machines) {
            $.each(machines, function(i, machine) {
                var $opt = $('<option>').val(machine.id).text(machine.nom);
                if (machine.id === currentVal) {
                    $opt.prop('selected', true);
                }
                $machineSelect.append($opt);
            });
        });
    }

    // Load programme structure via AJAX
    function loadProgrammeStructure(programmeId) {
        $.getJSON('<?= controller_url($controller) ?>/ajax_programme_structure', {programme_id: programmeId}, function(lecons) {
            var $container = $('#evaluations-dynamic');
            var $placeholder = $('#evaluations-placeholder');

            $container.empty();

            if (!lecons || lecons.length === 0) {
                $placeholder.show();
                return;
            }

            $placeholder.hide();

            $.each(lecons, function(i, lecon) {
                var html = '<div class="mb-3">';
                html += '<h6 class="text-primary"><i class="fas fa-book"></i> ';
                html += '<?= $this->lang->line("formation_lecon") ?> ' + escapeHtml(lecon.numero) + ': ' + escapeHtml(lecon.titre) + '</h6>';

                if (lecon.sujets && lecon.sujets.length > 0) {
                    html += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                    html += '<thead class="table-light"><tr>';
                    html += '<th style="width:60px">#</th>';
                    html += '<th><?= $this->lang->line("formation_evaluation_sujet") ?></th>';
                    html += '<th style="width:200px"><?= $this->lang->line("formation_evaluation_niveau") ?></th>';
                    html += '<th style="width:250px"><?= $this->lang->line("formation_evaluation_commentaire") ?></th>';
                    html += '</tr></thead><tbody>';

                    $.each(lecon.sujets, function(j, sujet) {
                        html += '<tr>';
                        html += '<td class="text-muted">' + escapeHtml(sujet.numero) + '</td>';
                        html += '<td>' + escapeHtml(sujet.titre) + '</td>';
                        html += '<td><select class="form-select form-select-sm eval-niveau" name="eval[' + sujet.id + '][niveau]">';
                        html += '<option value="-"><?= $this->lang->line("formation_evaluation_niveau_non_aborde") ?></option>';
                        html += '<option value="A"><?= $this->lang->line("formation_evaluation_niveau_aborde") ?></option>';
                        html += '<option value="R"><?= $this->lang->line("formation_evaluation_niveau_a_revoir") ?></option>';
                        html += '<option value="Q"><?= $this->lang->line("formation_evaluation_niveau_acquis") ?></option>';
                        html += '</select></td>';
                        html += '<td><input type="text" class="form-control form-control-sm" name="eval[' + sujet.id + '][commentaire]" placeholder="<?= $this->lang->line("formation_evaluation_commentaire") ?>"></td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table></div>';
                }

                html += '</div>';
                $container.append(html);
            });

            // Apply color coding to evaluation selects
            applyEvalColors();
        });
    }

    // Color coding for evaluation levels
    function applyEvalColors() {
        $(document).on('change', '.eval-niveau', function() {
            var val = $(this).val();
            $(this).removeClass('bg-light bg-info bg-warning bg-success text-white');
            switch(val) {
                case 'A': $(this).addClass('bg-info text-white'); break;
                case 'R': $(this).addClass('bg-warning'); break;
                case 'Q': $(this).addClass('bg-success text-white'); break;
                default: $(this).addClass('bg-light');
            }
        });
        // Apply initial colors
        $('.eval-niveau').trigger('change');
    }

    // Initialize colors on page load
    applyEvalColors();

    // HTML escape helper
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php $this->load->view('bs_footer'); ?>
