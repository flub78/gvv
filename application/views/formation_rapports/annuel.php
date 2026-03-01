<?php
/**
 * Vue : rapport annuel consolidé (vol + théorique) par instructeur et par programme.
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-chart-line" aria-hidden="true"></i>
            <?= $this->lang->line('formation_rapports_annuel_title') ?>
        </h3>
        <div class="d-flex align-items-center gap-3">
            <?= year_selector('formation_rapports/new_year_annuel', $year, $year_selector) ?>
            <a href="<?= controller_url($controller) ?>/export_annuel_csv/<?= $year ?>"
               class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-csv" aria-hidden="true"></i>
                <?= $this->lang->line('formation_rapports_annuel_export_csv') ?>
            </a>
            <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <?= $this->lang->line('formation_rapports_title') ?>
            </a>
        </div>
    </div>

    <script>
    function new_year() {
        var year = document.getElementById('year_selector').value;
        var url = document.querySelector('input[name="controller_url"]').value + '/new_year_annuel/' + year;
        window.location.href = url;
    }
    </script>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="annuelTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-instructeurs-tab"
                    data-bs-toggle="tab" data-bs-target="#tab-instructeurs"
                    type="button" role="tab" aria-controls="tab-instructeurs" aria-selected="true">
                <i class="fas fa-user-tie me-1" aria-hidden="true"></i>
                <?= $this->lang->line('formation_rapports_annuel_par_instructeur') ?>
                <span class="badge bg-secondary ms-1"><?= count($stats_instructeurs) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-programmes-tab"
                    data-bs-toggle="tab" data-bs-target="#tab-programmes"
                    type="button" role="tab" aria-controls="tab-programmes" aria-selected="false">
                <i class="fas fa-book me-1" aria-hidden="true"></i>
                <?= $this->lang->line('formation_rapports_annuel_par_programme') ?>
                <span class="badge bg-secondary ms-1"><?= count($stats_programmes) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="annuelTabsContent">

        <!-- ============================================================ -->
        <!-- TAB 1: Par instructeur                                        -->
        <!-- ============================================================ -->
        <div class="tab-pane fade show active" id="tab-instructeurs" role="tabpanel" aria-labelledby="tab-instructeurs-tab">

            <?php if (empty($stats_instructeurs)): ?>
                <p class="text-muted"><?= $this->lang->line('formation_rapports_annuel_aucun') ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th><?= $this->lang->line('formation_inscription_instructeur') ?></th>
                                <th class="text-center" colspan="3">
                                    <i class="fas fa-plane text-primary me-1" aria-hidden="true"></i>
                                    <?= $this->lang->line('formation_seance_nature_vol') ?>
                                </th>
                                <th class="text-center" colspan="3">
                                    <i class="fas fa-chalkboard text-success me-1" aria-hidden="true"></i>
                                    <?= $this->lang->line('formation_seance_nature_theorique') ?>
                                </th>
                                <th class="text-center">
                                    <?= $this->lang->line('formation_rapports_annuel_total') ?>
                                </th>
                            </tr>
                            <tr class="table-secondary">
                                <th></th>
                                <th class="text-center"><?= $this->lang->line('formation_rapports_annuel_nb_seances_vol') ?></th>
                                <th class="text-center"><?= $this->lang->line('formation_rapports_annuel_heures_vol') ?></th>
                                <th class="text-center"><?= $this->lang->line('formation_rapports_annuel_nb_eleves_vol') ?></th>
                                <th class="text-center"><?= $this->lang->line('formation_rapports_annuel_nb_seances_sol') ?></th>
                                <th class="text-center"><?= $this->lang->line('formation_rapports_annuel_heures_sol') ?></th>
                                <th class="text-center"><?= $this->lang->line('formation_rapports_annuel_nb_eleves_sol') ?></th>
                                <th class="text-center"><?= $this->lang->line('formation_rapports_nb_seances') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $tot_sv = $tot_hv = $tot_ev = $tot_ss = $tot_hs = $tot_es = $tot_t = 0;
                            foreach ($stats_instructeurs as $s):
                                $total = $s['nb_seances_vol'] + $s['nb_seances_sol'];
                                $tot_sv += $s['nb_seances_vol'];
                                $tot_hv += $s['heures_vol'];
                                $tot_ev += $s['nb_eleves_vol'];
                                $tot_ss += $s['nb_seances_sol'];
                                $tot_hs += $s['heures_sol'];
                                $tot_es += $s['nb_eleves_sol'];
                                $tot_t  += $total;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars(trim($s['prenom'] . ' ' . $s['nom'])) ?></td>
                                <td class="text-center"><?= $s['nb_seances_vol'] ?: '—' ?></td>
                                <td class="text-center"><?= $s['heures_vol'] > 0 ? number_format($s['heures_vol'], 1, ',', '') . ' h' : '—' ?></td>
                                <td class="text-center"><?= $s['nb_eleves_vol'] ?: '—' ?></td>
                                <td class="text-center"><?= $s['nb_seances_sol'] ?: '—' ?></td>
                                <td class="text-center"><?= $s['heures_sol'] > 0 ? number_format($s['heures_sol'], 1, ',', '') . ' h' : '—' ?></td>
                                <td class="text-center"><?= $s['nb_eleves_sol'] ?: '—' ?></td>
                                <td class="text-center"><strong><?= $total ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th><?= $this->lang->line('gvv_str_total') ?></th>
                                <th class="text-center"><?= $tot_sv ?></th>
                                <th class="text-center"><?= number_format($tot_hv, 1, ',', '') ?> h</th>
                                <th class="text-center"><?= $tot_ev ?></th>
                                <th class="text-center"><?= $tot_ss ?></th>
                                <th class="text-center"><?= number_format($tot_hs, 1, ',', '') ?> h</th>
                                <th class="text-center"><?= $tot_es ?></th>
                                <th class="text-center"><?= $tot_t ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>

        </div>

        <!-- ============================================================ -->
        <!-- TAB 2: Par programme                                          -->
        <!-- ============================================================ -->
        <div class="tab-pane fade" id="tab-programmes" role="tabpanel" aria-labelledby="tab-programmes-tab">

            <?php if (empty($stats_programmes)): ?>
                <p class="text-muted"><?= $this->lang->line('formation_rapports_annuel_aucun') ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th><?= $this->lang->line('formation_seance_programme') ?></th>
                                <th class="text-center" colspan="2">
                                    <i class="fas fa-plane text-primary me-1" aria-hidden="true"></i>
                                    <?= $this->lang->line('formation_seance_nature_vol') ?>
                                </th>
                                <th class="text-center" colspan="2">
                                    <i class="fas fa-chalkboard text-success me-1" aria-hidden="true"></i>
                                    <?= $this->lang->line('formation_seance_nature_theorique') ?>
                                </th>
                            </tr>
                            <tr class="table-secondary">
                                <th></th>
                                <th class="text-center"><?= $this->lang->line('formation_rapports_annuel_nb_seances_vol') ?></th>
                                <th class="text-center"><?= $this->lang->line('formation_rapports_annuel_heures_vol') ?></th>
                                <th class="text-center"><?= $this->lang->line('formation_rapports_annuel_nb_seances_sol') ?></th>
                                <th class="text-center"><?= $this->lang->line('formation_rapports_annuel_heures_sol') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $tot_sv = $tot_hv = $tot_ss = $tot_hs = 0;
                            foreach ($stats_programmes as $p):
                                $tot_sv += $p['nb_seances_vol'];
                                $tot_hv += $p['heures_vol'];
                                $tot_ss += $p['nb_seances_sol'];
                                $tot_hs += $p['heures_sol'];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($p['programme_titre']) ?></td>
                                <td class="text-center"><?= $p['nb_seances_vol'] ?: '—' ?></td>
                                <td class="text-center"><?= $p['heures_vol'] > 0 ? number_format($p['heures_vol'], 1, ',', '') . ' h' : '—' ?></td>
                                <td class="text-center"><?= $p['nb_seances_sol'] ?: '—' ?></td>
                                <td class="text-center"><?= $p['heures_sol'] > 0 ? number_format($p['heures_sol'], 1, ',', '') . ' h' : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th><?= $this->lang->line('gvv_str_total') ?></th>
                                <th class="text-center"><?= $tot_sv ?></th>
                                <th class="text-center"><?= number_format($tot_hv, 1, ',', '') ?> h</th>
                                <th class="text-center"><?= $tot_ss ?></th>
                                <th class="text-center"><?= number_format($tot_hs, 1, ',', '') ?> h</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>

        </div>

    </div><!-- /.tab-content -->

</div>
<?php $this->load->view('bs_footer'); ?>
