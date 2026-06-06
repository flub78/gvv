<?php $this->lang->load('forms'); ?>
<div class="container mt-4">
    <?php $form = isset($form) ? $form : array('id' => 0, 'title' => '', 'code' => ''); ?>
    <?php $pages = isset($pages) ? $pages : array(); ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1"><?= $this->lang->line('forms_title_pages') ?></h1>
            <p class="text-muted mb-0">
                <?= html_escape(isset($form['title']) ? $form['title'] : '') ?>
                (<?= html_escape(isset($form['code']) ? $form['code'] : '') ?>)
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/edit/' . (int) $form['id']) ?>"><?= $this->lang->line('forms_button_back_form') ?></a>
            <a class="btn btn-primary" href="<?= site_url('forms_admin/page_create/' . (int) $form['id']) ?>"><?= $this->lang->line('forms_button_new_page') ?></a>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= $this->lang->line('forms_label_number') ?></th>
                            <th><?= $this->lang->line('forms_label_title') ?></th>
                            <th><?= $this->lang->line('forms_label_preview') ?></th>
                            <th class="text-end"><?= $this->lang->line('forms_label_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4"><?= $this->lang->line('forms_empty_no_pages') ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td><?= (int) $page['page_number'] ?></td>
                                    <td><?= html_escape((string) $page['title']) ?></td>
                                    <td class="text-muted"><?= html_escape(mb_substr(trim(strip_tags((string) $page['content_html'])), 0, 120)) ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('forms_admin/page_edit/' . (int) $form['id'] . '/' . (int) $page['id']) ?>"><?= $this->lang->line('forms_button_edit') ?></a>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']) ?>"><?= $this->lang->line('forms_button_fields') ?></a>
                                        <form method="post" action="<?= site_url('forms_admin/page_delete/' . (int) $form['id'] . '/' . (int) $page['id']) ?>" class="d-inline" onsubmit="return confirm('<?= $this->lang->line('forms_confirm_delete_page') ?>');">
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
