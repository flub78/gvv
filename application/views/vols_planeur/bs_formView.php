<!-- VIEW: application/views/vols_planeur/bs_formView.php -->
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
 * Formulaire de saisie d'un vol planeur
 *
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('vols_planeur');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");
echo validation_errors();

echo heading("gvv_vols_planeur_fieldset_flight", 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// On affiche tous les champs dans un tableau. C'est plus simple de remplir d'abbord le tableau
// et de l'afficher ensuite, surtout pour modifier l'affichage

echo form_hidden('vpid', $vpid);
echo form_hidden('saisie_par', $saisie_par, '');

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

$attrs = ['onfocusout' => "calculp()", "type" => "time"];
$altitude = ($remorque_100eme) ? $this->lang->line("gvv_vols_planeur_label_centieme") : $this->lang->line("gvv_vols_planeur_label_alt");
$percent_selector = array('0' => 0, '50' => 50, '100' => 100);

?>
<div class="d-md-flex flex-row mb-2">
    <!-- Date, immat-->
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vpdate") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpdate', $vpdate) ?>
    </div>

    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vpmacid") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpmacid', $vpmacid) ?>
    </div>
</div>

<div class="d-md-flex flex-row mb-2">
    <!-- Pilote, inst et passager -->
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vppilid") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vppilid', $vppilid) ?>
    </div>

    <div class="me-3 mb-2" id="DC">
        <?= $this->lang->line("gvv_volsp_field_vpdc") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpdc', $vpdc) ?>
    </div>

    <div class="me-3 mb-2" id="instruction">
        <?= $this->lang->line("gvv_volsp_field_instructeur") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpinst', $vpinst) ?>
    </div>

    <div class="me-3 mb-2" id="passager">
        <?= $this->lang->line("gvv_volsp_field_pas") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vppassager', $vppassager) ?>
    </div>

</div>

<div class="d-md-flex flex-row mb-2">
    <!-- Heure de début et de fin -->
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vpcdeb") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpcdeb', $vpcdeb, "rw", $attrs) ?>
    </div>

    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vpcfin") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpcfin', $vpcfin, "rw", $attrs) ?>
    </div>

    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vpduree") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpduree', $vpduree) ?>
    </div>

    <div class="me-3 mb-2 text-danger" id="time_error">
    </div>

</div>

<div class="d-md-flex flex-row mb-2">
    <!-- Lancement -->
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vpautonome") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpautonome', $vpautonome) ?>
    </div>

    <div class="me-3 mb-2 altitude">
        <?= $altitude . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpaltrem', $vpaltrem) ?>
    </div>

    <?php if (isset($vpticcolle)) : ?>
        <div class="me-3 mb-2">
            <?= $this->lang->line("gvv_vols_planeur_label_ticket") . ": " ?>
            <?= $this->gvvmetadata->input_field("volsp", 'vpticcolle', $vpticcolle) ?>
        </div>
    <?php endif; ?>

    <div class="me-3 mb-2 altitude">
        <?= $this->lang->line("gvv_volsp_field_remorqueur") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'remorqueur', $remorqueur) ?>
    </div>

    <div class="me-3 mb-2 altitude">
        <?= $this->lang->line("gvv_volsp_field_pilote_remorqueur") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'pilote_remorqueur', $pilote_remorqueur) ?>
    </div>

    <div class="me-3 mb-2 treuil">
        <?= $this->lang->line("gvv_vols_planeur_label_whincher") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vptreuillard', $vptreuillard) ?>
    </div>

    <div class="me-3 mb-2 autonome">
        <?= $this->lang->line("gvv_volsp_field_tempmoteur") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'tempmoteur', $tempmoteur) ?>
    </div>

    <div class="me-3 mb-2 autonome">
        <?= $this->lang->line("gvv_volsp_field_avitaillement") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'reappro', $reappro) ?>
    </div>

    <div class="me-3 mb-2 autonome">
        <?= $this->lang->line("gvv_volsp_field_essence") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'essence', $essence) ?>
    </div>

</div>

<div class="d-md-flex flex-row mb-2">
    <!-- Catégorie -->
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vpcategorie") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpcategorie', $vpcategorie, "rw", ['onChange' => "vpcategorie_changed()"]) ?>
    </div>
</div>

<div class="d-md-flex flex-row mb-2">
    <div class="me-3 mb-2 VI" style="display:none;">
        <?= $this->lang->line("gvv_volsp_field_vpnumvi") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpnumvi', $vpnumvi) ?>
    </div>

</div>

<?php if ($payeur_non_pilote) : ?>
    <div class="d-md-flex flex-row mb-2">
        <!-- Payeur -->
        <div class="me-3 mb-2 payeur">
            <?= $this->lang->line("gvv_vols_planeur_label_payer") . ": " ?>
            <?= $this->gvvmetadata->input_field("volsp", 'payeur', $payeur) ?>
        </div>

        <?php if ($partage) : ?>
            <div class="me-3 mb-2 payeur">
                <?= $this->lang->line("gvv_vols_planeur_label_percent") . ": " ?>
                <?= $this->gvvmetadata->input_field("volsp", 'pourcentage', $pourcentage) ?>
            </div>
        <?php endif; ?>

    </div>
<?php endif; ?>

<div class="d-md-flex flex-row mb-2">
    <!-- Lieux et distance -->
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vplieudeco") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vplieudeco', $vplieudeco) ?>
    </div>
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vplieuatt") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vplieuatt', $vplieuatt) ?>
    </div>
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vpnbkm") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpnbkm', $vpnbkm) ?>
    </div>

</div>

<div class="d-md-flex flex-row mb-2">
    <!-- Observations -->
    <div class="me-3 mb-2">
        <?= $this->lang->line("gvv_volsp_field_vpobs") . ": " ?>
        <?= $this->gvvmetadata->input_field("volsp", 'vpobs', $vpobs) ?>
    </div>
</div>

<!-- Formation -->
<div class="mb-3">
    <h5 class="mb-2"><?= $this->lang->line("gvv_vols_planeur_fieldset_formation") ?></h5>
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

<!-- Certificats FAI -->
<div class="mb-3">
    <h5 class="mb-2"><?= $this->lang->line("gvv_vols_planeur_fieldset_FAI") ?></h5>
    <div class="d-md-flex flex-row flex-wrap">
        <?php foreach ($certificats_fai as $certificat_fai) {
            $id = $certificat_fai['id'];
            $value = isset($certificat_fai_values[$id]) ? $certificat_fai_values[$id] : null;
            $checkbox_id = 'certificat_fai_' . $id;
            echo '<div class="form-check form-check-inline me-2 mb-2" style="border: 1px solid #adb5bd; border-radius: 4px; padding: 6px 10px 6px 30px;">';
            echo '<input class="form-check-input" type="checkbox" name="certificat_fai_values[]" id="' . $checkbox_id . '" value="' . $id . '" ' . (array_key_exists($id, $certificat_fai_values) ? 'checked' : '') . '>';
            echo '<label class="form-check-label" for="' . $checkbox_id . '">' . $certificat_fai['label'] . '</label>';
            echo '</div>';
        } ?>
    </div>
</div>


<?php

echo validation_button($action);
echo form_close();

echo br();
$list = array(
    $this->lang->line("gvv_vols_planeur_tooltip_1"),
    $this->lang->line("gvv_vols_planeur_tooltip_2")
);
echo ul($list);

echo '</div>';
?>
<script type="text/javascript" src="<?php echo js_url('form_vols_planeur'); ?>"></script>