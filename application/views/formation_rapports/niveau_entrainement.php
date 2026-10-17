<!-- VIEW: application/views/formation_rapports/niveau_entrainement.php -->
<?php
/**
 * Vue : niveau d'entraînement – heures de vol des pilotes solo sur les
 * 12 derniers mois, par ordre croissant, avec alerte sous le seuil.
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$nb_sous_seuil = 0;
foreach ($pilotes as $p) {
    if ((float) $p['heures_total'] < $seuil_heures) {
        $nb_sous_seuil++;
    }
}
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
            <?= $this->lang->line('formation_niveau_entrainement_title') ?>
            <span class="badge bg-secondary align-middle" style="font-size: 0.5em;">
                <i class="fas fa-layer-group" aria-hidden="true"></i>
                <?= $this->lang->line("formation_rapports_section_active") ?> : <?= htmlspecialchars($section_active_label) ?>
            </span>
        </h3>
    </div>

    <p class="text-muted mb-1">
        <?= sprintf($this->lang->line('formation_niveau_entrainement_intro'), date_db2ht($date_debut)) ?>
    </p>
    <p class="text-muted fst-italic">
        <i class="fas fa-info-circle" aria-hidden="true"></i>
        <?= $this->lang->line('formation_niveau_entrainement_avertissement') ?>
    </p>

    <?php if (empty($pilotes)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <?= $this->lang->line('formation_niveau_entrainement_aucun') ?>
        </div>
    <?php else: ?>
        <?php if ($nb_sous_seuil > 0): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <?= sprintf($this->lang->line('formation_niveau_entrainement_alerte'), $nb_sous_seuil, $seuil_heures) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead class="table-light">
                    <tr>
                        <th><?= $this->lang->line('formation_rapports_conformite_pilote') ?></th>
                        <th class="text-center"><?= $this->lang->line('formation_niveau_entrainement_heures_total') ?></th>
                        <th class="text-center"><?= $this->lang->line('formation_niveau_entrainement_heures_solo') ?></th>
                        <th class="text-center"><?= $this->lang->line('formation_niveau_entrainement_heures_dc') ?></th>
                        <th class="text-center"><?= $this->lang->line('formation_niveau_entrainement_heures_instruction') ?></th>
                        <th class="text-center"><?= $this->lang->line('formation_niveau_entrainement_nb_vols') ?></th>
                        <th class="text-center"><?= $this->lang->line('formation_niveau_entrainement_dernier_vol') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pilotes as $p): ?>
                        <?php
                        $sous_seuil = (float) $p['heures_total'] < $seuil_heures;
                        $instructeur = (int) $p['nb_vols_instruction'] > 0;
                        ?>
                        <tr class="<?= $sous_seuil ? 'table-warning' : '' ?>">
                            <td>
                                <?php if ($sous_seuil): ?>
                                    <i class="fas fa-exclamation-triangle text-warning me-1"
                                       title="<?= sprintf($this->lang->line('formation_niveau_entrainement_sous_seuil'), $seuil_heures) ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-user me-1" aria-hidden="true"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars(trim($p['mprenom'] . ' ' . $p['mnom'])) ?>
                                <?php if ($instructeur): ?>
                                    <span class="badge bg-info text-dark ms-1"><?= $this->lang->line('formation_niveau_entrainement_instructeur') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold"><?= centieme_to_hhmm($p['heures_total']) ?></td>
                            <td class="text-center"><?= centieme_to_hhmm($p['heures_solo']) ?></td>
                            <td class="text-center"><?= (float) $p['heures_dc'] > 0 ? centieme_to_hhmm($p['heures_dc']) : '' ?></td>
                            <td class="text-center"><?= $instructeur ? centieme_to_hhmm($p['heures_instruction']) : '' ?></td>
                            <td class="text-center"><?= (int) $p['nb_vols'] ?></td>
                            <td class="text-center"><?= date_db2ht($p['dernier_vol']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php $this->load->view('bs_footer'); ?>
