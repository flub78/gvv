<!-- VIEW: application/views/adherents_report/bs_page.php -->
<?php
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Vue des statistiques adhérents
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid">
    <h3><?= translation('gvv_adherents_report_title') ?> - <?= $year ?></h3>

    <!-- Sélecteur d'année -->
    <div class="row mb-4">
        <div class="col-md-4">
            <label for="year_selector" class="form-label"><?= translation('gvv_adherents_report_select_year') ?></label>
            <select class="form-select" id="year_selector" name="year">
                <?php foreach ($year_selector as $y => $label): ?>
                    <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php
    $section_names = array();
    foreach ($sections as $section) {
        $section_names[$section['id']] = $section['nom'];
    }

    // Tableau de répartition : une ligne par classe, une colonne par section + total club
    $render_repartition = function ($repartition, $show_percent) use ($sections) {
        $CI = &get_instance();
        $columns = array();
        foreach ($sections as $section) {
            $columns[] = 'section_' . $section['id'];
        }
        $columns[] = 'club_total';
        ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th></th>
                        <?php foreach ($sections as $section): ?>
                            <th class="text-center"><?= htmlspecialchars($section['nom']) ?></th>
                        <?php endforeach; ?>
                        <th class="text-center table-primary"><?= translation('gvv_adherents_report_club_total') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($repartition['counts'] as $key => $row): ?>
                        <tr class="<?= ($key == 'unknown') ? 'table-warning' : '' ?>" data-age-class="<?= $key ?>">
                            <td><strong><?= translation('gvv_adherents_report_' . $key) ?></strong></td>
                            <?php foreach ($columns as $column): ?>
                                <td class="text-center<?= ($column == 'club_total') ? ' table-primary' : '' ?>">
                                    <?= ($column == 'club_total') ? '<strong>' . $row[$column] . '</strong>' : $row[$column] ?>
                                    <?php if ($show_percent && $repartition['total'][$column]): ?>
                                        <small class="text-muted">(<?= $CI->adherents_stats->pourcentage($row[$column], $repartition['total'][$column]) ?>&nbsp;%)</small>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <td><strong><?= translation('gvv_adherents_report_total') ?></strong></td>
                        <?php foreach ($columns as $column): ?>
                            <td class="text-center<?= ($column == 'club_total') ? ' table-primary' : '' ?>"><strong><?= $repartition['total'][$column] ?></strong></td>
                        <?php endforeach; ?>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php
    };
    ?>

    <!-- Âge inconnu -->
    <?php if (count($age_inconnu) > 0): ?>
        <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2" id="age_inconnu_alert">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?= sprintf(translation('gvv_adherents_report_unknown_count'), count($age_inconnu)) ?></span>
            <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#age_inconnu_list" aria-expanded="false" aria-controls="age_inconnu_list">
                <?= translation('gvv_adherents_report_show_list') ?>
            </button>
        </div>
        <div class="collapse mb-3" id="age_inconnu_list">
            <div class="card card-body">
                <h6><?= translation('gvv_adherents_report_unknown_list_title') ?></h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th><?= translation('gvv_adherents_report_col_member') ?></th>
                                <th><?= translation('gvv_adherents_report_col_sections') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($age_inconnu as $member): ?>
                                <?php
                                $names = array();
                                foreach ($member['sections'] as $section_id) {
                                    if (isset($section_names[$section_id])) {
                                        $names[] = $section_names[$section_id];
                                    }
                                }
                                ?>
                                <tr>
                                    <td><a href="<?= controller_url('membre/edit/' . rawurlencode($member['mlogin'])) ?>"><?= htmlspecialchars($member['mnom'] . ' ' . $member['mprenom']) ?></a></td>
                                    <td><?= $names ? htmlspecialchars(implode(', ', $names)) : '<span class="text-muted">' . translation('gvv_adherents_report_no_section') . '</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            <?= translation('gvv_adherents_report_none_unknown') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Classes d'âge réglementaires -->
    <div class="card mb-4" id="classes_reglementaires">
        <div class="card-header"><h5 class="mb-0"><?= translation('gvv_adherents_report_regl_title') ?></h5></div>
        <div class="card-body">
            <?php $render_repartition($classes_reglementaires, false); ?>
        </div>
    </div>

    <!-- Tranches de 10 ans -->
    <div class="card mb-4" id="tranches_10_ans">
        <div class="card-header"><h5 class="mb-0"><?= translation('gvv_adherents_report_tranches_title') ?></h5></div>
        <div class="card-body">
            <?php $render_repartition($tranches_10_ans, true); ?>
            <h6 class="mt-4"><?= translation('gvv_adherents_report_chart_title') ?></h6>
            <div style="position: relative; height: 320px; max-width: 800px;">
                <canvas id="tranches_chart" aria-label="<?= htmlspecialchars(translation('gvv_adherents_report_chart_title')) ?>" role="img"></canvas>
            </div>
        </div>
    </div>

    <!-- Note explicative -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <?= translation('gvv_adherents_report_age_note') ?>
        <?= translation('gvv_adherents_report_note') ?>
    </div>
</div>

<?php
$chart_labels = array();
$chart_values = array();
foreach ($tranches_10_ans['counts'] as $key => $row) {
    if ($key == 'unknown') {
        continue;
    }
    $chart_labels[] = translation('gvv_adherents_report_' . $key);
    $chart_values[] = $row['club_total'];
}
?>
<script src="<?= js_url('chart.umd.min') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var canvas = document.getElementById('tranches_chart');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: <?= json_encode(translation('gvv_adherents_report_club_total')) ?>,
                data: <?= json_encode($chart_values) ?>,
                backgroundColor: '#2a7ab0'
            }]
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
});
</script>

<script>
$(document).ready(function() {
    // Gestionnaire pour le changement d'année
    $('#year_selector').on('change', function() {
        var selectedYear = $(this).val();

        $.ajax({
            url: '<?= site_url('adherents_report/set_year') ?>/' + selectedYear,
            type: 'GET',
            dataType: 'json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                if (response.success) {
                    // Recharger la page pour afficher les nouvelles statistiques
                    window.location.reload();
                } else {
                    console.error('Erreur lors du changement d\'année:', response.error);
                    alert('Erreur: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
                alert('Erreur lors du changement d\'année');
            }
        });
    });
});
</script>
