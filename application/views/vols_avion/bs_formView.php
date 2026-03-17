<!-- VIEW: application/views/vols_avion/bs_formView.php -->
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
 * Formulaire de saisie d'un vol avion
 *
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('vols_avion');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");
echo validation_errors();

echo heading("gvv_vols_avion_title", 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

echo form_hidden('vaid', $vaid);
echo form_hidden('saisie_par', $saisie_par, '');
echo form_hidden('horametres_mode', $horametres_mode, '');
echo form_hidden('machines', $machines, '');

if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

?>

<!-- Ligne 1 : Date, Immat, Pilote, DC, Instructeur -->
<div class="d-md-flex flex-row align-items-start gap-3 mb-3">

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vadate") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "vadate", $vadate) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vamacid") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "vamacid", $vamacid) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vapilid") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "vapilid", $vapilid) ?></div>
    </div>

    <div class="mb-2 DC" id="DC">
        <div class="d-flex flex-column">
            <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vadc") ?></div>
            <div><?= $this->gvvmetadata->input_field("volsa", "vadc", $vadc) ?></div>
        </div>
    </div>

    <div class="mb-2" id="instruction">
        <div class="d-flex flex-column">
            <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vainst") ?></div>
            <div><?= $this->gvvmetadata->input_field("volsa", "vainst", $vainst) ?></div>
        </div>
    </div>

</div>

<!-- Ligne 2 : Heure début, Heure fin, Horamètre début, Horamètre fin, Durée -->
<div class="d-md-flex flex-row align-items-start gap-3 mb-3">

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vahdeb") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "vahdeb", $vahdeb) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vahfin") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "vahfin", $vahfin) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vacdeb") ?></div>
        <div class="border rounded px-2 pb-2">
            <div id="debut_widget"></div>
            <input type="hidden" name="vacdeb" id="debut" value="<?= isset($vacdeb) ? htmlspecialchars($vacdeb) : '' ?>">
        </div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vacfin") ?></div>
        <div class="border rounded px-2 pb-2">
            <div id="fin_widget"></div>
            <input type="hidden" name="vacfin" id="fin" value="<?= isset($vacfin) ? htmlspecialchars($vacfin) : '' ?>">
        </div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vaduree") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "vaduree", $vaduree) ?></div>
        <div class="small text-muted mt-1" id="duree_display"></div>
        <div class="text-danger small mt-1" id="time_error"></div>
        <div class="small text-muted mt-1" id="hora_format"></div>
    </div>

</div>

<!-- Ligne 3 : Type de vol, N° VI, Passagers, Atterrissages, Local, Nuit -->
<div class="d-md-flex flex-row align-items-start gap-3 mb-3">

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vacategorie") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "vacategorie", $vacategorie) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vanumvi") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "vanumvi", $vanumvi) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vanbpax") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "vanbpax", $vanbpax) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vaatt") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "vaatt", $vaatt) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "local") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "local", $local) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "nuit") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "nuit", $nuit) ?></div>
    </div>

</div>

<!-- Ligne 4 : Aérodromes, Avitaillement, Essence -->
<div class="d-md-flex flex-row align-items-start gap-3 mb-3">

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "valieudeco") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "valieudeco", $valieudeco) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "valieuatt") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "valieuatt", $valieuatt) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "reappro") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "reappro", $reappro) ?></div>
    </div>

    <div class="d-flex flex-column mb-2">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "essence") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "essence", $essence) ?></div>
    </div>

</div>

<?php if ($payeur_non_pilote) : ?>
    <!-- hidden: remove wrapping div to re-enable payeur/pourcentage -->
    <div style="display:none">
    <div class="d-md-flex flex-row align-items-start gap-3 mb-3 payeur">
        <div class="d-flex flex-column mb-2">
            <div class="small mb-1"><?= $this->lang->line("gvv_vols_avion_label_payer") ?></div>
            <div><?= $this->gvvmetadata->input_field("volsa", 'payeur', $payeur) ?></div>
        </div>
        <?php if ($partage) : ?>
        <div class="mb-2 payeur">
            <div class="d-flex flex-column">
                <div class="small mb-1"><?= $this->lang->line("gvv_vols_avion_label_percent") ?></div>
                <div><?= $this->gvvmetadata->input_field("volsa", 'pourcentage', $pourcentage) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    </div><!-- end hidden -->
<?php endif; ?>

<!-- Observations -->
<div class="mb-3">
    <div class="d-flex flex-column" style="max-width:500px">
        <div class="small mb-1"><?= $this->gvvmetadata->field_long_name("volsa", "vaobs") ?></div>
        <div><?= $this->gvvmetadata->input_field("volsa", "vaobs", $vaobs) ?></div>
    </div>
</div>

<!-- Formation -->
<div class="mb-3">
    <h5 class="mb-2"><?= $this->lang->line("gvv_vols_avion_fieldset_formation") ?></h5>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($certificats as $certificat) {
            $id = $certificat['id'];
            $value = isset($certificat_values[$id]) ? $certificat_values[$id] : null;
            $checkbox_id = 'certificat_' . $id;
            echo '<div class="form-check form-check-inline" style="border: 1px solid #adb5bd; border-radius: 4px; padding: 6px 10px 6px 30px;">';
            echo '<input class="form-check-input" type="checkbox" name="certificat_values[]" id="' . $checkbox_id . '" value="' . $id . '" ' . (array_key_exists($id, $certificat_values) ? 'checked' : '') . '>';
            echo '<label class="form-check-label" for="' . $checkbox_id . '">' . $certificat['label'] . '</label>';
            echo '</div>';
        } ?>
    </div>
</div>

<?php

echo validation_button($action);
echo form_close();

echo br();
echo $this->lang->line("gvv_vols_avion_tip_billing");
echo '</div>';
?>
<style>
/* Normalise la hauteur de tous les champs non-Bootstrap de ce formulaire */
form[name="saisie"] input[type="text"]:not(.form-control),
form[name="saisie"] input[type="number"]:not(.form-control),
form[name="saisie"] input[type="date"]:not(.form-control),
form[name="saisie"] input[type="time"]:not(.form-control),
form[name="saisie"] select:not(.form-select) {
    height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    box-sizing: border-box;
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
}
form[name="saisie"] input[type="text"]:not(.form-control):focus,
form[name="saisie"] input[type="number"]:not(.form-control):focus,
form[name="saisie"] input[type="date"]:not(.form-control):focus,
form[name="saisie"] input[type="time"]:not(.form-control):focus,
form[name="saisie"] select:not(.form-select):focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
form[name="saisie"] textarea {
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    box-sizing: border-box;
    width: 100%;
}
/* Harmonise la hauteur des widgets select2 avec les champs Bootstrap */
form[name="saisie"] .select2-container .select2-selection--single {
    height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
}
form[name="saisie"] .select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 1.5;
    padding: 0;
    color: #212529;
}
form[name="saisie"] .select2-container .select2-selection--single .select2-selection__arrow {
    height: 100%;
    top: 0;
}
</style>
<script type="text/javascript" src="<?php echo js_url('form_vols_avion'); ?>"></script>
<script>
var horametres_modes_data = <?= json_encode($horametres_mode ? $horametres_mode : (object)array()) ?>;
var initial_horametre_mode = <?= isset($initial_horametre_mode) ? (int)$initial_horametre_mode : 0 ?>;
</script>
