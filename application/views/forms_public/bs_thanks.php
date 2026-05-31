<?php $this->lang->load('forms'); ?>
<div class="container mt-5 mb-5">
    <?php
        $form = isset($form) ? $form : array('title' => '', 'public_slug' => '');
        $submission = isset($submission) ? $submission : array('submission_uuid' => '', 'submitted_at' => '');
        $uploaded_file_names = isset($uploaded_file_names) ? $uploaded_file_names : array();
        $uploaded_files_count = isset($uploaded_files_count) ? (int) $uploaded_files_count : 0;
    ?>
    <div class="card shadow-sm">
        <div class="card-body p-4 text-center">
            <h1 class="h3 mb-3"><?= $this->lang->line('forms_title_thank_you') ?></h1>
            <p class="text-muted mb-4">
                <?= sprintf($this->lang->line('forms_message_submitted'), html_escape($form['title'])) ?>
            </p>

            <div class="alert alert-success text-start mx-auto" style="max-width: 720px;">
                <div><strong><?= $this->lang->line('forms_label_reference') ?>:</strong> <code><?= html_escape((string) $submission['submission_uuid']) ?></code></div>
                <?php if (!empty($submission['submitted_at'])): ?>
                    <div><strong><?= $this->lang->line('forms_label_date') ?>:</strong> <?= html_escape((string) $submission['submitted_at']) ?></div>
                <?php endif; ?>
                <div><strong><?= $this->lang->line('forms_label_files_attached') ?>:</strong> <?= $uploaded_files_count ?></div>
            </div>

            <?php if (!empty($uploaded_file_names)): ?>
                <div class="text-start mx-auto mb-4" style="max-width: 720px;">
                    <div class="fw-semibold mb-2"><?= $this->lang->line('forms_section_received_files') ?>:</div>
                    <ul class="mb-0">
                        <?php foreach ($uploaded_file_names as $file_name): ?>
                            <li><?= html_escape((string) $file_name) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
