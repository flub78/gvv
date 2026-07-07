<?php
    $this->lang->load('forms');
    $this->lang->load('archived_documents');
?>
<div class="container mt-4">
    <?php
        $form = isset($form) ? $form : array('id' => 0, 'title' => '', 'code' => '');
        $submissions = isset($submissions) ? $submissions : array();
        $upload_files = isset($upload_files) ? $upload_files : array();
        $public_slug = trim((string) ($form['public_slug'] ?? ''));
        $can_fill_form = ($public_slug !== '');
        $required_params = (string) ($form['required_params'] ?? 'none');
        $requires_generate = in_array($required_params, array('pilot', 'instructor', 'pilot+instructor'), true);
        $fill_label = $requires_generate ? $this->lang->line('forms_button_generate') : $this->lang->line('forms_generate_button');
        $fill_url = $requires_generate
            ? site_url('forms_admin/generate/' . rawurlencode($public_slug))
            : site_url('forms/' . rawurlencode($public_slug));
        $allow_upload_response = !empty($form['allow_upload_response']);
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1"><?= $this->lang->line('forms_title_submissions') ?></h1>
            <p class="text-muted mb-0"><?= html_escape($form['title']) ?> (<?= html_escape($form['code']) ?>)</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($can_fill_form): ?>
                <a class="btn btn-primary" href="<?= $fill_url ?>"><?= $fill_label ?></a>
            <?php endif; ?>
            <?php if ($allow_upload_response && $public_slug !== ''): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadResponseModal">
                    <?= $this->lang->line('forms_button_upload_response') ?>
                </button>
            <?php endif; ?>
            <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/edit/' . (int) $form['id']) ?>"><?= $this->lang->line('forms_button_back_form') ?></a>
        </div>
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
                <table class="table table-hover mb-0 datatable" id="dt-submissions">
                    <thead>
                        <tr>
                            <th><?= $this->lang->line('forms_label_id') ?></th>
                            <th><?= $this->lang->line('forms_label_identifier') ?></th>
                            <th><?= $this->lang->line('forms_label_submitted_by') ?></th>
                            <th><?= $this->lang->line('forms_label_date') ?></th>
                            <th class="text-end"><?= $this->lang->line('forms_label_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($submissions)): ?>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td><?= (int) $submission['id'] ?></td>
                                    <td>
                                        <?php $ident = trim((string) ($submission['response_identifier'] ?? '')); ?>
                                        <?= $ident !== '' ? html_escape($ident) : '<span class="text-muted">—</span>' ?>
                                    </td>
                                    <td>
                                        <?php
                                            $name = trim((string) $submission['submitter_name']);
                                            $email = trim((string) $submission['submitter_email']);
                                        ?>
                                        <?php if ($name !== '' || $email !== ''): ?>
                                            <?= html_escape($name !== '' ? $name : $email) ?>
                                            <?php if ($name !== '' && $email !== ''): ?>
                                                <br><span class="text-muted"><?= html_escape($email) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?= $this->lang->line('forms_label_anonymous') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= html_escape((string) $submission['submitted_at']) ?></td>
                                    <td class="text-end">
                                        <?php
                                            $is_upload = ($submission['submission_method'] ?? 'online') === 'upload';
                                            $upload_file = $is_upload && isset($upload_files[(int) $submission['id']]) ? $upload_files[(int) $submission['id']] : null;
                                        ?>
                                        <?php if ($is_upload && $upload_file): ?>
                                            <?php $preview_url = site_url('forms_admin/submission_file/' . (int) $form['id'] . '/' . (int) $submission['id'] . '/' . (int) $upload_file['id']) . '?inline=1'; ?>
                                            <span class="d-inline-block align-middle me-1" title="<?= html_escape((string) $upload_file['original_name']) ?>">
                                                <?= attachment((int) $upload_file['id'], './' . $upload_file['storage_path'], $preview_url) ?>
                                            </span>
                                            <a class="btn btn-sm btn-outline-secondary me-1"
                                               href="<?= site_url('forms_admin/submission_rotate/' . (int) $form['id'] . '/' . (int) $submission['id'] . '/ccw') ?>"
                                               onclick="return confirm('<?= $this->lang->line('archived_documents_rotate_ccw') ?> ?');"
                                               title="<?= $this->lang->line('archived_documents_rotate_ccw') ?>">
                                                <i class="fas fa-undo"></i>
                                            </a>
                                            <a class="btn btn-sm btn-outline-secondary me-1"
                                               href="<?= site_url('forms_admin/submission_rotate/' . (int) $form['id'] . '/' . (int) $submission['id'] . '/cw') ?>"
                                               onclick="return confirm('<?= $this->lang->line('archived_documents_rotate_cw') ?> ?');"
                                               title="<?= $this->lang->line('archived_documents_rotate_cw') ?>">
                                                <i class="fas fa-redo"></i>
                                            </a>
                                        <?php elseif ($is_upload): ?>
                                            <span class="text-muted small me-1"><?= $this->lang->line('forms_empty_no_files') ?></span>
                                        <?php else: ?>
                                            <a class="btn btn-sm btn-outline-primary me-1" href="<?= site_url('forms_admin/submission/' . (int) $form['id'] . '/' . (int) $submission['id']) ?>"><?= $this->lang->line('forms_button_open') ?></a>
                                            <a class="btn btn-sm btn-outline-secondary me-1" href="<?= site_url('forms_admin/submission_pdf/' . (int) $form['id'] . '/' . (int) $submission['id']) ?>"><?= $this->lang->line('forms_button_pdf') ?></a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-submission"
                                            data-submission-id="<?= (int) $submission['id'] ?>"
                                            data-form-id="<?= (int) $form['id'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#deleteSubmissionModal">
                                            <?= $this->lang->line('forms_button_delete') ?>
                                        </button>
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

<?php if ($allow_upload_response && $public_slug !== ''): ?>
<div class="modal fade" id="uploadResponseModal" tabindex="-1" aria-labelledby="uploadResponseModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data"
            action="<?= site_url('forms/upload/' . rawurlencode($public_slug)) ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="uploadResponseModalLabel"><?= $this->lang->line('forms_upload_modal_title') ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="drop-zone" id="drop-zone-upload_response_file">
              <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
              <p class="mb-1"><?= $this->lang->line('gvv_drop_file_here') ?></p>
              <p class="text-muted small"><?= $this->lang->line('gvv_or') ?></p>
              <label for="upload_response_file" class="btn btn-outline-secondary btn-sm">
                  <i class="fas fa-folder-open"></i> <?= $this->lang->line('gvv_choose_file') ?>
              </label>
              <input type="file" name="upload_response_file" id="upload_response_file" class="d-none" required
                     accept=".pdf,.jpg,.jpeg,.png,.gif,.webp">
              <p class="mt-2 small text-muted" id="filename-upload_response_file"><?= $this->lang->line('gvv_no_file_selected') ?></p>
          </div>
          <div class="mb-3 mt-3">
              <label for="upload_comment" class="form-label"><?= $this->lang->line('forms_upload_modal_comment_label') ?></label>
              <textarea class="form-control" name="upload_comment" id="upload_comment" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $this->lang->line('forms_button_cancel') ?></button>
          <button type="submit" class="btn btn-success"><?= $this->lang->line('forms_upload_modal_submit') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<style>
.drop-zone { border: 2px dashed #ccc; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: border-color 0.2s, background-color 0.2s; background: #fafafa; }
.drop-zone.drag-over { border-color: #0d6efd; background-color: #e8f0fe; }
.drop-zone.has-file { border-color: #198754; background-color: #f0fff4; }
</style>
<script>
(function () {
    var input = document.getElementById('upload_response_file');
    if (!input) return;
    var zone = input.closest('.drop-zone');
    var label = document.getElementById('filename-upload_response_file');

    function updateFilename(files) {
        if (files && files.length > 0) {
            label.textContent = files[0].name;
            zone.classList.add('has-file');
        }
    }

    zone.addEventListener('click', function (e) {
        if (e.target.tagName !== 'LABEL' && e.target.tagName !== 'INPUT') {
            input.click();
        }
    });
    input.addEventListener('change', function () { updateFilename(this.files); });
    zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', function () { zone.classList.remove('drag-over'); });
    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('drag-over');
        var dt = e.dataTransfer;
        if (dt.files.length > 0) {
            input.files = dt.files;
            updateFilename(dt.files);
        }
    });
})();
</script>
<?php endif; ?>

<div class="modal fade" id="deleteSubmissionModal" tabindex="-1" aria-labelledby="deleteSubmissionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteSubmissionModalLabel"><?= $this->lang->line('forms_modal_title_delete') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $this->lang->line('forms_button_close') ?>"></button>
            </div>
            <div class="modal-body">
                <?= $this->lang->line('forms_modal_confirm_delete') ?> <strong>#<span id="deleteSubmissionId"></span></strong> ?
                <?= $this->lang->line('forms_modal_help_delete') ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $this->lang->line('forms_button_cancel') ?></button>
                <form id="deleteSubmissionForm" method="post" action="">
                    <button type="submit" class="btn btn-danger"><?= $this->lang->line('forms_button_delete') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-delete-submission').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var submissionId = this.dataset.submissionId;
        var formId = this.dataset.formId;
        document.getElementById('deleteSubmissionId').textContent = submissionId;
        document.getElementById('deleteSubmissionForm').action =
            '<?= site_url('forms_admin/submission_delete') ?>/' + formId + '/' + submissionId;
    });
});
</script>
