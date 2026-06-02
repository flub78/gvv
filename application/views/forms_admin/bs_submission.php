<?php $this->lang->load('forms'); ?>
<div class="container mt-4 mb-5">
    <?php
        $form = isset($form) ? $form : array('id' => 0, 'title' => '', 'code' => '');
        $submission = isset($submission) ? $submission : array('id' => 0, 'submission_uuid' => '', 'status' => '', 'submitted_at' => '');
        $values = isset($values) ? $values : array();
        $files = isset($files) ? $files : array();

        // Build a map of files indexed by field_id for inline signature display
        $files_by_field_id = array();
        foreach ($files as $f) {
            if (!empty($f['field_id'])) {
                $files_by_field_id[(int) $f['field_id']] = $f;
            }
        }
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1"><?= $this->lang->line('forms_title_submission_detail') ?></h1>
            <p class="text-muted mb-0">
                <?= html_escape($form['title']) ?> (<?= html_escape($form['code']) ?>)
                - <?= $this->lang->line('forms_section_submission') ?><?= (int) $submission['id'] ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="<?= site_url('forms_admin/submission_view/' . (int) $form['id'] . '/' . (int) $submission['id']) ?>" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer me-1" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                <?= $this->lang->line('forms_button_pdf') ?>
            </a>
            <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/submissions/' . (int) $form['id']) ?>"><?= $this->lang->line('forms_button_back_submissions') ?></a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong><?= $this->lang->line('forms_label_uuid') ?>:</strong> <code><?= html_escape((string) $submission['submission_uuid']) ?></code></div>
                <div class="col-md-4"><strong><?= $this->lang->line('forms_label_status') ?>:</strong> <?= html_escape((string) $submission['status']) ?></div>
                <div class="col-md-4"><strong><?= $this->lang->line('forms_label_date') ?>:</strong> <?= html_escape((string) $submission['submitted_at']) ?></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header"><strong><?= $this->lang->line('forms_section_submitted_values') ?></strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th><?= $this->lang->line('forms_label_field') ?></th>
                            <th><?= $this->lang->line('forms_label_type') ?></th>
                            <th><?= $this->lang->line('forms_label_value') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($values)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3"><?= $this->lang->line('forms_empty_no_values') ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($values as $value): ?>
                                <?php
                                    $is_signature = (isset($value['field_type']) && $value['field_type'] === 'signature');
                                    $sig_file = ($is_signature && !empty($value['field_id'])) ? (isset($files_by_field_id[(int) $value['field_id']]) ? $files_by_field_id[(int) $value['field_id']] : null) : null;
                                ?>
                                <tr>
                                    <td><?= html_escape((string) $value['field_label']) ?></td>
                                    <td><?= html_escape((string) $value['field_type']) ?></td>
                                    <td>
                                        <?php if ($is_signature && $sig_file !== null): ?>
                                            <?php $sig_url = site_url('forms_admin/submission_file/' . (int) $form['id'] . '/' . (int) $submission['id'] . '/' . (int) $sig_file['id']) . '?inline=1'; ?>
                                            <img src="<?= $sig_url ?>" alt="Signature" style="max-width:300px;max-height:100px;border:1px solid #dee2e6;border-radius:4px;">
                                        <?php elseif ($is_signature): ?>
                                            <span class="text-muted fst-italic"><?= $this->lang->line('forms_empty_no_files') ?></span>
                                        <?php else: ?>
                                            <pre class="mb-0" style="white-space: pre-wrap;"><?= html_escape((string) $value['value_text']) ?></pre>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header"><strong><?= $this->lang->line('forms_section_uploaded_files') ?></strong></div>
        <div class="card-body">
            <?php if (empty($files)): ?>
                <div class="text-muted"><?= $this->lang->line('forms_empty_no_files') ?></div>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <?php
                        $mime = isset($file['mime_type']) ? (string) $file['mime_type'] : '';
                        $inline_url = site_url('forms_admin/submission_file/' . (int) $form['id'] . '/' . (int) $submission['id'] . '/' . (int) $file['id']) . '?inline=1';
                        $download_url = site_url('forms_admin/submission_file/' . (int) $form['id'] . '/' . (int) $submission['id'] . '/' . (int) $file['id']);
                        $is_image = strpos($mime, 'image/') === 0;
                        $is_pdf = ($mime === 'application/pdf');
                    ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong><?= html_escape((string) $file['original_name']) ?></strong>
                                <div class="text-muted small">
                                    <?= $this->lang->line('forms_label_field') ?>: <?= html_escape((string) $file['field_label']) ?>
                                    <?php if (!empty($file['size_bytes'])): ?>
                                        - <?= (int) $file['size_bytes'] ?> <?= $this->lang->line('forms_unit_bytes') ?>
                                    <?php endif; ?>
                                    <?php if ($mime !== ''): ?>
                                        - <?= html_escape($mime) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= $inline_url ?>" target="_blank"><?= $this->lang->line('forms_button_preview') ?></a>
                                <a class="btn btn-sm btn-outline-primary" href="<?= $download_url ?>"><?= $this->lang->line('forms_button_download') ?></a>
                            </div>
                        </div>

                        <?php if ($is_image): ?>
                            <img src="<?= $inline_url ?>" alt="<?= $this->lang->line('forms_label_preview') ?>" style="max-width: 100%; max-height: 360px;" class="border rounded">
                        <?php elseif ($is_pdf): ?>
                            <iframe src="<?= $inline_url ?>" style="width:100%; height:360px; border:1px solid #dee2e6; border-radius: 0.375rem;"></iframe>
                        <?php else: ?>
                            <div class="text-muted small"><?= $this->lang->line('forms_message_no_preview') ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
