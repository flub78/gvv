<!-- VIEW: application/views/paiements_en_ligne/bs_cotisation_form.php -->
<?php
/**
 * Formulaire de paiement cotisation en ligne (UC3) — pilote connecté.
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<h3><?= $this->lang->line('gvv_cotisation_form_title') ?></h3>
<p class="text-muted"><?= $this->lang->line('gvv_cotisation_form_intro') ?></p>

<?php if (empty($produits)): ?>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <?= $this->lang->line('gvv_cotisation_form_no_produits') ?>
</div>

<?php else: ?>

<form method="post" action="<?= site_url('paiements_en_ligne/cotisation') ?>" style="max-width: 600px;">

    <div class="mb-4">
        <label class="form-label fw-bold"><?= $this->lang->line('gvv_cotisation_form_choose') ?></label>

        <?php foreach ($produits as $p): ?>
        <div class="card mb-2">
            <div class="card-body py-2">
                <div class="form-check">
                    <input class="form-check-input" type="radio"
                           name="produit_id" value="<?= (int) $p['id'] ?>"
                           id="produit_<?= (int) $p['id'] ?>" required>
                    <label class="form-check-label d-flex justify-content-between align-items-center w-100"
                           for="produit_<?= (int) $p['id'] ?>">
                        <span>
                            <strong><?= htmlspecialchars($p['libelle']) ?></strong>
                        </span>
                        <span class="badge bg-warning text-dark fs-6">
                            <?= euros((float) $p['montant']) ?>
                        </span>
                    </label>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-info small mb-3">
        <i class="fas fa-info-circle"></i>
        <?= $this->lang->line('gvv_cotisation_helloasso_notice') ?>
    </div>

    <button type="submit" name="button" value="payer" class="btn btn-warning btn-lg">
        <i class="fas fa-credit-card"></i> <?= $this->lang->line('gvv_cotisation_form_button') ?>
    </button>
    <a href="<?= site_url('compta/mon_compte') ?>" class="btn btn-outline-secondary ms-2">
        <?= $this->lang->line('gvv_button_cancel') ?>
    </a>

</form>

<?php endif; ?>

</div>
