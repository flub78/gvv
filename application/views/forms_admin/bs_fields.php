<?php $this->lang->load('forms'); ?>
<div class="container mt-4 mb-5">
    <?php
        $form   = isset($form)   ? $form   : array('id' => 0, 'title' => '', 'code' => '');
        $page   = isset($page)   ? $page   : array('id' => 0, 'page_number' => 1, 'title' => '');
        $fields = isset($fields) ? $fields : array();
        $field_type_labels = array(
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
            <h1 class="h3 mb-1"><?= $this->lang->line('forms_title_fields') ?> <?= (int) $page['page_number'] ?></h1>
            <p class="text-muted mb-0">
                <?= html_escape($form['title']) ?> (<?= html_escape($form['code']) ?>)
                <?php if (!empty($page['title'])): ?> — <?= html_escape($page['title']) ?><?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/pages/' . (int) $form['id']) ?>"><?= $this->lang->line('forms_button_back_pages') ?></a>
            <a class="btn btn-primary" href="<?= site_url('forms_admin/field_create/' . (int) $form['id'] . '/' . (int) $page['id']) ?>"><?= $this->lang->line('forms_button_add_field') ?></a>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= html_escape($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= html_escape($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px"><?= $this->lang->line('forms_label_order') ?></th>
                            <th><?= $this->lang->line('forms_label_label') ?></th>
                            <th><?= $this->lang->line('forms_label_technical_name') ?></th>
                            <th><?= $this->lang->line('forms_label_type') ?></th>
                            <th style="width:80px"><?= $this->lang->line('forms_label_required') ?></th>
                            <th class="text-end"><?= $this->lang->line('forms_label_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fields)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4"><?= $this->lang->line('forms_empty_no_fields') ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fields as $field): ?>
                                <tr>
                                    <td><?= (int) $field['sort_order'] ?></td>
                                    <td><?= html_escape((string) $field['label']) ?></td>
                                    <td><code><?= html_escape((string) $field['name']) ?></code></td>
                                    <td><?= html_escape($field_type_labels[$field['field_type']] ?? $field['field_type']) ?></td>
                                    <td><?= $field['is_required'] ? '<span class="badge bg-danger">' . $this->lang->line('forms_label_yes') . '</span>' : '<span class="text-muted">' . $this->lang->line('forms_label_no') . '</span>' ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('forms_admin/field_edit/' . (int) $form['id'] . '/' . (int) $page['id'] . '/' . (int) $field['id']) ?>"><?= $this->lang->line('forms_button_edit') ?></a>
                                        <form method="post" action="<?= site_url('forms_admin/field_delete/' . (int) $form['id'] . '/' . (int) $page['id'] . '/' . (int) $field['id']) ?>" class="d-inline" onsubmit="return confirm('<?= $this->lang->line('forms_confirm_delete_field') ?>');">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= $this->lang->line('forms_button_delete') ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
