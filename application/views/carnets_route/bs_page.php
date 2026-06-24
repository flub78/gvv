<!-- VIEW: application/views/carnets_route/bs_page.php -->
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
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('carnets_route');
$this->load->helper('validation');
?>
<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<h3><?= $this->lang->line('carnets_route_title') ?></h3>

<!-- Zone de filtres -->
<form action="<?= controller_url('carnets_route/filter') ?>" method="post" accept-charset="utf-8">
    <div class="d-md-flex flex-row align-items-end gap-3 mb-3">
        <div>
            <label class="form-label"><?= $this->lang->line('carnets_route_filter_machine') ?></label>
            <?= dropdown_field('carnet_macid', $macid, $avion_selector, 'class="form-select"') ?>
        </div>
        <div>
            <label class="form-label"><?= $this->lang->line('carnets_route_filter_date_debut') ?></label>
            <input type="date" name="carnet_date_debut" value="<?= htmlspecialchars($date_debut) ?>" class="form-control" />
        </div>
        <div>
            <label class="form-label"><?= $this->lang->line('carnets_route_filter_date_fin') ?></label>
            <input type="date" name="carnet_date_fin" value="<?= htmlspecialchars($date_fin) ?>" class="form-control" />
        </div>
        <div>
            <button type="submit" class="btn btn-primary">
                <?= $this->lang->line('gvv_str_select') ?>
            </button>
        </div>
    </div>
</form>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (empty($macid)): ?>
<div class="alert alert-info"><?= $this->lang->line('carnets_route_select_machine') ?></div>

<?php elseif (empty($rows)): ?>
<div class="alert alert-info"><?= $this->lang->line('carnets_route_no_flights') ?></div>

<?php else: ?>

<!-- Résumé des anomalies -->
<?php
$has_anomaly = ($summary['gap'] > 0 || $summary['overlap'] > 0 || $summary['missing'] > 0);
if ($has_anomaly):
?>
<div class="alert alert-warning">
    <strong><?= $this->lang->line('carnets_route_summary_anomalies') ?> :</strong>
    <?php if ($summary['gap'] > 0): ?>
        <span class="badge bg-warning text-dark me-2"><?= $summary['gap'] ?> <?= $this->lang->line('carnets_route_gap') ?></span>
    <?php endif; ?>
    <?php if ($summary['overlap'] > 0): ?>
        <span class="badge bg-danger me-2"><?= $summary['overlap'] ?> <?= $this->lang->line('carnets_route_overlap') ?></span>
    <?php endif; ?>
    <?php if ($summary['missing'] > 0): ?>
        <span class="badge bg-secondary me-2"><?= $summary['missing'] ?> <?= $this->lang->line('carnets_route_missing') ?></span>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="alert alert-success"><?= $this->lang->line('carnets_route_summary_ok') ?></div>
<?php endif; ?>

<!-- Tableau principal -->
<table class="searchable_nosort_datatable table table-bordered table-sm" id="carnet-table">
    <thead class="table-dark">
        <tr>
            <th><?= $this->lang->line('carnets_route_col_date') ?></th>
            <th><?= $this->lang->line('carnets_route_col_pilote') ?></th>
            <th><?= $this->lang->line('carnets_route_col_immat') ?></th>
            <th><?= $this->lang->line('carnets_route_col_hora_deb') ?></th>
            <th><?= $this->lang->line('carnets_route_col_hora_fin') ?></th>
            <th><?= $this->lang->line('carnets_route_col_duree') ?></th>
            <th><?= $this->lang->line('carnets_route_col_depart') ?></th>
            <th><?= $this->lang->line('carnets_route_col_arrivee') ?></th>
            <th><?= $this->lang->line('carnets_route_col_obs') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <?php if ($row['type'] === 'flight'): ?>
            <?php
                $f    = $row['data'];
                $mode = isset($f['horametre_mode']) ? (int)$f['horametre_mode'] : 0;
                if ($f['status'] === 'ok') {
                    $tr_class = 'table-success';
                } elseif ($f['status'] === 'missing') {
                    $tr_class = 'table-secondary';
                } else {
                    $tr_class = 'table-danger';
                }
            ?>
            <tr class="<?= $tr_class ?>">
                <td><?= date_db2ht($f['vadate']) ?></td>
                <td><?= htmlspecialchars($f['pilote']) ?></td>
                <td><?= htmlspecialchars($f['vamacid']) ?></td>
                <td><?= horametre_display($f['vacdeb'], $mode) ?></td>
                <td><?= horametre_display($f['vacfin'], $mode) ?></td>
                <td><?= horametre_display($f['vaduree'], $mode) ?></td>
                <td><?= htmlspecialchars($f['valieudeco']) ?></td>
                <td><?= htmlspecialchars($f['valieuatt']) ?></td>
                <td><?= htmlspecialchars($f['vaobs']) ?></td>
            </tr>
        <?php else: ?>
            <?php
                if ($row['type'] === 'gap') {
                    $tr_class  = 'table-warning';
                    $label_key = 'carnets_route_gap';
                } elseif ($row['type'] === 'overlap') {
                    $tr_class  = 'table-danger';
                    $label_key = 'carnets_route_overlap';
                } else {
                    $tr_class  = 'table-secondary';
                    $label_key = 'carnets_route_missing';
                }
                $label = $this->lang->line($label_key);
                if ($row['duration'] > 0) {
                    $label .= ' : ' . $row['duration'];
                }
            ?>
            <tr class="<?= $tr_class ?> fw-bold">
                <td><?= htmlspecialchars($label) ?></td>
                <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Boutons d'export sous le tableau -->
<div class="mt-2">
    <a href="<?= controller_url('carnets_route/csv') ?>" class="btn btn-outline-secondary me-1">
        <i class="fas fa-file-csv"></i> CSV
    </a>
    <a href="<?= controller_url('carnets_route/pdf') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-file-pdf"></i> PDF
    </a>
</div>

<?php endif; ?>

</div>
