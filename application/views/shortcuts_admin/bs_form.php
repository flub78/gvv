<?php $this->lang->load('shortcuts'); ?>
<div class="container mt-4">
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= ($form_mode === 'edit') ? $this->lang->line('shortcuts_button_edit') : $this->lang->line('shortcuts_button_new') ?></h1>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= html_escape($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= html_escape($form_action) ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold" for="dashboard"><?= $this->lang->line('shortcuts_label_dashboard') ?> <span class="text-danger">*</span></label>
                        <select class="form-select" id="dashboard" name="dashboard" required>
                            <?php foreach ($allowed_dashboards as $d): ?>
                                <option value="<?= html_escape($d) ?>" <?= ($shortcut['dashboard'] === $d) ? 'selected' : '' ?>><?= html_escape($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="section"><?= $this->lang->line('shortcuts_label_section') ?></label>
                        <input class="form-control" id="section" name="section" type="text" maxlength="100"
                               value="<?= html_escape($shortcut['section'] ?? '') ?>">
                        <div class="form-text"><?= $this->lang->line('shortcuts_help_section') ?></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold" for="title"><?= $this->lang->line('shortcuts_label_title') ?> <span class="text-danger">*</span></label>
                        <input class="form-control" id="title" name="title" type="text" maxlength="100" required
                               value="<?= html_escape($shortcut['title'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="title_key"><?= $this->lang->line('shortcuts_label_title_key') ?></label>
                        <input class="form-control font-monospace" id="title_key" name="title_key" type="text" maxlength="100"
                               value="<?= html_escape($shortcut['title_key'] ?? '') ?>">
                        <div class="form-text"><?= $this->lang->line('shortcuts_help_lang_key') ?></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="description"><?= $this->lang->line('shortcuts_label_description') ?></label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?= html_escape($shortcut['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="description_key"><?= $this->lang->line('shortcuts_label_description_key') ?></label>
                        <input class="form-control font-monospace" id="description_key" name="description_key" type="text" maxlength="255"
                               value="<?= html_escape($shortcut['description_key'] ?? '') ?>">
                        <div class="form-text"><?= $this->lang->line('shortcuts_help_lang_key') ?></div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="url"><?= $this->lang->line('shortcuts_label_url') ?> <span class="text-danger">*</span></label>
                    <input class="form-control" id="url" name="url" type="text" maxlength="255" required
                           value="<?= html_escape($shortcut['url'] ?? '') ?>">
                    <div class="form-text"><?= $this->lang->line('shortcuts_help_url') ?></div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="icon"><?= $this->lang->line('shortcuts_label_icon') ?></label>
                        <input class="form-control font-monospace" id="icon" name="icon" type="text" maxlength="50"
                               placeholder="fa-file-signature"
                               value="<?= html_escape($shortcut['icon'] ?? '') ?>">
                        <div class="form-text"><?= $this->lang->line('shortcuts_help_icon') ?></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="color"><?= $this->lang->line('shortcuts_label_color') ?></label>
                        <input class="form-control font-monospace" id="color" name="color" type="text" maxlength="20"
                               placeholder="text-primary"
                               value="<?= html_escape($shortcut['color'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="sort_order"><?= $this->lang->line('shortcuts_label_sort_order') ?></label>
                        <input class="form-control" id="sort_order" name="sort_order" type="number" step="1"
                               value="<?= html_escape((string) ($shortcut['sort_order'] ?? 0)) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="role_required"><?= $this->lang->line('shortcuts_label_role') ?></label>
                    <select class="form-select" id="role_required" name="role_required">
                        <option value=""><?= $this->lang->line('shortcuts_scope_all_roles') ?></option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= html_escape($r) ?>" <?= (($shortcut['role_required'] ?? '') === $r) ? 'selected' : '' ?>><?= html_escape($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <input type="checkbox" class="form-check-input" name="active" value="1"
                               <?= !empty($shortcut['active']) ? 'checked' : '' ?>>
                        <?= $this->lang->line('shortcuts_label_active') ?>
                    </label>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="club_id"><?= $this->lang->line('shortcuts_label_scope') ?></label>
                    <?php
                    $club_current    = isset($shortcut['club_id']) && $shortcut['club_id'] !== null ? $shortcut['club_id'] : '';
                    $section_options = isset($section_selector) ? $section_selector : array();
                    ?>
                    <select class="form-select" id="club_id" name="club_id" style="max-width:320px;">
                        <?php foreach ($section_options as $val => $label): ?>
                            <option value="<?= html_escape($val) ?>" <?= ((string) $club_current === (string) $val) ? 'selected' : '' ?>><?= html_escape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><?= $this->lang->line('shortcuts_button_save') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('shortcuts_admin') ?>"><?= $this->lang->line('shortcuts_button_cancel') ?></a>
                </div>
            </form>
        </div>
    </div>
</div>
