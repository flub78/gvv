<!-- VIEW: application/views/formation_autorisations_solo/form.php -->
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
 * Formulaire création/édition autorisation de vol solo
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('formation');
$this->lang->load('gvv');

$is_edit = !empty($autorisation['id']);
$title = $is_edit ? $this->lang->line("formation_autorisations_solo_edit") : $this->lang->line("formation_autorisations_solo_create");

?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
            <?= $title ?>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("formation_autorisations_solo_back") ?>
            </a>
        </div>
    </div>

    <?php
    // Display flash messages
    if ($this->session->flashdata('error')) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i> ' . $this->session->flashdata('error');
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }

    // Display validation errors
    if (validation_errors()) {
        echo '<div class="alert alert-danger">';
        echo '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i> ';
        echo validation_errors();
        echo '</div>';
    }
    ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= controller_url($controller) ?>/<?= $action ?>">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="inscription_id" class="form-label">
                            <?= $this->lang->line("formation_autorisation_solo_formation") ?> <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="inscription_id" name="inscription_id" required>
                            <?php foreach ($inscriptions as $id => $label): ?>
                                <option value="<?= $id ?>" <?= (isset($autorisation['inscription_id']) && $autorisation['inscription_id'] == $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Sélectionnez la formation de l'élève</div>
                    </div>

                    <div class="col-md-6">
                        <label for="date_autorisation" class="form-label">
                            <?= $this->lang->line("formation_autorisation_solo_date") ?> <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control" id="date_autorisation" name="date_autorisation"
                               value="<?= htmlspecialchars($autorisation['date_autorisation'] ?? date('Y-m-d')) ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="instructeur_id" class="form-label">
                            <?= $this->lang->line("formation_autorisation_solo_instructeur") ?> <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="instructeur_id" name="instructeur_id" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($instructeurs as $id => $nom): ?>
                                <option value="<?= $id ?>" <?= (isset($autorisation['instructeur_id']) && $autorisation['instructeur_id'] == $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nom) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="machine_id" class="form-label">
                            <?= $this->lang->line("formation_autorisation_solo_machine") ?> <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="machine_id" name="machine_id" required>
                            <option value="">-- Sélectionner une formation d'abord --</option>
                        </select>
                        <div class="form-text">L'aéronef est filtré selon le type du programme</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="consignes" class="form-label">
                        <?= $this->lang->line("formation_autorisation_solo_consignes") ?>
                    </label>
                    <textarea class="form-control" id="consignes" name="consignes" rows="4"
                              maxlength="250"
                              placeholder="Consignes de vol solo (optionnel, 250 caractères max)..."><?= htmlspecialchars($autorisation['consignes'] ?? '') ?></textarea>
                    <div class="form-text">
                        <span id="char-count"></span>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between">
                    <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                        <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line("formation_form_cancel") ?>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i> <?= $this->lang->line("formation_form_save") ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for consignes
    const textarea = document.getElementById('consignes');
    const charCount = document.getElementById('char-count');
    const maxLength = 250;

    function updateCharCount() {
        const length = textarea.value.length;
        const remaining = maxLength - length;
        if (remaining < 20) {
            charCount.innerHTML = '<span class="text-warning">' + length + '/' + maxLength + ' caractères</span>';
        } else {
            charCount.innerHTML = '<span class="text-muted">' + length + '/' + maxLength + ' caractères</span>';
        }
    }

    textarea.addEventListener('input', updateCharCount);
    updateCharCount();

    // Aircraft filtering based on inscription's programme type
    const inscriptionsData = <?= json_encode($inscriptions_data ?? []) ?>;
    const planeurs = <?= json_encode($planeurs ?? []) ?>;
    const avions = <?= json_encode($avions ?? []) ?>;
    const currentMachineId = <?= json_encode($autorisation['machine_id'] ?? '') ?>;

    const inscriptionSelect = document.getElementById('inscription_id');
    const machineSelect = document.getElementById('machine_id');

    function updateMachineOptions() {
        const inscriptionId = inscriptionSelect.value;

        // Find the selected inscription's type_aeronef
        let typeAeronef = null;
        for (const insc of inscriptionsData) {
            if (insc.id == inscriptionId) {
                typeAeronef = insc.type_aeronef;
                break;
            }
        }

        // Clear current options
        machineSelect.innerHTML = '';

        if (!typeAeronef) {
            machineSelect.innerHTML = '<option value="">-- Sélectionner une formation d\'abord --</option>';
            return;
        }

        // Add empty option
        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = '-- Sélectionner --';
        machineSelect.appendChild(emptyOption);

        // Add appropriate aircraft
        const aircraft = (typeAeronef === 'planeur') ? planeurs : avions;
        for (const [id, nom] of Object.entries(aircraft)) {
            if (id === '') continue; // Skip empty key
            const option = document.createElement('option');
            option.value = id;
            option.textContent = nom;
            if (id === currentMachineId) {
                option.selected = true;
            }
            machineSelect.appendChild(option);
        }
    }

    inscriptionSelect.addEventListener('change', updateMachineOptions);

    // Initialize on page load
    updateMachineOptions();
});
</script>

<?php $this->load->view('bs_footer'); ?>
