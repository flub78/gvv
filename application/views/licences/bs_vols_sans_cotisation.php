<!-- VIEW: application/views/licences/bs_vols_sans_cotisation.php -->
<?php
/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Contrôle : pilotes ayant volé sans cotisation pour l'année
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid">
    <h3><?= sprintf($this->lang->line('gvv_vsc_title'), $year) ?></h3>
    <p class="text-muted"><?= $this->lang->line('gvv_vsc_explanation') ?></p>

    <div class="row g-2 align-items-end mb-3">
        <div class="col-auto">
            <label for="vsc_year" class="form-label"><?= $this->lang->line('gvv_vsc_select_year') ?></label>
            <select class="form-select" id="vsc_year">
                <?php foreach ($year_selector as $y => $label): ?>
                    <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if (empty($pilotes)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= sprintf($this->lang->line('gvv_vsc_none'), $year) ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <?= sprintf($this->lang->line('gvv_vsc_count'), count($pilotes), $year) ?>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th><?= $this->lang->line('gvv_vsc_col_pilot') ?></th>
                        <th><?= $this->lang->line('gvv_vsc_col_login') ?></th>
                        <th class="text-end"><?= $this->lang->line('gvv_vsc_col_glider') ?></th>
                        <th class="text-end"><?= $this->lang->line('gvv_vsc_col_plane') ?></th>
                        <th class="text-end"><?= $this->lang->line('gvv_vsc_col_ulm') ?></th>
                        <th><?= $this->lang->line('gvv_vsc_col_last_flight') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pilotes as $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['nom'] !== null): ?>
                                <a href="<?= controller_url('membre/edit/' . rawurlencode($p['pilote'])) ?>"><?= htmlspecialchars(trim($p['prenom'] . ' ' . $p['nom'])) ?></a>
                            <?php else: ?>
                                <span class="text-danger"><?= $this->lang->line('gvv_vsc_unknown_member') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['pilote']) ?></td>
                        <td class="text-end"><?= (int) $p['planeur'] ?: '' ?></td>
                        <td class="text-end"><?= (int) $p['avion'] ?: '' ?></td>
                        <td class="text-end"><?= (int) $p['ulm'] ?: '' ?></td>
                        <td><?= date_db2ht($p['dernier_vol']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="<?= controller_url('licences/per_year') ?>" class="btn btn-primary btn-sm"><?= $this->lang->line('gvv_vsc_manage') ?></a>
    <?php endif; ?>
</div>

<script>
document.getElementById('vsc_year').addEventListener('change', function () {
    window.location.href = '<?= controller_url('licences/vols_sans_cotisation') ?>/' + this.value;
});
</script>

