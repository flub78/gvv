<!-- VIEW: application/views/cartes_membre/bs_lot.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-id-card text-primary"></i> <?= $this->lang->line('gvv_cartes_membre_lot_title') ?></h4>
            <a href="<?= controller_url('cartes_membre/config') ?>?year=<?= $year ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-cog"></i> <?= $this->lang->line('gvv_cartes_membre_config') ?>
            </a>
        </div>
    </div>

    <form method="post" action="<?= controller_url('cartes_membre/lot') ?>">
        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

        <!-- Sélecteur d'année -->
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label"><?= $this->lang->line('gvv_cartes_membre_year') ?></label>
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($year_selector as $y => $label): ?>
                        <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8 d-flex align-items-end">
                <span class="text-muted small">
                    <?= count($membres) ?> <?= $this->lang->line('gvv_cartes_membre_membres_count') ?>
                </span>
            </div>
        </div>

        <?php if (empty($membres)): ?>
            <div class="alert alert-warning">
                <?= $this->lang->line('gvv_cartes_membre_no_membres') ?>
            </div>
        <?php else: ?>

        <!-- Liste membres -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= $this->lang->line('gvv_cartes_membre_select_membres') ?></span>
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="selectAll(true)">
                        <?= $this->lang->line('gvv_cartes_membre_select_all') ?>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAll(false)">
                        <?= $this->lang->line('gvv_cartes_membre_deselect_all') ?>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px"><input type="checkbox" id="checkAll" checked onchange="selectAll(this.checked)"></th>
                            <th><?= $this->lang->line('gvv_cartes_membre_nom') ?></th>
                            <th><?= $this->lang->line('gvv_cartes_membre_prenom') ?></th>
                            <th><?= $this->lang->line('gvv_cartes_membre_numero') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membres as $m): ?>
                        <tr>
                            <td><input type="checkbox" name="membres[]" value="<?= htmlspecialchars($m['mlogin']) ?>" class="membre-cb" checked></td>
                            <td><?= htmlspecialchars($m['mnom']) ?></td>
                            <td><?= htmlspecialchars($m['mprenom']) ?></td>
                            <td><?= htmlspecialchars($m['mnumero'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" name="generate" value="1" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> <?= $this->lang->line('gvv_cartes_membre_generate') ?>
            </button>
        </div>

        <?php endif; ?>

        <input type="hidden" name="year" value="<?= $year ?>">
    </form>

</div>

<script>
function selectAll(checked) {
    document.querySelectorAll('.membre-cb').forEach(cb => cb.checked = checked);
    document.getElementById('checkAll').checked = checked;
}
</script>
