<?php $this->lang->load('forms'); ?>
<div class="container mt-4">
    <?php
        $section_id = isset($section_id) ? (int) $section_id : 0;
        $submission_counts = isset($submission_counts) ? $submission_counts : array();
        $status_labels = array(
            'draft'     => $this->lang->line('forms_status_draft'),
            'published' => $this->lang->line('forms_status_published'),
            'archived'  => $this->lang->line('forms_status_archived'),
        );
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1"><?= $this->lang->line('forms_title_forms') ?></h1>
            <p class="text-muted mb-0"><?= $this->lang->line('forms_subtitle_admin') ?></p>
        </div>
        <a class="btn btn-primary" href="<?= site_url('forms_admin/create') ?>"><?= $this->lang->line('forms_button_new_form') ?></a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= $this->lang->line('forms_label_code') ?></th>
                            <th><?= $this->lang->line('forms_label_title') ?></th>
                            <th><?= $this->lang->line('forms_label_section') ?></th>
                            <th><?= $this->lang->line('forms_label_status') ?></th>
                            <th><?= $this->lang->line('forms_label_public_link') ?></th>
                            <th class="text-end"><?= $this->lang->line('forms_label_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($forms)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <?= ($section_id > 0) ? $this->lang->line('forms_empty_section') : $this->lang->line('forms_empty_no_forms') ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($forms as $form): ?>
                                <tr>
                                    <td><code><?= html_escape($form['code']) ?></code></td>
                                    <td><?= html_escape($form['title']) ?></td>
                                    <td>
                                        <?= !empty($form['section_name']) ? html_escape($form['section_name']) : '<span class="text-muted">' . $this->lang->line('forms_label_global') . '</span>' ?>
                                    </td>
                                    <td>
                                        <?php $status_label = isset($status_labels[$form['status']]) ? $status_labels[$form['status']] : html_escape($form['status']); ?>
                                        <span class="badge bg-<?= $form['status'] === 'published' ? 'success' : ($form['status'] === 'archived' ? 'secondary' : 'warning text-dark') ?>">
                                            <?= $status_label ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($form['public_slug'])): ?>
                                            <?php $public_url = site_url('forms/' . $form['public_slug']); ?>
                                            <a href="<?= $public_url ?>" target="_blank" class="me-1"><?= html_escape($form['public_slug']) ?></a>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" title="<?= $this->lang->line('forms_button_copy_link') ?>" onclick="var url='<?= $public_url ?>',btn=this;if(navigator.clipboard){navigator.clipboard.writeText(url).then(function(){btn.innerHTML='&#10003;';setTimeout(function(){btn.innerHTML='&#128203;';},1500);});}else{var t=document.createElement('textarea');t.value=url;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);btn.innerHTML='&#10003;';setTimeout(function(){btn.innerHTML='&#128203;';},1500);}" style="font-size:0.8rem;">&#128203;</button>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('forms_admin/edit/' . $form['id']) ?>"><?= $this->lang->line('forms_button_edit') ?></a>
                                        <a class="btn btn-sm btn-outline-dark" href="<?= site_url('forms_admin/pages/' . $form['id']) ?>"><?= $this->lang->line('forms_button_pages') ?></a>
                                        <?php $cnt = isset($submission_counts[$form['id']]) ? (int) $submission_counts[$form['id']] : 0; ?>
                                        <a class="btn btn-sm btn-outline-info" href="<?= site_url('forms_admin/submissions/' . $form['id']) ?>">
                                            <?= $this->lang->line('forms_button_responses') ?><?= $cnt > 0 ? ' <span class="badge bg-info text-dark ms-1">' . $cnt . '</span>' : '' ?>
                                        </a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('forms_admin/duplicate/' . $form['id']) ?>"><?= $this->lang->line('forms_button_duplicate') ?></a>
                                        <?php if ($form['status'] !== 'published'): ?>
                                            <a class="btn btn-sm btn-outline-success" href="<?= site_url('forms_admin/publish/' . $form['id']) ?>"><?= $this->lang->line('forms_button_publish') ?></a>
                                        <?php endif; ?>
                                        <a class="btn btn-sm btn-outline-danger" href="<?= site_url('forms_admin/delete/' . $form['id']) ?>" onclick="return confirm('<?= $this->lang->line('forms_confirm_delete_form') ?>');"><?= $this->lang->line('forms_button_delete') ?></a>
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
