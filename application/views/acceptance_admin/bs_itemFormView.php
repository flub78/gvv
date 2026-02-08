<!-- VIEW: application/views/acceptance_admin/bs_itemFormView.php -->
<?php
/**
 * Form view for creating/editing an acceptance item
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('acceptance');
?>

<div id="body" class="body container-fluid">

<h3>
    <?php if ($action == CREATION): ?>
        <i class="fas fa-plus"></i> <?= $this->lang->line('acceptance_add_item') ?>
    <?php else: ?>
        <i class="fas fa-edit"></i> <?= $this->lang->line('acceptance_edit_item') ?>
    <?php endif; ?>
</h3>

<?php if (isset($message)): ?>
    <?= $message ?>
<?php endif; ?>

<?= form_open_multipart('acceptance_admin/formValidation/' . $action, array('class' => 'form-horizontal')) ?>

<?php if ($action == MODIFICATION): ?>
    <?= form_hidden('original_id', isset($id) ? $id : '') ?>
<?php endif; ?>

<div class="card">
    <div class="card-body">

        <!-- Title -->
        <div class="mb-3 row">
            <label for="title" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_title') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-10">
                <?= form_input('title', set_value('title', isset($title) ? $title : ''), 'class="form-control" id="title" maxlength="255" required') ?>
            </div>
        </div>

        <!-- Category -->
        <div class="mb-3 row">
            <label for="category" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_category') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-10">
                <?= form_dropdown('category', $category_options, set_value('category', isset($category) ? $category : ''), 'class="form-select" id="category" required') ?>
            </div>
        </div>

        <!-- Target type -->
        <div class="mb-3 row">
            <label for="target_type" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_target_type') ?>
            </label>
            <div class="col-sm-10">
                <?= form_dropdown('target_type', $target_type_options, set_value('target_type', isset($target_type) ? $target_type : 'internal'), 'class="form-select" id="target_type"') ?>
            </div>
        </div>

        <!-- PDF file upload -->
        <div class="mb-3 row">
            <label for="pdf_file" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_pdf_path') ?>
            </label>
            <div class="col-sm-10">
                <?php if ($action == MODIFICATION && !empty($pdf_path)): ?>
                    <div class="mb-2">
                        <span class="badge bg-info"><i class="fas fa-file-pdf"></i> <?= $this->lang->line('acceptance_current_pdf') ?></span>
                        <a href="<?= site_url('acceptance_admin/download/' . $id) ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download"></i> <?= $this->lang->line('acceptance_download_pdf') ?>
                        </a>
                    </div>
                <?php endif; ?>
                <input type="file" name="pdf_file" id="pdf_file" class="form-control" accept=".pdf">
                <small class="text-muted"><?= $this->lang->line('acceptance_pdf_help') ?></small>
            </div>
        </div>

        <!-- Version date -->
        <div class="mb-3 row">
            <label for="version_date" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_version_date') ?>
            </label>
            <div class="col-sm-10">
                <?= form_input('version_date', set_value('version_date', isset($version_date) ? $version_date : ''), 'class="form-control datepicker" id="version_date" placeholder="jj/mm/aaaa"') ?>
            </div>
        </div>

        <!-- Mandatory -->
        <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_mandatory') ?>
            </label>
            <div class="col-sm-10">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="mandatory" id="mandatory" value="1"
                        <?= (isset($mandatory) && $mandatory) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mandatory">
                        <?= $this->lang->line('acceptance_mandatory_help') ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Deadline -->
        <div class="mb-3 row">
            <label for="deadline" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_deadline') ?>
            </label>
            <div class="col-sm-10">
                <?= form_input('deadline', set_value('deadline', isset($deadline) ? $deadline : ''), 'class="form-control datepicker" id="deadline" placeholder="jj/mm/aaaa"') ?>
            </div>
        </div>

        <!-- Dual validation -->
        <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_dual_validation') ?>
            </label>
            <div class="col-sm-10">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="dual_validation" id="dual_validation" value="1"
                        <?= (isset($dual_validation) && $dual_validation) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="dual_validation">
                        <?= $this->lang->line('acceptance_dual_validation_help') ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Role 1 (shown when dual validation) -->
        <div class="mb-3 row" id="role1_row">
            <label for="role_1" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_role_1') ?>
            </label>
            <div class="col-sm-10">
                <?= form_input('role_1', set_value('role_1', isset($role_1) ? $role_1 : ''), 'class="form-control" id="role_1" placeholder="' . $this->lang->line('acceptance_role_1_placeholder') . '"') ?>
            </div>
        </div>

        <!-- Role 2 -->
        <div class="mb-3 row" id="role2_row">
            <label for="role_2" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_role_2') ?>
            </label>
            <div class="col-sm-10">
                <?= form_input('role_2', set_value('role_2', isset($role_2) ? $role_2 : ''), 'class="form-control" id="role_2" placeholder="' . $this->lang->line('acceptance_role_2_placeholder') . '"') ?>
            </div>
        </div>

        <!-- Target roles -->
        <div class="mb-3 row">
            <label for="target_roles" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_target_roles') ?>
            </label>
            <div class="col-sm-10">
                <?= form_input('target_roles', set_value('target_roles', isset($target_roles) ? $target_roles : ''), 'class="form-control" id="target_roles" placeholder="' . $this->lang->line('acceptance_target_roles_placeholder') . '"') ?>
                <small class="text-muted"><?= $this->lang->line('acceptance_target_roles_help') ?></small>
            </div>
        </div>

        <!-- Active -->
        <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_active') ?>
            </label>
            <div class="col-sm-10">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="active" id="active" value="1"
                        <?= (!isset($active) || $active) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active">
                        <?= $this->lang->line('acceptance_active_help') ?>
                    </label>
                </div>
            </div>
        </div>

    </div>
    <div class="card-footer">
        <button type="submit" name="button" value="<?= $this->lang->line('gvv_button_validate') ?>" class="btn btn-primary">
            <i class="fas fa-save"></i> <?= $this->lang->line('gvv_button_validate') ?>
        </button>
        <button type="submit" name="button" value="<?= $this->lang->line('gvv_button_cancel') ?>" class="btn btn-secondary">
            <i class="fas fa-times"></i> <?= $this->lang->line('gvv_button_cancel') ?>
        </button>
        <?php if ($action == MODIFICATION): ?>
        <a href="<?= site_url('acceptance_admin/tracking/' . $id) ?>" class="btn btn-info">
            <i class="fas fa-chart-bar"></i> <?= $this->lang->line('acceptance_tracking') ?>
        </a>
        <?php endif; ?>
    </div>
</div>

<?= form_close() ?>

</div>

<script>
// Show/hide dual validation roles based on checkbox
document.addEventListener('DOMContentLoaded', function() {
    var dualCheckbox = document.getElementById('dual_validation');
    var role1Row = document.getElementById('role1_row');
    var role2Row = document.getElementById('role2_row');

    function toggleRoles() {
        var display = dualCheckbox.checked ? '' : 'none';
        role1Row.style.display = display;
        role2Row.style.display = display;
    }

    dualCheckbox.addEventListener('change', toggleRoles);
    toggleRoles(); // Initial state
});
</script>
