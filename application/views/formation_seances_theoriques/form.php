<?php
/**
 * Vue : formulaire création/édition d'une séance théorique
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$is_edit  = ($action === 'edit');
$form_url = $is_edit
    ? controller_url($controller) . '/update/' . $seance['id']
    : controller_url($controller) . '/store';
$title    = $is_edit
    ? $this->lang->line('formation_seance_theorique_edit')
    : $this->lang->line('formation_seance_theorique_create');

// Participants initiaux pour le composant JS
$initial_participants = array();
foreach ($participants_data as $p) {
    $initial_participants[] = array(
        'id'    => $p['pilote_id'],
        'label' => trim(($p['mnom'] ?? '') . ' ' . ($p['mprenom'] ?? '')),
    );
}
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-chalkboard" aria-hidden="true"></i>
            <?= $title ?>
        </h3>
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Retour
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?= form_open($form_url, array('class' => 'card')) ?>
    <div class="card-body">

        <!-- Date -->
        <div class="mb-3 row">
            <label for="date_seance" class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_seance_date') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-3">
                <input type="date" class="form-control" id="date_seance" name="date_seance"
                       value="<?= htmlspecialchars($seance['date_seance'] ?? date('Y-m-d')) ?>"
                       required>
            </div>
        </div>

        <!-- Type de séance -->
        <div class="mb-3 row">
            <label for="type_seance_id" class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_type_seance_nom') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-4">
                <select class="form-select" id="type_seance_id" name="type_seance_id" required>
                    <option value="">-- Choisir un type --</option>
                    <?php foreach ($types_seance as $id => $nom): ?>
                        <?php if ($id): ?>
                            <option value="<?= $id ?>"
                                <?= ($seance['type_seance_id'] ?? '') == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nom) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Instructeur -->
        <div class="mb-3 row">
            <label for="instructeur_id" class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_seance_instructeur') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-4">
                <select class="form-select" id="instructeur_id" name="instructeur_id" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($instructeurs as $id => $nom): ?>
                        <?php if ($id): ?>
                            <option value="<?= $id ?>"
                                <?= ($seance['instructeur_id'] ?? '') == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nom) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Programme (optionnel) -->
        <div class="mb-3 row">
            <label for="programme_id" class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_seance_programme') ?>
            </label>
            <div class="col-sm-4">
                <select class="form-select" id="programme_id" name="programme_id">
                    <option value="">-- Aucun --</option>
                    <?php foreach ($programmes as $id => $titre): ?>
                        <?php if ($id): ?>
                            <option value="<?= $id ?>"
                                <?= ($seance['programme_id'] ?? '') == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($titre) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Lieu (optionnel) -->
        <div class="mb-3 row">
            <label for="lieu" class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_seance_lieu') ?>
            </label>
            <div class="col-sm-4">
                <input type="text" class="form-control" id="lieu" name="lieu"
                       value="<?= htmlspecialchars($seance['lieu'] ?? '') ?>"
                       maxlength="255"
                       placeholder="<?= htmlspecialchars($this->lang->line('formation_seance_lieu_placeholder')) ?>">
            </div>
        </div>

        <!-- Durée (optionnel) -->
        <div class="mb-3 row">
            <label for="duree" class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_seance_duree_cours') ?>
            </label>
            <div class="col-sm-2">
                <input type="time" class="form-control" id="duree" name="duree"
                       value="<?= htmlspecialchars(isset($seance['duree']) ? substr($seance['duree'], 0, 5) : '') ?>">
            </div>
        </div>

        <!-- Participants -->
        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_seance_participants') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-6">
                <!-- Sélecteur big_select + bouton ajouter -->
                <div class="d-flex gap-2 align-items-start mb-2">
                    <div class="flex-grow-1">
                        <select class="form-select big_select_large" id="participant-select">
                            <option value=""></option>
                            <?php foreach ($membres as $id => $nom): ?>
                                <?php if ($id): ?>
                                    <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($nom) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-success" id="add-participant" title="Ajouter">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                    </button>
                </div>
                <!-- Liste des participants sélectionnés -->
                <ul class="list-group" id="participants-list"></ul>
                <!-- Champs hidden soumis avec le formulaire -->
                <div id="participants-inputs"></div>
            </div>
        </div>

        <!-- Commentaires (optionnel) -->
        <div class="mb-3 row">
            <label for="commentaires" class="col-sm-3 col-form-label">
                <?= $this->lang->line('formation_seance_commentaires') ?>
            </label>
            <div class="col-sm-6">
                <textarea class="form-control" id="commentaires" name="commentaires"
                          rows="3"><?= htmlspecialchars($seance['commentaires'] ?? '') ?></textarea>
            </div>
        </div>

    </div>
    <div class="card-footer text-end">
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary me-2">
            <i class="fas fa-times" aria-hidden="true"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save" aria-hidden="true"></i>
            <?= $is_edit ? 'Enregistrer' : 'Créer' ?>
        </button>
    </div>
    <?= form_close() ?>

</div>

<script>
$(document).ready(function () {
    var participants = {};

    // Participants initiaux (édition)
    <?php foreach ($initial_participants as $p): ?>
    participants[<?= json_encode($p['id']) ?>] = <?= json_encode($p['label']) ?>;
    <?php endforeach; ?>

    function render() {
        var $list   = $('#participants-list');
        var $inputs = $('#participants-inputs');
        $list.empty();
        $inputs.empty();

        var ids = Object.keys(participants);
        if (ids.length === 0) {
            $list.append('<li class="list-group-item text-muted fst-italic"><?= $this->lang->line('formation_seance_participants_aucun') ?></li>');
        } else {
            ids.forEach(function (id) {
                var label = participants[id];
                var $li = $('<li class="list-group-item d-flex justify-content-between align-items-center">')
                    .append($('<span>').html('<i class="fas fa-user me-2" aria-hidden="true"></i>' + $('<span>').text(label).html()))
                    .append($('<button type="button" class="btn btn-sm btn-outline-danger">').html('<i class="fas fa-times" aria-hidden="true"></i>')
                        .on('click', function () {
                            delete participants[id];
                            render();
                        })
                    );
                $list.append($li);
                $inputs.append($('<input type="hidden" name="participants[]">').val(id));
            });
        }
    }

    $('#add-participant').on('click', function () {
        var $sel = $('#participant-select');
        var id   = $sel.val();
        if (!id || participants[id]) return;
        var label = $sel.find('option:selected').text().trim();
        participants[id] = label;
        $sel.val(null).trigger('change');  // reset select2
        render();
    });

    render();
});
</script>

<?php $this->load->view('bs_footer'); ?>
