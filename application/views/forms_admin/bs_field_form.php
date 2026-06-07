<?php $this->lang->load('forms'); ?>
<div class="container mt-4 mb-5">
    <?php
        $form         = isset($form)         ? $form         : array('id' => 0, 'title' => '', 'code' => '');
        $page         = isset($page)         ? $page         : array('id' => 0, 'page_number' => 1, 'title' => '');
        $field        = isset($field)        ? $field        : array();
        $field_mode   = isset($field_mode)   ? $field_mode   : 'create';
        $form_action  = isset($form_action)  ? $form_action  : '';
        $submit_label = isset($submit_label) ? $submit_label : $this->lang->line('forms_button_save');
        $error        = isset($error)        ? $error        : '';

        $v_name         = isset($field['name'])         ? $field['name']         : '';
        $v_label        = isset($field['label'])        ? $field['label']        : '';
        $v_field_type   = isset($field['field_type'])   ? $field['field_type']   : 'text';
        $v_is_required   = !empty($field['is_required']);
        $v_is_identifier = !empty($field['is_identifier']);
        $v_sort_order   = isset($field['sort_order'])   ? $field['sort_order']   : '';
        $v_options_text = isset($field['options_text']) ? $field['options_text'] : '';

        $types_with_options = array('select', 'radio', 'checkbox');
        $field_types = array(
            'text'     => $this->lang->line('forms_type_text'),
            'email'    => $this->lang->line('forms_type_email'),
            'date'     => $this->lang->line('forms_type_date'),
            'number'   => $this->lang->line('forms_type_number'),
            'textarea' => $this->lang->line('forms_type_textarea'),
            'select'   => $this->lang->line('forms_type_select'),
            'radio'    => $this->lang->line('forms_type_radio'),
            'checkbox' => $this->lang->line('forms_type_checkbox'),
            'file'     => $this->lang->line('forms_type_file'),
        );
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1"><?= $field_mode === 'create' ? $this->lang->line('forms_title_add_field') : $this->lang->line('forms_title_edit_field') ?></h1>
            <p class="text-muted mb-0">
                <?= html_escape($form['title']) ?> (<?= html_escape($form['code']) ?>)
                — <?= $this->lang->line('forms_label_page') ?> <?= (int) $page['page_number'] ?>
                <?php if (!empty($page['title'])): ?>(<?= html_escape($page['title']) ?>)<?php endif; ?>
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']) ?>"><?= $this->lang->line('forms_button_back_fields') ?></a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= $form_action ?>">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="label"><?= $this->lang->line('forms_label_label') ?> <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="label" name="label" maxlength="255" required
                               value="<?= html_escape($v_label) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="name"><?= $this->lang->line('forms_label_technical_name') ?> <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="name" name="name" maxlength="100" required
                               pattern="[a-zA-Z0-9_\-]+" value="<?= html_escape($v_name) ?>">
                        <div class="form-text"><?= $this->lang->line('forms_help_technical_name') ?></div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="field_type"><?= $this->lang->line('forms_label_type') ?> <span class="text-danger">*</span></label>
                        <select class="form-select" id="field_type" name="field_type" required onchange="toggleOptions(this.value)">
                            <?php foreach ($field_types as $type_key => $type_label): ?>
                                <option value="<?= $type_key ?>" <?= $v_field_type === $type_key ? 'selected' : '' ?>><?= $type_label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="sort_order"><?= $this->lang->line('forms_label_display_order') ?></label>
                        <input class="form-control" type="number" id="sort_order" name="sort_order" min="1"
                               value="<?= html_escape((string) $v_sort_order) ?>">
                        <div class="form-text"><?= $this->lang->line('forms_help_display_order') ?></div>
                    </div>
                    <div class="col-md-4 d-flex flex-column gap-2 pt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_required" name="is_required" value="1" <?= $v_is_required ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_required"><?= $this->lang->line('forms_checkbox_required') ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_identifier" name="is_identifier" value="1" <?= $v_is_identifier ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_identifier"><?= $this->lang->line('forms_checkbox_identifier') ?></label>
                            <div class="form-text"><?= $this->lang->line('forms_help_identifier') ?></div>
                        </div>
                    </div>
                </div>

                <div class="mb-3" id="options_block" style="<?= in_array($v_field_type, $types_with_options) ? '' : 'display:none' ?>">
                    <label class="form-label" for="options_text"><?= $this->lang->line('forms_label_options') ?> <span class="text-muted"><?= $this->lang->line('forms_help_options_format') ?></span></label>
                    <textarea class="form-control" id="options_text" name="options_text" rows="6"><?= html_escape($v_options_text) ?></textarea>
                    <div class="form-text"><?= $this->lang->line('forms_help_options_usage') ?></div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= html_escape($submit_label) ?></button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']) ?>"><?= $this->lang->line('forms_button_cancel') ?></a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var typesWithOptions = <?= json_encode($types_with_options) ?>;

function toggleOptions(type) {
    var block = document.getElementById('options_block');
    block.style.display = typesWithOptions.indexOf(type) !== -1 ? '' : 'none';
}

document.getElementById('label').addEventListener('input', function() {
    var nameField = document.getElementById('name');
    if (nameField.dataset.manual) return;
    nameField.value = this.value
        .toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
});

document.getElementById('name').addEventListener('input', function() {
    this.dataset.manual = '1';
});
</script>
