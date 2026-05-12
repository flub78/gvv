<!-- VIEW: application/views/paiements_en_ligne/bs_paiement_generique_form.php -->
<?php
/**
 * Formulaire de création d'un paiement générique par QR code (trésorier).
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<h3><?= $this->lang->line('gvv_paiement_generique_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('gvv_paiement_generique_intro') ?></p>

<div class="card mb-3" style="max-width: 560px;">
    <div class="card-body text-muted small">
        <?= $this->lang->line('gvv_bar_label_section') ?> : <?= htmlspecialchars($section['nom']) ?>
    </div>
</div>

<?= form_open('paiements_en_ligne/paiement_generique', array('name' => 'saisie')) ?>

<div style="max-width: 560px;">

    <!-- Montant -->
    <div class="mb-3">
        <label for="montant" class="form-label">
            <?= $this->lang->line('gvv_paiement_generique_field_montant') ?> <span class="text-danger">*</span>
        </label>
        <div class="input-group" style="max-width: 200px;">
            <input type="number"
                   id="montant"
                   name="montant"
                   value="<?= htmlspecialchars($montant) ?>"
                   min="<?= $montant_min ?>"
                   max="<?= $montant_max ?>"
                   step="0.01"
                   class="form-control"
                   required>
            <span class="input-group-text">€</span>
        </div>
        <div class="form-text">
            <?= sprintf($this->lang->line('gvv_paiement_generique_error_montant'),
                number_format($montant_min, 2, ',', ' '),
                number_format($montant_max, 2, ',', ' ')) ?>
        </div>
    </div>

    <!-- Description -->
    <div class="mb-3">
        <label for="description" class="form-label">
            <?= $this->lang->line('gvv_paiement_generique_field_description') ?> <span class="text-danger">*</span>
        </label>
        <input type="text"
               id="description"
               name="description"
               value="<?= htmlspecialchars($description) ?>"
               maxlength="255"
               class="form-control"
               required>
        <div class="form-text"><?= $this->lang->line('gvv_paiement_generique_field_description_help') ?></div>
    </div>

    <!-- Compte à créditer -->
    <div class="mb-3">
        <label for="compte_destination_id" class="form-label">
            <?= $this->lang->line('gvv_paiement_generique_field_compte') ?> <span class="text-danger">*</span>
        </label>
        <?= form_dropdown('compte_destination_id', $compte_selector, $compte_destination_id,
            'id="compte_destination_id" class="form-select big_select" required') ?>
    </div>

    <!-- Email payeur (optionnel) -->
    <div class="mb-3">
        <label for="payer_email" class="form-label">
            <?= $this->lang->line('gvv_paiement_generique_field_email') ?>
        </label>
        <input type="email"
               id="payer_email"
               name="payer_email"
               value="<?= htmlspecialchars($payer_email) ?>"
               class="form-control"
               style="max-width: 360px;">
    </div>

    <div class="alert alert-info">
        <small><?= $this->lang->line('gvv_bar_carte_helloasso_notice') ?></small>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button type="submit" name="button" value="valider" class="btn btn-primary">
            <i class="fas fa-credit-card"></i> <?= $this->lang->line('gvv_paiement_generique_btn_create') ?>
        </button>
        <a href="<?= site_url('paiements_en_ligne/liste') ?>" class="btn btn-secondary">
            <?= $this->lang->line('gvv_button_cancel') ?>
        </a>
    </div>

</div>

<?= form_close() ?>

</div>
