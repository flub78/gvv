<?php
/**
 * Vue : tableau des potentiels, une ligne par aeronef, une colonne par
 * programme d'entretien actif (miroir du tableau blanc physique d'atelier)
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-table" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_tableau_titre') ?>
        </h3>
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-shield-alt" aria-hidden="true"></i> <?= $this->lang->line('maintenance_synthese_titre') ?>
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="section_select" class="form-label"><?= $this->lang->line('maintenance_programme_section') ?></label>
                    <?= form_dropdown('section_select', $section_selector, $section_id, 'class="form-select" id="section_select"') ?>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary" id="btn-filtrer-section">
                        <i class="fas fa-filter" aria-hidden="true"></i> <?= $this->lang->line('maintenance_synthese_filtrer') ?>
                    </button>
                </div>
            </form>
            <script>
                document.getElementById('btn-filtrer-section').addEventListener('click', function () {
                    var section = document.getElementById('section_select').value;
                    window.location.href = '<?= controller_url($controller) ?>/tableau/' + encodeURIComponent(section);
                });
            </script>
        </div>
    </div>

    <?php if (empty($programmes)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_tableau_aucun_programme') ?>
            <a href="<?= controller_url('maintenance_programmes') ?>"><?= $this->lang->line('db_card_maintenance_prog') ?></a>.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th><?= $this->lang->line('maintenance_synthese_aeronef') ?></th>
                        <th class="text-end"><?= $this->lang->line('maintenance_tableau_heures_reelles') ?></th>
                        <?php foreach ($programmes as $programme): ?>
                            <th class="text-center" title="<?= htmlspecialchars($programme['titre']) ?>">
                                <?= htmlspecialchars($programme['code']) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($aeronefs)): ?>
                    <tr>
                        <td colspan="<?= 2 + count($programmes) ?>" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            <?= $this->lang->line('maintenance_synthese_aucun_aeronef') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($aeronefs as $aeronef): ?>
                    <tr>
                        <td>
                            <a href="<?= controller_url($controller) ?>/aeronef/<?= htmlspecialchars($aeronef['macimmat']) ?>">
                                <?= htmlspecialchars($aeronef['macmodele']) ?> - <?= htmlspecialchars($aeronef['macimmat']) ?>
                            </a>
                        </td>
                        <td class="text-end">
                            <?= $aeronef['heures_reelles'] !== null ? htmlspecialchars($aeronef['heures_reelles']) . ' h' : '—' ?>
                        </td>
                        <?php foreach ($programmes as $programme): ?>
                            <?php $dossier = isset($aeronef['dossiers'][$programme['id']]) ? $aeronef['dossiers'][$programme['id']] : null; ?>
                            <td class="text-center">
                                <?php if ($dossier === null): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <span class="badge <?= $etat_badges[$dossier['etat']] ?>" title="<?= htmlspecialchars($etat_labels[$dossier['etat']]) ?>">
                                        <?php if ($programme['regle_butee_heures'] && $dossier['heures_restantes_courant'] !== null): ?>
                                            <?= htmlspecialchars($dossier['heures_restantes_courant']) ?> h
                                        <?php elseif ($programme['regle_butee_date'] && !empty($dossier['echeance_courante'])): ?>
                                            <?= htmlspecialchars(date('d/m/Y', strtotime($dossier['echeance_courante']))) ?>
                                        <?php else: ?>
                                            <?= $this->lang->line('maintenance_tableau_pas_de_releve') ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<?php $this->load->view('bs_footer'); ?>
