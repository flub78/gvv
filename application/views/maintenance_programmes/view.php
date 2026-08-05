<?php
/**
 * Vue : detail d'un programme d'entretien (metadonnees, document, structure)
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-clipboard-list" aria-hidden="true"></i>
            <?= htmlspecialchars($programme['titre']) ?>
            <code class="fs-6 text-muted"><?= htmlspecialchars($programme['code']) ?></code>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>/edit/<?= $programme['id'] ?>" class="btn btn-outline-primary">
                <i class="fas fa-edit" aria-hidden="true"></i> <?= $this->lang->line('maintenance_equipements_edit') ?>
            </a>
            <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_retour') ?>
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <?= nl2br(htmlspecialchars($this->session->flashdata('success'))) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <?= nl2br(htmlspecialchars($this->session->flashdata('error'))) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><?= $this->lang->line('maintenance_programme_regle_butee') ?></div>
                <div class="card-body">
                    <?php if ($programme['regle_butee_date']): ?>
                        <p><span class="badge bg-primary"><i class="fas fa-calendar" aria-hidden="true"></i></span>
                           <?= sprintf($this->lang->line('maintenance_programme_regle_date_resume'), $programme['periodicite_mois'] ?: '?') ?></p>
                    <?php endif; ?>
                    <?php if ($programme['regle_butee_heures']): ?>
                        <p><span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half" aria-hidden="true"></i></span>
                           <?= sprintf($this->lang->line('maintenance_programme_regle_heures_resume'), $programme['seuil_heures'] ?: '?') ?></p>
                    <?php endif; ?>
                    <?php if (!$programme['regle_butee_date'] && !$programme['regle_butee_heures']): ?>
                        <p class="text-muted"><?= $this->lang->line('maintenance_programme_aucune_regle') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <?= $this->lang->line('maintenance_programme_document') ?>
                    <a href="<?= controller_url($controller) ?>/upload_form/<?= $programme['id'] ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-upload" aria-hidden="true"></i> <?= $this->lang->line('maintenance_programme_uploader') ?>
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($document): ?>
                        <p>
                            <i class="fas fa-file-alt" aria-hidden="true"></i>
                            <a href="<?= site_url('archived_documents/preview/' . $document['id']) ?>" target="_blank">
                                <?= htmlspecialchars($document['original_filename']) ?>
                            </a>
                        </p>
                        <p class="text-muted small">
                            <?= sprintf($this->lang->line('maintenance_programme_document_depose_le'), date('d/m/Y', strtotime($document['uploaded_at']))) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-muted"><?= $this->lang->line('maintenance_programme_aucun_document') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><?= $this->lang->line('maintenance_programme_structure') ?></div>
        <div class="card-body">
            <?php if (empty($sections)): ?>
                <p class="text-muted"><?= $this->lang->line('maintenance_programme_aucune_structure') ?></p>
            <?php else: ?>
                <?php foreach ($sections as $section): ?>
                    <h6 class="mt-3"><i class="fas fa-folder-open text-primary" aria-hidden="true"></i> <?= htmlspecialchars($section['titre']) ?></h6>
                    <?php if (empty($section['taches'])): ?>
                        <p class="text-muted small ms-4"><?= $this->lang->line('maintenance_programme_section_vide') ?></p>
                    <?php else: ?>
                        <ul class="list-group ms-4 mb-2">
                            <?php foreach ($section['taches'] as $tache): ?>
                                <li class="list-group-item">
                                    <strong><?= htmlspecialchars($tache['titre']) ?></strong>
                                    <?php if (!empty($tache['description'])): ?>
                                        <div class="text-muted small"><?= nl2br(htmlspecialchars($tache['description'])) ?></div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php $this->load->view('bs_footer'); ?>
