<!-- VIEW: application/views/paiements_en_ligne/bs_admin_cotisations.php -->
<?php
/**
 * Interface admin — gestion des produits de cotisation (UC3).
 * Accès : trésorier, bureau, admin.
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<h3><?= $this->lang->line('gvv_admin_cotisations_title') ?></h3>
<p class="text-muted">
    <?= $this->lang->line('gvv_admin_cotisations_intro') ?>
    <strong><?= htmlspecialchars($section['nom']) ?></strong>
</p>

<!-- Liste des produits existants -->
<?php if (!empty($produits)): ?>
<div class="table-responsive mb-4" style="max-width: 800px;">
    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr>
                <th><?= $this->lang->line('gvv_admin_cotisations_col_libelle') ?></th>
                <th><?= $this->lang->line('gvv_admin_cotisations_col_annee') ?></th>
                <th><?= $this->lang->line('gvv_admin_cotisations_col_montant') ?></th>
                <th><?= $this->lang->line('gvv_admin_cotisations_col_statut') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produits as $p): ?>
            <tr class="<?= $p['actif'] ? '' : 'text-muted' ?>">
                <td><?= htmlspecialchars($p['libelle']) ?></td>
                <td><?= (int) $p['annee'] ?></td>
                <td><?= euros((float) $p['montant']) ?></td>
                <td>
                    <?php if ($p['actif']): ?>
                    <span class="badge bg-success"><?= $this->lang->line('gvv_admin_cotisations_actif') ?></span>
                    <?php else: ?>
                    <span class="badge bg-secondary"><?= $this->lang->line('gvv_admin_cotisations_inactif') ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= site_url('paiements_en_ligne/toggle_cotisation_produit/' . (int) $p['id']) ?>"
                       class="btn btn-sm <?= $p['actif'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                       onclick="return confirm('<?= $p['actif'] ? $this->lang->line('gvv_admin_cotisations_confirm_desactiver') : $this->lang->line('gvv_admin_cotisations_confirm_activer') ?>');">
                        <?= $p['actif'] ? $this->lang->line('gvv_admin_cotisations_btn_desactiver') : $this->lang->line('gvv_admin_cotisations_btn_activer') ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-info mb-4"><?= $this->lang->line('gvv_admin_cotisations_empty') ?></div>
<?php endif; ?>

<!-- Formulaire d'ajout -->
<div class="card" style="max-width: 560px;">
    <div class="card-header">
        <strong><?= $this->lang->line('gvv_admin_cotisations_add_title') ?></strong>
    </div>
    <div class="card-body">
        <form method="post" action="<?= site_url('paiements_en_ligne/admin_cotisations') ?>">

            <div class="mb-3">
                <label class="form-label"><?= $this->lang->line('gvv_admin_cotisations_col_libelle') ?> <span class="text-danger">*</span></label>
                <input type="text" name="libelle" class="form-control"
                       placeholder="<?= $this->lang->line('gvv_admin_cotisations_libelle_placeholder') ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label"><?= $this->lang->line('gvv_admin_cotisations_col_annee') ?> <span class="text-danger">*</span></label>
                    <input type="number" name="annee" class="form-control"
                           value="<?= $annee_courant ?>"
                           min="2000" max="<?= $annee_courant + 5 ?>" required>
                </div>
                <div class="col-6">
                    <label class="form-label"><?= $this->lang->line('gvv_admin_cotisations_col_montant') ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="montant" class="form-control"
                               min="1" step="0.01" required>
                        <span class="input-group-text">€</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($comptes_417)): ?>
            <div class="mb-3">
                <label class="form-label"><?= $this->lang->line('gvv_admin_cotisations_compte') ?> <span class="text-danger">*</span></label>
                <select name="compte_cotisation_id" class="form-select" required>
                    <option value=""><?= $this->lang->line('gvv_admin_cotisations_select_compte') ?></option>
                    <?php foreach ($comptes_417 as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['codec'] . ' — ' . $c['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <div class="alert alert-warning small"><?= $this->lang->line('gvv_admin_cotisations_no_compte_417') ?></div>
            <input type="hidden" name="compte_cotisation_id" value="0">
            <?php endif; ?>

            <button type="submit" name="button" value="save" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?= $this->lang->line('gvv_admin_cotisations_btn_add') ?>
            </button>

        </form>
    </div>
</div>

</div>
