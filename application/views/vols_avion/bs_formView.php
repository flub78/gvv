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
echo form_hidden('horametres_en_min', $horametres_en_min, '');
echo form_hidden('machines', $machines, '');

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

?>
<div class="d-md-flex flex-row mb-2">
    <!-- Date, immat-->
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vadate") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vadate", $vadate) ?>
    </div>

    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vamacid") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vamacid", $vamacid) ?>
    </div>
</div>

<div class="d-md-flex flex-row mb-2">
    <!-- Pilote, inst et passager -->
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vapilid") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vapilid", $vapilid) ?>
    </div>

    <div class="me-3 mb-2 DC" id="DC">
        <?= $this->gvvmetadata->field_long_name("volsa", "vadc") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vadc", $vadc) ?>
    </div>

    <div class="me-3 mb-2" id="instruction">
        <?= $this->gvvmetadata->field_long_name("volsa", "vainst") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vainst", $vainst) ?>
    </div>
</div>

<div class="d-md-flex flex-row mb-2">
    <!-- Heure de début et de fin -->
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vahdeb") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vahdeb", $vahdeb) ?>
    </div>

    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vahfin") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vahfin", $vahfin) ?>
    </div>

</div>
<div class="d-md-flex flex-row mb-2">
    <!-- Horametre de début et de fin -->
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vacdeb") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vacdeb", $vacdeb, 'rw', array('id' => "debut"))  ?>
    </div>

    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vacfin") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vacfin", $vacfin, 'rw', array('id' => "fin")) ?>
    </div>

    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vaduree") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vaduree", $vaduree) ?>
        <div class="me-3 mb-2 text-danger" id="time_error">
        </div>
        <span id="hora_format"></span>
    </div>


</div>

<div class="d-md-flex flex-row mb-2">
    <!-- Catégorie -->
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vacategorie") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vacategorie", $vacategorie) ?>
    </div>
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vanumvi") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vanumvi", $vanumvi) ?>
    </div>
</div>

<?php if ($payeur_non_pilote) : ?>
    <div class="d-md-flex flex-row mb-2 payeur">
        <!-- Payeur -->
        <div class="me-3 mb-2">
            <?= $this->lang->line("gvv_vols_avion_label_payer") . ": " ?>
            <?= $this->gvvmetadata->input_field("volsa", 'payeur', $payeur) ?>
        </div>

        <?php if ($partage) : ?>
            <div class="me-3 mb-2 payeur">
                <?= $this->lang->line("gvv_vols_avion_label_percent") . ": " ?>
                <?= $this->gvvmetadata->input_field("volsa", 'pourcentage', $pourcentage) ?>
            </div>
        <?php endif; ?>

    </div>
<?php endif; ?>

<div class="d-md-flex flex-row mb-2">
    <!-- Passagers, atterrissages, eloignement, nuit -->
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vanbpax") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vanbpax", $vanbpax) ?>
    </div>
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vaatt") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vaatt", $vaatt) ?>
    </div>
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "local") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "local", $local) ?>
    </div>
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "nuit") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "nuit", $nuit) ?>
    </div>

</div>

<div class="d-md-flex flex-row mb-2">
    <!-- Lieux et distance -->
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "valieudeco") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsa", "valieudeco", $valieudeco) ?>
    </div>
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "valieuatt") ?>
        <?= $this->gvvmetadata->input_field("volsa", "valieuatt", $valieuatt) ?>
    </div>
</div>

<div class="d-md-flex flex-row mb-2">
    <!-- Essence -->
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "reappro") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsa", "reappro", $reappro) ?>
    </div>
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "essence") ?>
        <?= $this->gvvmetadata->input_field("volsa", "essence", $essence) ?>
    </div>
</div>

<div class="d-md-flex flex-row mb-2">
    <!-- Observations -->
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->field_long_name("volsa", "vaobs") . ":" ?>
        <?= $this->gvvmetadata->input_field("volsa", "vaobs", $vaobs) ?>
    </div>
</div>

<!-- Formation -->
<div class="mb-3">
    <h5 class="mb-2"><?= $this->lang->line("gvv_vols_avion_fieldset_formation") ?></h5>
    <div class="d-md-flex flex-row flex-wrap mb-2">
        <?php foreach ($certificats as $certificat) {
            $id = $certificat['id'];
            $value = isset($certificat_values[$id]) ? $certificat_values[$id] : null;
            $checkbox_id = 'certificat_' . $id;
            echo '<div class="form-check form-check-inline me-2 mb-2" style="border: 1px solid #adb5bd; border-radius: 4px; padding: 6px 10px 6px 30px;">';
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
<script type="text/javascript" src="<?php echo js_url('form_vols_avion'); ?>"></script>