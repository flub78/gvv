<?php
/**
 * Vue : synthese de navigabilite d'un aeronef, etat de chaque entite maintenable (PRD EF7.1)
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
            <?= htmlspecialchars($aeronef['macmodele']) ?> - <?= htmlspecialchars($aeronef['macimmat']) ?>
            <span class="badge <?= $etat_badges[$aeronef['etat']] ?>"><?= htmlspecialchars($etat_labels[$aeronef['etat']]) ?></span>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>/export_pdf/<?= htmlspecialchars($aeronef['macimmat']) ?>" class="btn btn-outline-danger" target="_blank">
                <i class="fas fa-file-pdf" aria-hidden="true"></i> <?= $this->lang->line('maintenance_synthese_export_pdf') ?>
            </a>
            <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_retour') ?>
            </a>
        </div>
    </div>

    <?php foreach ($entites as $entite): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas <?= $entite['entite_type'] === 'aeronef' ? 'fa-plane' : 'fa-cog' ?>" aria-hidden="true"></i>
                    <?= htmlspecialchars($entite['label']) ?>
                </span>
                <span class="badge <?= $etat_badges[$entite['etat']] ?>"><?= htmlspecialchars($etat_labels[$entite['etat']]) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($entite['dossiers'])): ?>
                    <p class="text-muted p-3 mb-0"><?= $this->lang->line('maintenance_synthese_aucun_dossier') ?></p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <tbody>
                        <?php foreach ($entite['dossiers'] as $dossier): ?>
                            <tr>
                                <td>
                                    <a href="<?= controller_url('maintenance_dossiers') ?>/view/<?= $dossier['id'] ?>">
                                        <?= htmlspecialchars($dossier['programme_code']) ?> - <?= htmlspecialchars($dossier['programme_titre']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($dossier['echeance_courante'])): ?>
                                        <?= $this->lang->line('maintenance_synthese_echeance') ?> : <?= date('d/m/Y', strtotime($dossier['echeance_courante'])) ?>
                                    <?php endif; ?>
                                    <?php if (isset($dossier['heures_restantes_courant']) && $dossier['heures_restantes_courant'] !== null): ?>
                                        <?= $this->lang->line('maintenance_synthese_potentiel') ?> : <?= htmlspecialchars($dossier['heures_restantes_courant']) ?> h
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <span class="badge <?= $etat_badges[$dossier['etat']] ?>"><?= htmlspecialchars($etat_labels[$dossier['etat']]) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>
<?php $this->load->view('bs_footer'); ?>
