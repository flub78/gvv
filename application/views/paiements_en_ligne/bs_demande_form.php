<!-- VIEW: application/views/paiements_en_ligne/bs_demande_form.php -->
<?php
/**
 * Formulaire de provisionnement du compte pilote 411 par carte bancaire (EF1).
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<h3><?= $this->lang->line('gvv_provision_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('gvv_provision_intro') ?></p>

<div class="card mb-3" style="max-width: 500px;">
    <div class="card-body">
        <div class="text-muted small">
            <?= $this->lang->line('gvv_bar_label_section') ?> : <?= htmlspecialchars($section['nom']) ?>
        </div>
    </div>
</div>

<?= form_open('paiements_en_ligne/demande', array('name' => 'saisie')) ?>

<div class="mb-3" style="max-width: 300px;">
    <label for="montant" class="form-label">
        <?= $this->lang->line('gvv_bar_montant') ?> <span class="text-danger">*</span>
    </label>
    <select id="montant" name="montant" class="form-select" required>
        <option value=""><?= $this->lang->line('gvv_provision_select_montant') ?></option>
        <?php
        $step = 100;
        for ($m = $step; $m <= (int) $montant_max; $m += $step):
        ?>
        <option value="<?= $m ?>" <?= ((int)$montant === $m) ? 'selected' : '' ?>><?= $m ?> €</option>
        <?php endfor; ?>
    </select>
    <div class="form-text"><?= $this->lang->line('gvv_provision_montant_help_multi') ?></div>
</div>

<div class="alert alert-info" style="max-width: 500px;">
    <small><?= $this->lang->line('gvv_bar_carte_helloasso_notice') ?></small>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" name="button" value="valider" class="btn btn-primary">
        <?= $this->lang->line('gvv_provision_button_valider') ?>
    </button>
    <a href="<?= site_url('compta/mon_compte') ?>" class="btn btn-secondary">
        <?= $this->lang->line('gvv_button_cancel') ?>
    </a>
</div>

<?= form_close() ?>

</div>
