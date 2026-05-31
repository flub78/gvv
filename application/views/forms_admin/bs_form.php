<?php $this->lang->load('forms'); ?>
<div class="container mt-4">
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= (isset($form_mode) && $form_mode === 'edit') ? $this->lang->line('forms_title_edit_form') : $this->lang->line('forms_title_new_form') ?></h1>
        <p class="text-muted mb-0"><?= $this->lang->line('forms_subtitle_form_container') ?></p>
        <?php if (isset($form_mode) && $form_mode === 'edit' && !empty($form['id'])): ?>
            <div class="mt-2">
                <a class="btn btn-sm btn-outline-dark" href="<?= site_url('forms_admin/pages/' . (int) $form['id']) ?>"><?= $this->lang->line('forms_button_manage_pages') ?></a>
                <a class="btn btn-sm btn-outline-info" href="<?= site_url('forms_admin/submissions/' . (int) $form['id']) ?>"><?= $this->lang->line('forms_button_view_responses') ?></a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('forms_admin/css_preview/' . (int) $form['id']) ?>" target="_blank"><?= $this->lang->line('forms_button_preview_css') ?></a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= isset($form_action) ? $form_action : site_url('forms_admin/store') ?>">
                <?php if (!empty($section_id) && (int) $section_id > 0): ?>
                    <div class="alert alert-info">
                        <?= $this->lang->line('forms_alert_section_active') ?> <strong><?= (int) $section_id ?></strong>
                        <br>
                        <?= $this->lang->line('forms_alert_global_checkbox') ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary">
                        <?= $this->lang->line('forms_alert_no_section') ?>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label" for="code"><?= $this->lang->line('forms_label_code') ?></label>
                    <input class="form-control" id="code" name="code" type="text" maxlength="50" <?= (isset($form_mode) && $form_mode === 'edit') ? '' : 'required' ?> value="<?= html_escape(isset($form['code']) ? $form['code'] : '') ?>" <?= (isset($form_mode) && $form_mode === 'edit') ? 'readonly' : '' ?>>
                    <div class="form-text"><?= $this->lang->line('forms_help_code') ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="title"><?= $this->lang->line('forms_label_title') ?></label>
                    <input class="form-control" id="title" name="title" type="text" maxlength="255" required value="<?= html_escape(isset($form['title']) ? $form['title'] : '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description"><?= $this->lang->line('forms_label_description') ?></label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?= html_escape(isset($form['description']) ? $form['description'] : '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="public_slug"><?= $this->lang->line('forms_label_public_link') ?></label>
                        <input class="form-control" id="public_slug" name="public_slug" type="text" maxlength="100" value="<?= html_escape(isset($form['public_slug']) ? $form['public_slug'] : '') ?>">
                        <?php if (!empty($form['public_slug'])): ?>
                            <?php $public_url = site_url('forms/' . $form['public_slug']); ?>
                            <div class="mt-1 d-flex align-items-center gap-2">
                                <a href="<?= $public_url ?>" target="_blank" class="form-text text-truncate" style="max-width:260px;"><?= $public_url ?></a>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" title="<?= $this->lang->line('forms_button_copy_link') ?>" onclick="var url='<?= $public_url ?>',btn=this;if(navigator.clipboard){navigator.clipboard.writeText(url).then(function(){btn.innerHTML='&#10003;';setTimeout(function(){btn.innerHTML='&#128203; <?= $this->lang->line('forms_button_copy_link') ?>';},1500);});}else{var t=document.createElement('textarea');t.value=url;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);btn.innerHTML='&#10003;';setTimeout(function(){btn.innerHTML='&#128203; <?= $this->lang->line('forms_button_copy_link') ?>';},1500);}">&#128203; <?= $this->lang->line('forms_button_copy_link') ?></button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="css_scope"><?= $this->lang->line('forms_label_css_scope') ?></label>
                        <input class="form-control" id="css_scope" name="css_scope" type="text" maxlength="100" value="<?= html_escape(isset($form['css_scope']) ? $form['css_scope'] : '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="global_css"><?= $this->lang->line('forms_label_global_css') ?></label>
                    <textarea class="form-control" id="global_css" name="global_css" rows="8" placeholder="<?= $this->lang->line('forms_help_css_placeholder') ?>"><?= html_escape(isset($form['global_css']) ? $form['global_css'] : '') ?></textarea>
                    <div class="form-text"><?= $this->lang->line('forms_help_global_css') ?></div>
                </div>

                <div class="form-check mb-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="is_global"
                        name="is_global"
                        value="1"
                        <?= !empty($form['is_global']) ? 'checked' : '' ?>
                        <?= (!empty($section_id) && (int) $section_id <= 0) ? 'checked disabled' : '' ?>
                    >
                    <label class="form-check-label" for="is_global"><?= $this->lang->line('forms_checkbox_global_form') ?></label>
                </div>

                <?php if (isset($form_mode) && $form_mode === 'edit'): ?>
                    <?php $current_status = isset($form['status']) ? $form['status'] : 'draft'; ?>
                    <?php if ($current_status === 'published'): ?>
                        <div class="alert alert-warning py-2 mb-3">
                            <?= $this->lang->line('forms_alert_published_warning') ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="status"><?= $this->lang->line('forms_label_status') ?></label>
                        <select class="form-select" id="status" name="status" style="max-width:220px;">
                            <option value="draft"     <?= $current_status === 'draft'     ? 'selected' : '' ?>><?= $this->lang->line('forms_status_draft') ?></option>
                            <option value="published" <?= $current_status === 'published' ? 'selected' : '' ?>><?= $this->lang->line('forms_status_published') ?></option>
                            <option value="archived"  <?= $current_status === 'archived'  ? 'selected' : '' ?>><?= $this->lang->line('forms_status_archived') ?></option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= isset($submit_label) ? $submit_label : $this->lang->line('forms_button_create') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin') ?>"><?= $this->lang->line('forms_button_cancel') ?></a>
                </div>
            </form>
        </div>
    </div>
</div>
