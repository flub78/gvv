<?php
/**
 * Vue : transfert d'un equipement vers un autre aeronef (PRD Parcours 5)
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$form_url = controller_url($controller) . '/transfer_store/' . $equipement['id'];
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-exchange-alt" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_equipements_transfer') ?>
        </h3>
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_retour') ?>
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-info">
        <i class="fas fa-info-circle" aria-hidden="true"></i>
        <?= sprintf(
            $this->lang->line('maintenance_transfert_info'),
            '<strong>' . htmlspecialchars($equipement['nom']) . '</strong>',
            '<strong>' . htmlspecialchars($equipement['aeronef_id']) . '</strong>'
        ) ?>
    </div>

    <?= form_open($form_url, array('class' => 'card')) ?>
    <div class="card-body">

        <div class="mb-3 row">
            <label for="nouvel_aeronef_id" class="col-sm-4 col-form-label">
                <?= $this->lang->line('maintenance_transfert_nouvel_aeronef') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-6">
                <?= form_dropdown('nouvel_aeronef_id', $aeronef_selector, '', 'class="form-select" id="nouvel_aeronef_id" required') ?>
            </div>
        </div>

        <div class="mb-3 row">
            <div class="col-sm-8 offset-sm-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmation" name="confirmation" value="1" required>
                    <label class="form-check-label" for="confirmation">
                        <?= $this->lang->line('maintenance_transfert_confirmation') ?>
                    </label>
                </div>
            </div>
        </div>

    </div>
    <div class="card-footer text-end">
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary me-2">
            <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_annuler') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-exchange-alt" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_equipements_transfer') ?>
        </button>
    </div>
    <?= form_close() ?>

</div>
<?php $this->load->view('bs_footer'); ?>
