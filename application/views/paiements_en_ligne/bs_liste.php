<!-- VIEW: application/views/paiements_en_ligne/bs_liste.php -->
<?php
/**
 * Liste des paiements en ligne — vue trésorier (EF4).
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<h3><?= $this->lang->line('gvv_liste_title') ?></h3>

<!-- ── Filtres ────────────────────────────────────────────────── -->
<form method="get" action="<?= controller_url('paiements_en_ligne/liste') ?>" class="mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label small"><?= $this->lang->line('gvv_liste_filter_from') ?></label>
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="<?= htmlspecialchars($filters['date_from']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small"><?= $this->lang->line('gvv_liste_filter_to') ?></label>
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="<?= htmlspecialchars($filters['date_to']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small"><?= $this->lang->line('gvv_liste_filter_statut') ?></label>
            <select name="statut" class="form-select form-select-sm">
                <option value=""><?= $this->lang->line('gvv_liste_filter_all') ?></option>
                <option value="pending"   <?= $filters['statut'] === 'pending'   ? 'selected' : '' ?>>
                    <?= $this->lang->line('gvv_pel_statut_pending') ?></option>
                <option value="completed" <?= $filters['statut'] === 'completed' ? 'selected' : '' ?>>
                    <?= $this->lang->line('gvv_pel_statut_completed') ?></option>
                <option value="failed"    <?= $filters['statut'] === 'failed'    ? 'selected' : '' ?>>
                    <?= $this->lang->line('gvv_pel_statut_failed') ?></option>
                <option value="cancelled" <?= $filters['statut'] === 'cancelled' ? 'selected' : '' ?>>
                    <?= $this->lang->line('gvv_pel_statut_cancelled') ?></option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small"><?= $this->lang->line('gvv_liste_filter_plateforme') ?></label>
            <select name="plateforme" class="form-select form-select-sm">
                <option value=""><?= $this->lang->line('gvv_liste_filter_all') ?></option>
                <option value="helloasso" <?= $filters['plateforme'] === 'helloasso' ? 'selected' : '' ?>>HelloAsso</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small"><?= $this->lang->line('gvv_liste_filter_section') ?></label>
            <select name="club" class="form-select form-select-sm">
                <option value=""><?= $this->lang->line('gvv_liste_filter_all') ?></option>
                <?php foreach ($sections as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= (int)$filters['club'] === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm"><?= $this->lang->line('gvv_liste_filter_apply') ?></button>
            <a href="<?= controller_url('paiements_en_ligne/liste') ?>" class="btn btn-outline-secondary btn-sm"><?= $this->lang->line('gvv_liste_filter_reset') ?></a>
        </div>
    </div>
</form>

<!-- ── Statistiques ───────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-success">
            <div class="card-body py-2">
                <div class="fs-4 fw-bold text-success"><?= $stats['count'] ?></div>
                <div class="small text-muted"><?= $this->lang->line('gvv_liste_stat_count') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-primary">
            <div class="card-body py-2">
                <div class="fs-4 fw-bold text-primary"><?= euros($stats['total']) ?></div>
                <div class="small text-muted"><?= $this->lang->line('gvv_liste_stat_total') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-warning">
            <div class="card-body py-2">
                <div class="fs-4 fw-bold text-warning"><?= euros($stats['commissions']) ?></div>
                <div class="small text-muted"><?= $this->lang->line('gvv_liste_stat_commissions') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 d-flex align-items-center">
        <a href="<?= controller_url('paiements_en_ligne/liste_csv') ?>?<?= http_build_query(array_filter($filters)) ?>"
           class="btn btn-outline-success w-100">
            <i class="fas fa-file-csv"></i> <?= $this->lang->line('gvv_liste_export_csv') ?>
        </a>
    </div>
</div>

<!-- ── Tableau ────────────────────────────────────────────────── -->
<?php if (empty($transactions)): ?>
<div class="alert alert-info"><?= $this->lang->line('gvv_liste_empty') ?></div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm table-hover table-bordered">
    <thead class="table-dark">
        <tr>
            <th><?= $this->lang->line('gvv_pel_col_date') ?></th>
            <th><?= $this->lang->line('gvv_liste_col_pilote') ?></th>
            <th class="text-end"><?= $this->lang->line('gvv_pel_col_montant') ?></th>
            <th class="text-end"><?= $this->lang->line('gvv_liste_col_commission') ?></th>
            <th><?= $this->lang->line('gvv_pel_col_plateforme') ?></th>
            <th><?= $this->lang->line('gvv_liste_col_reference') ?></th>
            <th><?= $this->lang->line('gvv_liste_col_section') ?></th>
            <th><?= $this->lang->line('gvv_pel_col_statut') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($transactions as $tx): ?>
        <?php
        $prenom = isset($tx['mprenom']) ? $tx['mprenom'] : '';
        $nom    = isset($tx['mnom'])    ? $tx['mnom']    : '';
        $pilot  = trim($prenom . ' ' . $nom) ?: $tx['username'];

        $badge_class = array(
            'pending'   => 'bg-warning text-dark',
            'completed' => 'bg-success',
            'failed'    => 'bg-danger',
            'cancelled' => 'bg-secondary',
        );
        $statut_key = array(
            'pending'   => 'gvv_pel_statut_pending',
            'completed' => 'gvv_pel_statut_completed',
            'failed'    => 'gvv_pel_statut_failed',
            'cancelled' => 'gvv_pel_statut_cancelled',
        );
        $cls  = isset($badge_class[$tx['statut']]) ? $badge_class[$tx['statut']] : 'bg-secondary';
        $lbl  = isset($statut_key[$tx['statut']])  ? $this->lang->line($statut_key[$tx['statut']]) : $tx['statut'];
        ?>
        <tr>
            <td class="text-nowrap"><?= htmlspecialchars($tx['date_demande']) ?></td>
            <td><?= htmlspecialchars($pilot) ?></td>
            <td class="text-end text-nowrap"><?= euros((float)$tx['montant']) ?></td>
            <td class="text-end text-nowrap"><?= euros((float)$tx['commission']) ?></td>
            <td><?= htmlspecialchars($tx['plateforme']) ?></td>
            <td class="font-monospace small"><?= htmlspecialchars($tx['transaction_id']) ?></td>
            <td><?= (int)$tx['club'] ?></td>
            <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
            <td>
            <?php if (!empty($tx['ecriture_id'])): ?>
                <a href="<?= controller_url('ecritures/view/' . (int)$tx['ecriture_id']) ?>"
                   class="btn btn-outline-secondary btn-sm py-0">
                    <i class="fas fa-book-open"></i>
                </a>
            <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

</div>
