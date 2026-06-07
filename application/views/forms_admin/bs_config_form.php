<?php $this->lang->load('forms'); ?>
<div class="container mt-4">
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= ($form_mode === 'edit') ? $this->lang->line('forms_config_button_edit') : $this->lang->line('forms_config_button_new') ?></h1>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= html_escape($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= html_escape($form_action) ?>">

                <?php if ($form_mode === 'create'): ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="param_key"><?= $this->lang->line('forms_config_label_key') ?> <span class="text-danger">*</span></label>
                    <input class="form-control font-monospace" id="param_key" name="param_key" type="text"
                           maxlength="100" required pattern="[a-zA-Z0-9_]+"
                           value="<?= html_escape($param['param_key']) ?>">
                    <div class="form-text"><?= $this->lang->line('forms_config_help_key') ?></div>
                </div>
                <?php else: ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= $this->lang->line('forms_config_label_key') ?></label>
                    <p class="form-control-plaintext font-monospace"><?= html_escape($param['param_key']) ?></p>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="param_label"><?= $this->lang->line('forms_config_label_label') ?> <span class="text-danger">*</span></label>
                    <input class="form-control" id="param_label" name="param_label" type="text"
                           maxlength="255" required
                           value="<?= html_escape($param['param_label']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="param_value"><?= $this->lang->line('forms_config_label_value') ?></label>
                    <textarea class="form-control" id="param_value" name="param_value" rows="3"><?= html_escape($param['param_value']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="param_description"><?= $this->lang->line('forms_config_label_description') ?></label>
                    <textarea class="form-control" id="param_description" name="param_description" rows="2"><?= html_escape($param['param_description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= $this->lang->line('forms_config_label_scope') ?></label>
                    <?php if ($section_id > 0): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_global" name="is_global" value="1"
                                   <?= empty($param['club_id']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_global"><?= $this->lang->line('forms_config_scope_global') ?></label>
                        </div>
                        <div class="form-text"><?= $this->lang->line('forms_label_section') ?> : <strong><?= html_escape($section_name) ?></strong></div>
                    <?php else: ?>
                        <p class="form-control-plaintext text-muted"><?= $this->lang->line('forms_config_scope_global') ?></p>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><?= $this->lang->line('forms_config_button_save') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/config') ?>"><?= $this->lang->line('forms_config_button_cancel') ?></a>
                </div>
            </form>
        </div>
    </div>
</div>
