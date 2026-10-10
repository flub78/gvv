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

    $fmt_age = function ($age) {
        return ($age === null) ? '—' : number_format($age, 1, ',', '');
    };

    $column_label = function ($column) use ($section_names) {
        return ($column == 'club_total')
            ? translation('gvv_adherents_report_club_total')
            : $section_names[(int) substr($column, strlen('section_'))];
    };

    // En-tête commun : une colonne par section, puis le total club
    $render_header = function () use ($columns, $column_label) {
        ?>
        <thead class="table-dark">
            <tr>
                <th></th>
                <?php foreach ($columns as $column): ?>
                    <th class="text-center<?= ($column == 'club_total') ? ' table-primary' : '' ?>"><?= htmlspecialchars($column_label($column)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <?php
    };

    // Tableau de répartition : une ligne par classe, une colonne par section + total club
    $render_repartition = function ($repartition, $show_percent) use ($columns, $render_header) {
        $CI = &get_instance();
        ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm mb-0">
                <?php $render_header(); ?>
                <tbody>
                    <?php foreach ($repartition['counts'] as $key => $row): ?>
                        <tr class="<?= (substr($key, -7) == 'unknown') ? 'table-warning' : '' ?>" data-age-class="<?= $key ?>">
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

    <!-- Indicateurs -->
    <div class="card mb-4" id="indicateurs">
        <div class="card-header"><h5 class="mb-0"><?= translation('gvv_adherents_report_ind_title') ?></h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm mb-0">
                    <?php $render_header(); ?>
                    <tbody>
                        <?php foreach (array('effectif', 'age_moyen', 'age_median', 'age_inconnu') as $indicateur): ?>
                            <tr data-indicateur="<?= $indicateur ?>">
                                <td><strong><?= translation('gvv_adherents_report_ind_' . $indicateur) ?></strong></td>
                                <?php foreach ($columns as $column): ?>
                                    <?php $values = $indicateurs[$column]; ?>
                                    <td class="text-center<?= ($column == 'club_total') ? ' table-primary fw-bold' : '' ?>">
                                        <?= in_array($indicateur, array('age_moyen', 'age_median')) ? $fmt_age($values[$indicateur]) : $values[$indicateur] ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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

    <!-- Hommes / femmes -->
    <div class="card mb-4" id="sexes">
        <div class="card-header"><h5 class="mb-0"><?= translation('gvv_adherents_report_sex_title') ?></h5></div>
        <div class="card-body">
            <?php $render_repartition($sexes, true); ?>

            <h6 class="mt-4"><?= translation('gvv_adherents_report_pyramid_title') ?></h6>
            <div class="row">
                <div class="col-lg-5">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm" id="pyramide_table">
                            <thead class="table-dark">
                                <tr>
                                    <th><?= translation('gvv_adherents_report_col_tranche') ?></th>
                                    <?php foreach (array_keys(reset($pyramide)) as $sex): ?>
                                        <th class="text-center"><?= translation('gvv_adherents_report_' . $sex) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pyramide as $tranche => $row): ?>
                                    <tr class="<?= ($tranche == 'unknown') ? 'table-warning' : '' ?>">
                                        <td><?= translation('gvv_adherents_report_' . $tranche) ?></td>
                                        <?php foreach ($row as $count): ?>
                                            <td class="text-center"><?= $count ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div style="position: relative; height: 320px;">
                        <canvas id="pyramide_chart" aria-label="<?= htmlspecialchars(translation('gvv_adherents_report_pyramid_title')) ?>" role="img"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Évolution -->
    <div class="card mb-4" id="evolution">
        <div class="card-header"><h5 class="mb-0"><?= translation('gvv_adherents_report_evol_title') ?></h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th><?= translation('gvv_adherents_report_col_year') ?></th>
                            <th class="text-center"><?= translation('gvv_adherents_report_ind_effectif') ?></th>
                            <th class="text-center"><?= translation('gvv_adherents_report_ind_age_moyen') ?></th>
                            <?php foreach ($cles_classes_reglementaires as $classe): ?>
                                <th class="text-center"><?= translation('gvv_adherents_report_' . $classe) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evolution as $evol_year => $row): ?>
                            <tr class="<?= ($evol_year == $year) ? 'table-primary' : '' ?>" data-year="<?= $evol_year ?>">
                                <td><strong><?= $evol_year ?></strong></td>
                                <?php if ($row['effectif'] === null): ?>
                                    <td class="text-center text-muted fst-italic" colspan="<?= 2 + count($cles_classes_reglementaires) ?>"><?= translation('gvv_adherents_report_evol_no_data') ?></td>
                                <?php else: ?>
                                    <td class="text-center"><?= $row['effectif'] ?></td>
                                    <td class="text-center"><?= $fmt_age($row['age_moyen']) ?></td>
                                    <?php foreach ($cles_classes_reglementaires as $classe): ?>
                                        <td class="text-center"><?= $row[$classe] ?></td>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($evolution) > 1): ?>
                <div style="position: relative; height: 300px; max-width: 900px;">
                    <canvas id="evolution_chart" aria-label="<?= htmlspecialchars(translation('gvv_adherents_report_evol_title')) ?>" role="img"></canvas>
                </div>
            <?php endif; ?>
            <p class="text-muted small mt-2 mb-0"><?= translation('gvv_adherents_report_evol_note') ?></p>
        </div>
    </div>

    <!-- Fidélisation -->
    <div class="card mb-4" id="fidelisation">
        <div class="card-header"><h5 class="mb-0"><?= translation('gvv_adherents_report_fid_title') ?></h5></div>
        <div class="card-body">
            <?php if ($annee_en_cours): ?>
                <div class="alert alert-warning py-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= translation('gvv_adherents_report_fid_provisional') ?>
                </div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm mb-0">
                    <?php $render_header(); ?>
                    <tbody>
                        <?php foreach ($fidelisation['counts'] as $key => $row): ?>
                            <tr data-fidelisation="<?= $key ?>">
                                <td><strong><?= translation('gvv_adherents_report_' . $key) ?></strong></td>
                                <?php foreach ($columns as $column): ?>
                                    <td class="text-center<?= ($column == 'club_total') ? ' table-primary' : '' ?>">
                                        <?php if ($row[$column] > 0): ?>
                                            <button type="button" class="btn btn-link btn-sm p-0<?= ($column == 'club_total') ? ' fw-bold' : '' ?>"
                                                data-bs-toggle="collapse" data-bs-target="#fid_<?= $key ?>_<?= $column ?>"
                                                aria-expanded="false" aria-controls="fid_<?= $key ?>_<?= $column ?>"><?= $row[$column] ?></button>
                                        <?php else: ?>
                                            0
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-fidelisation="retention">
                            <td><strong><?= sprintf(translation('gvv_adherents_report_fid_retention'), $year - 1) ?></strong></td>
                            <?php foreach ($columns as $column): ?>
                                <?php $r = $fidelisation['retention'][$column]; ?>
                                <td class="text-center<?= ($column == 'club_total') ? ' table-primary' : '' ?>">
                                    <?php if ($r['taux'] === null): ?>
                                        —
                                    <?php else: ?>
                                        <?= $r['taux'] ?>&nbsp;% <small class="text-muted">(<?= $r['toujours_adherents'] ?>/<?= $r['nouveaux_n1'] ?>)</small>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mt-2 mb-1"><?= translation('gvv_adherents_report_fid_list_hint') ?></p>

            <div id="fid_lists">
                <?php foreach ($fidelisation['members'] as $key => $by_column): ?>
                    <?php foreach ($by_column as $column => $list): ?>
                        <?php if (count($list) == 0) continue; ?>
                        <div class="collapse" id="fid_<?= $key ?>_<?= $column ?>" data-bs-parent="#fid_lists">
                            <div class="card card-body mt-2">
                                <h6><?= translation('gvv_adherents_report_' . $key) ?> — <?= htmlspecialchars($column_label($column)) ?> (<?= count($list) ?>)</h6>
                                <ul class="list-unstyled mb-0" style="columns: 16rem;">
                                    <?php foreach ($list as $member): ?>
                                        <li><a href="<?= controller_url('membre/edit/' . rawurlencode($member['mlogin'])) ?>"><?= htmlspecialchars($member['mnom'] . ' ' . $member['mprenom']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <p class="text-muted small mt-2 mb-0">
                <?= sprintf(translation('gvv_adherents_report_fid_note'), $year, $year - 1) ?>
                <?= translation('gvv_adherents_report_fid_section_note') ?>
            </p>
        </div>
    </div>

    <!-- Ancienneté -->
    <div class="card mb-4" id="anciennete">
        <div class="card-header"><h5 class="mb-0"><?= translation('gvv_adherents_report_anc_title') ?></h5></div>
        <div class="card-body">
            <?php $render_repartition($anciennete, true); ?>
            <p class="text-muted small mt-2 mb-0"><?= translation('gvv_adherents_report_anc_note') ?></p>
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

// Pyramide : tranches de la plus âgée à la plus jeune, hommes en valeurs négatives
$pyramide_labels = array();
$pyramide_hommes = array();
$pyramide_femmes = array();
foreach (array_reverse($pyramide, true) as $tranche => $row) {
    if ($tranche == 'unknown') {
        continue;
    }
    $pyramide_labels[] = translation('gvv_adherents_report_' . $tranche);
    $pyramide_hommes[] = -$row['sex_M'];
    $pyramide_femmes[] = $row['sex_F'];
}

// Axe symétrique autour de zéro
$pyramide_max = max(1, max(array_merge(array_map('abs', $pyramide_hommes), $pyramide_femmes)));

$evolution_years = array_map('strval', array_keys($evolution));
$evolution_effectif = array();
$evolution_age = array();
foreach ($evolution as $row) {
    $evolution_effectif[] = $row['effectif'];
    $evolution_age[] = $row['age_moyen'];
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

    var pyramide = document.getElementById('pyramide_chart');
    if (pyramide) {
        new Chart(pyramide, {
            type: 'bar',
            data: {
                labels: <?= json_encode($pyramide_labels) ?>,
                datasets: [{
                    label: <?= json_encode(translation('gvv_adherents_report_sex_M')) ?>,
                    data: <?= json_encode($pyramide_hommes) ?>,
                    backgroundColor: '#2a7ab0'
                }, {
                    label: <?= json_encode(translation('gvv_adherents_report_sex_F')) ?>,
                    data: <?= json_encode($pyramide_femmes) ?>,
                    backgroundColor: '#c0507a'
                }]
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, min: -<?= $pyramide_max ?>, max: <?= $pyramide_max ?>, ticks: { precision: 0, callback: function(v) { return Math.abs(v); } } },
                    y: { stacked: true }
                },
                plugins: {
                    tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ' : ' + Math.abs(ctx.raw); } } }
                }
            }
        });
    }

    var evolution = document.getElementById('evolution_chart');
    if (evolution) {
        new Chart(evolution, {
            type: 'line',
            data: {
                labels: <?= json_encode($evolution_years) ?>,
                datasets: [{
                    label: <?= json_encode(translation('gvv_adherents_report_ind_effectif')) ?>,
                    data: <?= json_encode($evolution_effectif) ?>,
                    borderColor: '#2a7ab0',
                    backgroundColor: '#2a7ab0',
                    yAxisID: 'y'
                }, {
                    label: <?= json_encode(translation('gvv_adherents_report_ind_age_moyen')) ?>,
                    data: <?= json_encode($evolution_age) ?>,
                    borderColor: '#e08a1e',
                    backgroundColor: '#e08a1e',
                    borderDash: [6, 4],
                    yAxisID: 'y1'
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: <?= json_encode(translation('gvv_adherents_report_ind_effectif')) ?> } },
                    y1: { position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: <?= json_encode(translation('gvv_adherents_report_ind_age_moyen')) ?> } }
                }
            }
        });
    }
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
