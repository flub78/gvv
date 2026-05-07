<!-- VIEW: application/views/cartes_membre/bs_carte.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12">
            <h4><i class="fas fa-id-card text-success"></i>
                <?= $is_admin
                    ? $this->lang->line('gvv_cartes_membre_carte_admin_title')
                    : $this->lang->line('gvv_cartes_membre_carte_title') ?>
            </h4>
        </div>
    </div>

    <?php if ($is_admin && empty($mlogin)): ?>
    <!-- Admin : sélection membre + année -->
    <form method="get" id="form_carte" action="<?= controller_url('cartes_membre/carte') ?>">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label"><?= $this->lang->line('gvv_cartes_membre_carte_member') ?></label>
                        <select name="mlogin" id="sel_mlogin" class="form-select big_select" required>
                            <option value=""><?= $this->lang->line('gvv_cartes_membre_carte_member_select') ?></option>
                            <?php foreach ($membres as $m): ?>
                            <option value="<?= htmlspecialchars($m['mlogin']) ?>">
                                <?= htmlspecialchars($m['mnom'] . ' ' . $m['mprenom']) ?>
                                <?= !empty($m['mnumero']) ? '(N° ' . htmlspecialchars($m['mnumero']) . ')' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?= $this->lang->line('gvv_cartes_membre_carte_year') ?></label>
                        <select name="year" class="form-select">
                            <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success" id="btn_generate">
                            <i class="fas fa-file-pdf"></i> <?= $this->lang->line('gvv_cartes_membre_carte_generate') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <?php else: ?>
    <!-- Membre ou admin avec mlogin fourni -->
    <?php if ($membre): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label"><?= $this->lang->line('gvv_cartes_membre_carte_member') ?></label>
                    <p class="form-control-plaintext fw-bold">
                        <?= htmlspecialchars($membre['mnom'] . ' ' . $membre['mprenom']) ?>
                        <?php if (!empty($membre['mnumero'])): ?>
                            <span class="text-muted small">(N° <?= htmlspecialchars($membre['mnumero']) ?>)</span>
                        <?php endif; ?>
                    </p>
                </div>

                <?php if (empty($years)): ?>
                <div class="col-md-8">
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= $this->lang->line('gvv_cartes_membre_carte_no_cotisation') ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="col-md-3">
                    <label class="form-label"><?= $this->lang->line('gvv_cartes_membre_carte_year') ?></label>
                    <select id="sel_year" class="form-select">
                        <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <a id="btn_generate" href="<?= controller_url('cartes_membre/carte/' . urlencode($mlogin) . '/' . $year) ?>"
                       class="btn btn-success">
                        <i class="fas fa-file-pdf"></i> <?= $this->lang->line('gvv_cartes_membre_carte_generate') ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<script>
(function () {
    var selYear = document.getElementById('sel_year');
    var btnGen  = document.getElementById('btn_generate');
    var baseUrl = '<?= controller_url('cartes_membre/carte/' . urlencode($mlogin ?? '')) ?>/';

    if (selYear && btnGen) {
        selYear.addEventListener('change', function () {
            btnGen.href = baseUrl + this.value;
        });
    }
}());
</script>
