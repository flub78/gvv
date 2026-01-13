<!-- VIEW: application/views/email_lists/create.php -->
<?php
/**
 * Vue simple de création de liste de diffusion (workflow v1.4)
 * Formulaire simple sans JavaScript, POST vers store()
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('email_lists');
?>

<div id="body" class="body container-fluid">
    <h3><?= $this->lang->line('email_lists_create') ?></h3>

    <?php
    // Show validation errors
    if (validation_errors()) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<strong><i class="bi bi-exclamation-triangle-fill"></i></strong> ';
        echo validation_errors();
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }

    // Show error message
    if ($this->session->flashdata('error')) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<strong><i class="bi bi-exclamation-triangle-fill"></i></strong> ';
        echo nl2br(htmlspecialchars($this->session->flashdata('error')));
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
    ?>

    <div class="card">
        <div class="card-body">
            <p class="text-muted">
                <?= $this->lang->line('email_lists_create') ?> - <?= $this->lang->line('email_lists_description') ?>
            </p>

            <form action="<?= site_url('email_lists/store') ?>" method="post" accept-charset="utf-8">

                <!-- Nom de la liste -->
                <div class="row mb-3">
                    <label for="name" class="col-sm-2 col-form-label">
                        <?= $this->lang->line("email_lists_name") ?> <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-10">
                        <input type="text"
                               class="form-control <?= form_error('name') ? 'is-invalid' : '' ?>"
                               id="name"
                               name="name"
                               value="<?= set_value('name', '') ?>"
                               required
                               maxlength="255">
                        <?php if (form_error('name')): ?>
                            <div class="invalid-feedback d-block">
                                <?= form_error('name') ?>
                            </div>
                        <?php endif; ?>
                        <small class="form-text text-muted">
                            Nom unique pour identifier la liste
                        </small>
                    </div>
                </div>

                <!-- Description -->
                <div class="row mb-3">
                    <label for="description" class="col-sm-2 col-form-label">
                        <?= $this->lang->line("email_lists_description") ?>
                    </label>
                    <div class="col-sm-10">
                        <textarea class="form-control <?= form_error('description') ? 'is-invalid' : '' ?>"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  maxlength="1000"><?= set_value('description', '') ?></textarea>
                        <?php if (form_error('description')): ?>
                            <div class="invalid-feedback d-block">
                                <?= form_error('description') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Type de membre -->
                <div class="row mb-3">
                    <label for="active_member" class="col-sm-2 col-form-label">
                        <?= $this->lang->line("email_lists_active_member") ?> <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-10">
                        <select class="form-select <?= form_error('active_member') ? 'is-invalid' : '' ?>"
                                id="active_member"
                                name="active_member"
                                required>
                            <option value="active" <?= set_select('active_member', 'active', TRUE) ?>>
                                <?= $this->lang->line("email_lists_active_members_only") ?>
                            </option>
                            <option value="inactive" <?= set_select('active_member', 'inactive') ?>>
                                <?= $this->lang->line("email_lists_inactive_members_only") ?>
                            </option>
                            <option value="all" <?= set_select('active_member', 'all') ?>>
                                <?= $this->lang->line("email_lists_all_members") ?>
                            </option>
                        </select>
                        <?php if (form_error('active_member')): ?>
                            <div class="invalid-feedback d-block">
                                <?= form_error('active_member') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Visible (Public/Private) -->
                <div class="row mb-3">
                    <div class="col-sm-10 offset-sm-2">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="visible"
                                   name="visible"
                                   value="1"
                                   <?= set_checkbox('visible', '1', TRUE) ?>>
                            <label class="form-check-label" for="visible">
                                <?= $this->lang->line("email_lists_visible") ?>
                            </label>
                            <div class="form-text">
                                <?= $this->lang->line("email_lists_visible_help") ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="row">
                    <div class="col-sm-10 offset-sm-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> <?= $this->lang->line("gvv_button_save") ?>
                        </button>
                        <a href="<?= site_url('email_lists') ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> <?= $this->lang->line("gvv_button_cancel") ?>
                        </a>
                    </div>
                </div>

            </form>

            <hr class="my-4">

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Note:</strong> Après avoir créé la liste, vous pourrez ajouter des adresses email via différentes méthodes :
                <ul class="mb-0 mt-2">
                    <li>Par critères (rôles et sections)</li>
                    <li>Sélection manuelle de membres</li>
                    <li>Import de fichiers</li>
                </ul>
            </div>

        </div>
    </div>

</div>
