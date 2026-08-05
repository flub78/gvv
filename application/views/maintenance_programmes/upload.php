<?php
/**
 * Vue : depot d'une (nouvelle) version du programme d'entretien (markdown)
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$form_url = controller_url($controller) . '/upload/' . $programme['id'];
$has_document = !empty($programme['document_id']);
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-upload" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_programme_uploader') ?>
        </h3>
        <a href="<?= controller_url($controller) ?>/view/<?= $programme['id'] ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_retour') ?>
        </a>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <?= nl2br(htmlspecialchars($this->session->flashdata('error'))) ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-info">
        <i class="fas fa-info-circle" aria-hidden="true"></i>
        <?= $has_document
            ? $this->lang->line('maintenance_programme_upload_info_nouvelle_version')
            : $this->lang->line('maintenance_programme_upload_info_premiere_version') ?>
    </div>

    <?= form_open_multipart($form_url, array('class' => 'card')) ?>
    <div class="card-body">

        <div class="mb-3 row">
            <label for="markdown_file" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_programme_fichier') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-6">
                <input type="file" class="form-control" id="markdown_file" name="markdown_file" accept=".md,.txt" required>
                <div class="form-text text-muted"><?= $this->lang->line('maintenance_programme_fichier_help') ?></div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="description" class="col-sm-3 col-form-label">
                <?= $this->lang->line('maintenance_equipement_description') ?>
            </label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="description" name="description" maxlength="255">
            </div>
        </div>

    </div>
    <div class="card-footer text-end">
        <a href="<?= controller_url($controller) ?>/view/<?= $programme['id'] ?>" class="btn btn-outline-secondary me-2">
            <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_annuler') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-upload" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_programme_uploader') ?>
        </button>
    </div>
    <?= form_close() ?>

</div>
<?php $this->load->view('bs_footer'); ?>
