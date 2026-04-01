<!-- VIEW: application/views/paiements_en_ligne/bs_bar_carte_form.php -->
<?php
/**
 * Formulaire de règlement des consommations de bar par carte bancaire (UC1).
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<h3><?= $this->lang->line('gvv_bar_carte_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('gvv_bar_carte_intro') ?></p>

<div class="card mb-3" style="max-width: 500px;">
    <div class="card-body">
        <div class="text-muted small">
            <?= $this->lang->line('gvv_bar_label_section') ?> : <?= htmlspecialchars($section['nom']) ?>
        </div>
    </div>
</div>

<?= form_open('paiements_en_ligne/bar_carte', array('name' => 'saisie')) ?>

<div class="mb-3" style="max-width: 500px;">
    <label for="montant" class="form-label">
        <?= $this->lang->line('gvv_bar_montant') ?> <span class="text-danger">*</span>
    </label>
    <div class="input-group">
        <input type="number"
               id="montant"
               name="montant"
               value="<?= htmlspecialchars($montant) ?>"
               min="<?= isset($montant_min) ? $montant_min : 1 ?>"
               max="<?= isset($montant_max) ? $montant_max : 500 ?>"
               step="1"
               class="form-control"
               required
               style="max-width: 150px;"
               placeholder="0" />
        <span class="input-group-text">€</span>
    </div>
    <div class="form-text">
        <?php 
        if (isset($montant_min) && isset($montant_max)) {
            echo sprintf(
                $this->lang->line('gvv_provision_montant_help'),
                number_format((float) $montant_min, 2, ',', ' '),
                number_format((float) $montant_max, 2, ',', ' ')
            );
        } else {
            echo $this->lang->line('gvv_bar_montant_help');
        }
        ?>
    </div>
</div>

<div class="mb-3" style="max-width: 500px;">
    <label for="description" class="form-label">
        <?= $this->lang->line('gvv_bar_description') ?> <span class="text-danger">*</span>
    </label>
    <input type="text"
           id="description"
           name="description"
           value="<?= htmlspecialchars($description) ?>"
           maxlength="255"
           class="form-control"
           required
           placeholder="<?= $this->lang->line('gvv_bar_description_placeholder') ?>" />
    <div class="form-text"><?= $this->lang->line('gvv_bar_description_help') ?></div>
</div>

<div class="alert alert-info" style="max-width: 500px;">
    <small><?= $this->lang->line('gvv_bar_carte_helloasso_notice') ?></small>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" name="button" value="valider" class="btn btn-primary">
        <?= $this->lang->line('gvv_bar_carte_button_valider') ?>
    </button>
    <a href="<?= site_url('compta/mon_compte') ?>" class="btn btn-secondary">
        <?= $this->lang->line('gvv_button_cancel') ?>
    </a>
</div>

<?= form_close() ?>

</div>
