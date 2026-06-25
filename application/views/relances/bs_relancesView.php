<!-- VIEW: application/views/relances/bs_relancesView.php -->
<?php
/**
 * Vue principale des comptes débiteurs (Phase 1).
 *
 * Variables transmises par Relances::index() :
 *   $sections        array  Sections actives [id, nom, acronyme]
 *   $debiteurs       array  Lignes retournées par relances_model::get_debiteurs()
 *   $seuil_alarme    float  Seuil jaune (€)
 *   $seuil_critique  float  Seuil rouge (€)
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('relances');
?>

<div id="body" class="body container-fluid">
  <h2><?= $this->lang->line('relances_title') ?></h2>

  <?php
  $CI = &get_instance();
  $flash_error   = $CI->session->flashdata('error');
  $flash_success = $CI->session->flashdata('success');
  if ($flash_error):
  ?>
  <div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div>
  <?php endif; if ($flash_success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($flash_success) ?></div>
  <?php endif; ?>

  <!-- Formulaire seuils -->
  <form method="post" action="<?= controller_url('relances/update_seuils') ?>" class="row g-2 align-items-end mb-3" id="form-seuils">
    <div class="col-auto">
      <label class="form-label"><?= $this->lang->line('relances_seuil_alarme') ?> (€)</label>
      <input type="number" name="seuil_alarme" id="seuil_alarme" min="0" step="1"
             value="<?= (int)$seuil_alarme ?>" class="form-control form-control-sm" style="width:100px">
    </div>
    <div class="col-auto">
      <label class="form-label"><?= $this->lang->line('relances_seuil_critique') ?> (€)</label>
      <input type="number" name="seuil_critique" id="seuil_critique" min="0" step="1"
             value="<?= (int)$seuil_critique ?>" class="form-control form-control-sm" style="width:100px">
    </div>
    <div class="col-auto align-self-end">
      <button type="submit" class="btn btn-primary btn-sm"><?= $this->lang->line('relances_appliquer') ?></button>
    </div>
    <div class="col-auto align-self-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="mode_anonyme">
        <label class="form-check-label" for="mode_anonyme"><?= $this->lang->line('relances_mode_anonyme') ?></label>
      </div>
    </div>
  </form>

  <!-- Légende -->
  <div class="mb-2 d-flex gap-3 flex-wrap">
    <span class="badge bg-danger"><?= $this->lang->line('relances_legende_critique') ?> (&ge; <?= (int)$seuil_critique ?>&nbsp;€)</span>
    <span class="badge bg-warning text-dark"><?= $this->lang->line('relances_legende_alarme') ?> (&ge; <?= (int)$seuil_alarme ?>&nbsp;€)</span>
    <span class="badge bg-secondary"><?= $this->lang->line('relances_legende_normal') ?></span>
  </div>

  <?php if (empty($debiteurs)): ?>
    <div class="alert alert-info"><?= $this->lang->line('relances_aucun_debiteur') ?></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-bordered table-hover table-sm align-middle" id="table-debiteurs">
      <thead class="table-dark">
        <tr>
          <th><?= $this->lang->line('relances_col_nom') ?></th>
          <?php foreach ($sections as $s): ?>
          <th class="text-end"><?= htmlspecialchars($s['acronyme']) ?></th>
          <?php endforeach; ?>
          <th class="text-end"><?= $this->lang->line('relances_col_total') ?></th>
          <th class="text-end"><?= $this->lang->line('relances_col_6mois') ?></th>
          <th class="text-end"><?= $this->lang->line('relances_col_1an') ?></th>
          <th><?= $this->lang->line('relances_col_relances') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($debiteurs as $d):
            $total = $d['total'];
            $abs   = abs($total);
            if ($abs >= $seuil_critique) {
                $row_class = 'table-danger';
            } elseif ($abs >= $seuil_alarme) {
                $row_class = 'table-warning';
            } else {
                $row_class = '';
            }
        ?>
        <tr class="<?= $row_class ?>">
          <td class="nom-membre"><?= htmlspecialchars($d['mnom'] . ' ' . $d['mprenom']) ?></td>
          <?php foreach ($sections as $s):
              $solde = $d['par_section'][$s['id']]['solde'] ?? 0;
          ?>
          <td class="text-end"><?= $solde != 0 ? euros($solde) : '' ?></td>
          <?php endforeach; ?>
          <td class="text-end fw-bold"><?= euros($total) ?></td>
          <td class="text-end"><?= $d['total_6m'] != 0 ? euros($d['total_6m']) : '–' ?></td>
          <td class="text-end"><?= $d['total_1an'] != 0 ? euros($d['total_1an']) : '–' ?></td>
          <td>
            <span class="badge bg-secondary">0</span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</div><!-- /#body -->

<style>
.nom-membre {
  transition: filter 0.2s;
}
body.mode-anonyme .nom-membre {
  filter: blur(5px);
  user-select: none;
}
</style>

<script>
(function () {
  var KEY = 'relances_mode_anonyme';
  var checkbox = document.getElementById('mode_anonyme');
  var body = document.body;

  function apply(active) {
    if (active) {
      body.classList.add('mode-anonyme');
      checkbox.checked = true;
    } else {
      body.classList.remove('mode-anonyme');
      checkbox.checked = false;
    }
    localStorage.setItem(KEY, active ? '1' : '0');
  }

  // Restore stored preference; default = active (anonyme)
  var stored = localStorage.getItem(KEY);
  apply(stored === null ? true : stored === '1');

  checkbox.addEventListener('change', function () {
    apply(this.checked);
  });
})();
</script>
