<!-- VIEW: application/views/paiements_en_ligne/bs_bar_hub.php -->
<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>
        <i class="fas fa-coffee text-warning me-2"></i>
        <?= $this->lang->line('gvv_bar_hub_title') ?>
        <small class="text-muted fs-6 ms-2"><?= htmlspecialchars($section['nom']) ?></small>
    </h3>
    <a href="<?= site_url('compta/mon_compte') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i><?= $this->lang->line('gvv_bar_hub_back') ?>
    </a>
</div>

<p class="text-muted mb-4"><?= $this->lang->line('gvv_bar_hub_intro') ?></p>

<div class="row g-4 justify-content-center">

    <!-- Débiter mon compte -->
    <div class="col-12 col-md-5">
        <div class="card h-100 border-success">
            <div class="card-body text-center py-4">
                <i class="fas fa-wallet fa-3x text-success mb-3"></i>
                <h5 class="card-title"><?= $this->lang->line('gvv_bar_hub_debit_title') ?></h5>
                <p class="card-text text-muted"><?= $this->lang->line('gvv_bar_hub_debit_sub') ?></p>
                <a href="<?= site_url('paiements_en_ligne/bar_debit_solde') ?>" class="btn btn-success">
                    <i class="fas fa-arrow-right me-1"></i><?= $this->lang->line('gvv_bar_hub_debit_title') ?>
                </a>
            </div>
        </div>
    </div>

    <?php if ($helloasso_enabled): ?>
    <!-- Paiement en ligne -->
    <div class="col-12 col-md-5">
        <div class="card h-100 border-primary">
            <div class="card-body text-center py-4">
                <i class="fas fa-credit-card fa-3x text-primary mb-3"></i>
                <h5 class="card-title"><?= $this->lang->line('gvv_bar_hub_carte_title') ?></h5>
                <p class="card-text text-muted"><?= $this->lang->line('gvv_bar_hub_carte_sub') ?></p>
                <a href="<?= site_url('paiements_en_ligne/bar_carte') ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-right me-1"></i><?= $this->lang->line('gvv_bar_hub_carte_title') ?>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

</div>
