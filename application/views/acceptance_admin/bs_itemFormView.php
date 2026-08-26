<!-- VIEW: application/views/acceptance_admin/bs_itemFormView.php -->
<?php
/**
 * Form view for creating/editing an acceptance item
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('acceptance');
$this->lang->load('archived_documents');
$this->lang->load('email_lists');

$archived_document_id = isset($archived_document_id) ? $archived_document_id : '';
$archived_document = isset($archived_document) ? $archived_document : null;
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

        <!-- Description -->
        <div class="mb-3 row">
            <label for="description" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_description') ?>
            </label>
            <div class="col-sm-10">
                <?= form_textarea('description', set_value('description', isset($description) ? $description : ''), 'class="form-control" id="description" rows="3"') ?>
            </div>
        </div>

        <!-- Category -->
        <div class="mb-3 row">
            <label for="category" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_category') ?> <span class="text-danger">*</span>
            </label>
            <div class="col-sm-10">
                <?php if (!empty($archived_document_id) || $action == CREATION): ?>
                    <?= form_hidden('category', 'document') ?>
                    <input type="text" class="form-control" value="<?= $this->lang->line('acceptance_category_document') ?>" disabled>
                <?php else: ?>
                    <?= form_dropdown('category', $category_options, set_value('category', isset($category) ? $category : ''), 'class="form-select" id="category" required') ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($archived_document_id)): ?>
        <!-- Linked archived document (read-only, replaces the PDF upload) -->
        <?= form_hidden('archived_document_id', $archived_document_id) ?>
        <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_archived_document') ?>
            </label>
            <div class="col-sm-10">
                <div class="alert alert-secondary d-flex align-items-center gap-2 mb-1">
                    <i class="fas fa-file-pdf text-danger"></i>
                    <span>
                        <?= htmlspecialchars($archived_document['original_filename'] ?? '') ?>
                        <?php if (!empty($archived_document['description'])): ?>
                            &mdash; <?= htmlspecialchars($archived_document['description']) ?>
                        <?php endif; ?>
                    </span>
                    <a href="<?= site_url('archived_documents/view/' . $archived_document_id) ?>" class="btn btn-sm btn-outline-secondary ms-auto" target="_blank">
                        <i class="fas fa-eye"></i> <?= $this->lang->line('archived_documents_view') ?>
                    </a>
                </div>
                <small class="text-muted"><?= $this->lang->line('acceptance_archived_document_help') ?></small>
            </div>
        </div>
        <?php elseif ($action == MODIFICATION): ?>
        <!-- PDF file upload — legacy items only (created before the Lot 4 amendment
             restricting new items to a linked archived document) -->
        <div class="mb-3 row">
            <label for="pdf_file" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_pdf_path') ?>
            </label>
            <div class="col-sm-10">
                <?php if (!empty($pdf_path)): ?>
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
        <?php endif; ?>

        <!-- Version date -->
        <div class="mb-3 row">
            <label for="version_date" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_version_date') ?>
            </label>
            <div class="col-sm-10">
                <?php if (!empty($archived_document_id)): ?>
                    <?= form_hidden('version_date', isset($version_date) ? $version_date : '') ?>
                    <input type="text" class="form-control" value="<?= isset($version_date) ? htmlspecialchars($version_date) : '' ?>" disabled>
                    <small class="text-muted"><?= $this->lang->line('acceptance_version_date_archived_help') ?></small>
                <?php else: ?>
                    <?= form_input('version_date', set_value('version_date', isset($version_date) ? $version_date : ''), 'class="form-control datepicker" id="version_date" placeholder="jj/mm/aaaa"') ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mandatory level -->
        <div class="mb-3 row">
            <label for="mandatory_level" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_mandatory') ?>
            </label>
            <div class="col-sm-10">
                <?php
                    $mandatory_level_options = array(
                        'optional' => $this->lang->line('acceptance_mandatory_optional'),
                        'mandatory_soft' => $this->lang->line('acceptance_mandatory_soft'),
                        'mandatory_hard' => $this->lang->line('acceptance_mandatory_hard')
                    );
                ?>
                <?= form_dropdown('mandatory_level', $mandatory_level_options, set_value('mandatory_level', isset($mandatory_level) ? $mandatory_level : 'optional'), 'class="form-select" id="mandatory_level"') ?>
                <small class="text-muted"><?= $this->lang->line('acceptance_mandatory_help') ?></small>
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

        <!-- Targeting: individual user or categories, exclusive -->
        <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_target_mode') ?>
            </label>
            <div class="col-sm-10">
                <?php $current_target_mode = isset($target_mode) ? $target_mode : 'roles'; ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="target_mode" id="target_mode_roles" value="roles"
                        <?= ($current_target_mode !== 'user') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="target_mode_roles"><?= $this->lang->line('acceptance_target_mode_roles') ?></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="target_mode" id="target_mode_user" value="user"
                        <?= ($current_target_mode === 'user') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="target_mode_user"><?= $this->lang->line('acceptance_target_mode_user') ?></label>
                </div>
                <small class="text-muted d-block"><?= $this->lang->line('acceptance_target_user_help') ?></small>
            </div>
        </div>

        <!-- Target roles (shown when targeting by categories): role x section
             grid, same layout/value convention as email_lists/_criteria_tab.php
             ("role_id_section_id", "role_id_0" for all sections). -->
        <div class="mb-3 row" id="target_roles_row">
            <label class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_target_roles') ?>
            </label>
            <div class="col-sm-10">
                <?php
                    $checked_roles = isset($checked_roles) ? $checked_roles : array();
                    $available_roles = isset($available_roles) ? $available_roles : array();
                    $available_sections = isset($available_sections) ? $available_sections : array();
                    $global_roles = array();
                    $section_roles = array();
                    foreach ($available_roles as $role) {
                        if ($role['scope'] === 'global') {
                            $global_roles[] = $role;
                        } else {
                            $section_roles[] = $role;
                        }
                    }
                    $ordered_roles = array_merge($global_roles, $section_roles);
                ?>
                <?php if (empty($ordered_roles)): ?>
                    <div class="alert alert-warning mb-1"><?= $this->lang->line('email_lists_no_roles_available') ?></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-1">
                            <thead>
                                <tr>
                                    <th><?= $this->lang->line('authorization_role') ?></th>
                                    <th>Global</th>
                                    <th>Toutes sections</th>
                                    <?php foreach ($available_sections as $section): ?>
                                        <th style="background-color: <?= htmlspecialchars($section['couleur'] ?? '#e9ecef') ?>; color: black;">
                                            <?= htmlspecialchars($section['nom']) ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordered_roles as $role): ?>
                                <tr>
                                    <td>
                                        <?php if ($role['scope'] === 'global'): ?><strong><?php endif; ?>
                                        <?php
                                            if (!empty($role['translation_key']) && $this->lang->line($role['translation_key'])) {
                                                echo htmlspecialchars($this->lang->line($role['translation_key']));
                                            } else {
                                                echo htmlspecialchars($role['nom']);
                                            }
                                        ?>
                                        <?php if ($role['scope'] === 'global'): ?></strong><?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($role['scope'] === 'global'): ?>
                                            <input type="checkbox" class="form-check-input" name="roles[]"
                                                value="<?= $role['id'] ?>_0"
                                                <?= isset($checked_roles[$role['id'] . '_0']) ? 'checked' : '' ?>>
                                        <?php else: ?>
                                            <span class="text-muted small">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($role['scope'] === 'section'): ?>
                                            <input type="checkbox" class="form-check-input check-all-sections" data-role-id="<?= $role['id'] ?>">
                                        <?php else: ?>
                                            <span class="text-muted small">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php foreach ($available_sections as $section): ?>
                                        <td class="text-center">
                                            <?php if ($role['scope'] === 'section'): ?>
                                                <?php $key = $role['id'] . '_' . $section['id']; ?>
                                                <input type="checkbox" class="form-check-input section-checkbox" name="roles[]"
                                                    value="<?= $key ?>" data-role-id="<?= $role['id'] ?>"
                                                    <?= isset($checked_roles[$key]) ? 'checked' : '' ?>>
                                            <?php else: ?>
                                                <span class="text-muted small">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <small class="text-muted"><?= $this->lang->line('acceptance_target_roles_help') ?></small>
            </div>
        </div>

        <!-- Target user (shown when targeting an individual user) -->
        <div class="mb-3 row" id="target_user_row">
            <label for="target_user_login" class="col-sm-2 col-form-label">
                <?= $this->lang->line('acceptance_target_user_login') ?>
            </label>
            <div class="col-sm-10">
                <?= form_dropdown('target_user_login', $member_selector, set_value('target_user_login', isset($target_user_login) ? $target_user_login : ''), 'class="form-select" id="target_user_login"') ?>
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
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide targeting fields based on target_mode radio (exclusive: user or categories)
    var targetModeRadios = document.getElementsByName('target_mode');
    var targetRolesRow = document.getElementById('target_roles_row');
    var targetUserRow = document.getElementById('target_user_row');

    function toggleTargetMode() {
        var isUser = document.getElementById('target_mode_user').checked;
        targetRolesRow.style.display = isUser ? 'none' : '';
        targetUserRow.style.display = isUser ? '' : 'none';
    }

    targetModeRadios.forEach(function (radio) {
        radio.addEventListener('change', toggleTargetMode);
    });
    toggleTargetMode(); // Initial state

    // "Toutes sections" checkbox checks/unchecks every section box for that role
    // (same behavior as email_lists/_criteria_tab.php).
    document.querySelectorAll('.check-all-sections').forEach(function(checkAllBox) {
        checkAllBox.addEventListener('change', function() {
            var roleId = this.dataset.roleId;
            var isChecked = this.checked;
            document.querySelectorAll('.section-checkbox[data-role-id="' + roleId + '"]').forEach(function(sectionBox) {
                sectionBox.checked = isChecked;
            });
        });
    });

    document.querySelectorAll('.section-checkbox').forEach(function(sectionBox) {
        sectionBox.addEventListener('change', function() {
            var roleId = this.dataset.roleId;
            var allSectionBoxes = document.querySelectorAll('.section-checkbox[data-role-id="' + roleId + '"]');
            var checkAllBox = document.querySelector('.check-all-sections[data-role-id="' + roleId + '"]');
            if (checkAllBox) {
                var allChecked = true;
                allSectionBoxes.forEach(function(box) {
                    if (!box.checked) {
                        allChecked = false;
                    }
                });
                checkAllBox.checked = allChecked;
            }
        });
    });
});
</script>
