<!-- VIEW: application/views/compta/bs_provisionnement_tresorier.php -->
<?php
/**
 * Formulaire d'approvisionnement du compte pilote par le trésorier (UC7).
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('paiements_en_ligne');

echo '<div id="body" class="body container-fluid">';

echo checkalert($this->session);
?>
<h3><?= $this->lang->line('gvv_credit_tresorier_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('gvv_credit_tresorier_intro') ?></p>

<?php
$error_msg = isset($error_message) ? $error_message : $this->session->flashdata('error');
if ($error_msg) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-exclamation-triangle"></i></strong> ';
    echo nl2br(htmlspecialchars($error_msg));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
?>

<?= form_open('compta/provisionnement_tresorier', array('id' => 'form_provisionnement_tresorier')) ?>

<div class="row g-3" style="max-width: 700px;">

    <div class="col-12">
        <label for="pilote" class="form-label"><?= $this->lang->line('gvv_compta_label_pilote') ?> <span class="text-danger">*</span></label>
        <?= form_dropdown('pilote', $pilote_selector, $pilote, 'class="form-select big_select" id="pilote"') ?>
    </div>

    <div class="col-12 col-md-6">
        <label for="montant" class="form-label"><?= $this->lang->line('gvv_bar_montant') ?> <span class="text-danger">*</span></label>
        <input type="number" class="form-control" id="montant" name="montant"
               value="<?= htmlspecialchars($montant) ?>" step="0.01" min="0.01">
    </div>

    <div class="col-12">
        <label for="description" class="form-label"><?= $this->lang->line('gvv_ecritures_field_description') ?></label>
        <input type="text" class="form-control" id="description" name="description"
               value="<?= htmlspecialchars($description) ?>"
               placeholder="<?= $this->lang->line('gvv_provision_checkout_description') ?>">
    </div>

    <!-- Compte banque — requis pour le chemin "Valider" uniquement -->
    <div class="col-12" id="compte_banque_row">
        <label for="compte_banque" class="form-label"><?= $this->lang->line('gvv_compta_label_compte_banque') ?></label>
        <?php if (!empty($single_compte_banque)): ?>
            <input type="text" class="form-control" value="<?= htmlspecialchars($compte_banque_label) ?>" readonly>
            <input type="hidden" name="compte_banque" value="<?= htmlspecialchars($compte_banque) ?>">
        <?php else: ?>
            <?= form_dropdown('compte_banque', $compte_banque_selector, $compte_banque, 'class="form-select" id="compte_banque"') ?>
        <?php endif; ?>
        <small class="form-text text-muted"><?= $this->lang->line('gvv_admin_config_compte_passage_help') ?></small>
    </div>

</div>

<!-- Boutons -->
<div class="row mt-4" style="max-width: 700px;">
    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
        <button type="submit" name="button" value="valider" id="btnValidate" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> <?= $this->lang->line('gvv_button_validate') ?>
        </button>
        <?php if (!empty($is_dev_authorized) && !empty($helloasso_enabled)): ?>
        <button type="submit" name="button" value="helloasso" id="btnHelloasso" class="btn btn-warning">
            <i class="fas fa-credit-card"></i> <?= $this->lang->line('gvv_credit_tresorier_button') ?>
        </button>
        <?php endif; ?>
        <button type="button" class="btn btn-secondary" onclick="history.back()">
            <i class="bi bi-x-circle"></i> <?= $this->lang->line('gvv_button_cancel') ?>
        </button>
    </div>
</div>

<?= form_close() ?>

<script>
$(document).ready(function() {
    // Masquer le compte banque si on clique HelloAsso (pas nécessaire pour ce chemin)
    $('#btnHelloasso').on('click', function() {
        $('#compte_banque_row').hide();
    });
    $('#btnValidate').on('click', function() {
        $('#compte_banque_row').show();
    });
});
</script>

<?= '</div>' ?>
