<?php $this->lang->load('forms'); ?>
<?php if (!empty($has_signature_widget)): ?>
<script src="<?= base_url('assets/js/signature_pad.umd.min.js') ?>"></script>
<?php endif; ?>
<div class="container mt-4 mb-5">
    <?php
        $form = isset($form) ? $form : array('title' => '', 'description' => '', 'public_slug' => '');
        $current_page = isset($current_page) ? $current_page : array('title' => '', 'content_html' => '');
        $current_page_number = isset($current_page_number) ? (int) $current_page_number : 1;
        $page_count = isset($page_count) ? (int) $page_count : 1;
        $css_scope = trim(isset($form['css_scope']) ? (string) $form['css_scope'] : '');
        $scope_class = 'forms-public-root';
        if ($css_scope !== '') {
            $scope_class .= ' ' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $css_scope);
        }

        $raw_html = html_entity_decode(
            isset($current_page['content_html']) ? (string) $current_page['content_html'] : '',
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        // Strip document structure and standalone <form> wrapper from native HTML
        $raw_html = preg_replace('/<\!DOCTYPE[^>]*>/i', '', $raw_html);
        $raw_html = preg_replace('/<html[^>]*>/i', '', $raw_html);
        $raw_html = preg_replace('/<\/html>/i', '', $raw_html);
        $raw_html = preg_replace('/<head\b[^>]*>.*?<\/head>/is', '', $raw_html);
        $raw_html = preg_replace('/<body[^>]*>/i', '', $raw_html);
        $raw_html = preg_replace('/<\/body>/i', '', $raw_html);
        $raw_html = preg_replace('/<form\b[^>]*>/i', '', $raw_html);
        $raw_html = preg_replace('/<\/form>/i', '', $raw_html);
        $raw_html = preg_replace('/<button\b[^>]*\btype=["\']?(submit|reset)["\']?[^>]*>.*?<\/button>/is', '', $raw_html);
        $raw_html = preg_replace('/<input\b[^>]*\btype=["\']?(submit|reset|button)["\']?[^>]*\/?>/i', '', $raw_html);
        $raw_html = trim($raw_html);
    ?>
    <?php if (!empty($form['global_css'])): ?>
        <style>
            <?= str_ireplace('</style>', '<\/style>', (string) $form['global_css']) ?>
        </style>
    <?php endif; ?>

    <div class="mb-4">
        <h1 class="h3 mb-1"><?= html_escape($form['title']) ?></h1>
        <?php if (!empty($form['description'])): ?>
            <p class="text-muted mb-0"><?= nl2br(html_escape($form['description'])) ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body <?= html_escape($scope_class) ?>">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">
                    <?= !empty($current_page['title']) ? html_escape($current_page['title']) : $this->lang->line('forms_label_page') . ' ' . $current_page_number ?>
                </h2>
                <span class="badge bg-secondary"><?= $this->lang->line('forms_label_page') ?> <?= $current_page_number ?> / <?= $page_count ?></span>
            </div>

            <?php
            $pilot_login_v      = isset($pilot_login)      ? (string) $pilot_login      : '';
            $instructor_login_v = isset($instructor_login) ? (string) $instructor_login : '';
            $gvv_qs = '';
            if ($pilot_login_v      !== '') $gvv_qs .= '&pilot_login='      . rawurlencode($pilot_login_v);
            if ($instructor_login_v !== '') $gvv_qs .= '&instructor_login=' . rawurlencode($instructor_login_v);
            ?>
            <form method="post" enctype="multipart/form-data"
                  action="<?= site_url('forms/submit/' . rawurlencode($form['public_slug'])) ?>">
                <input type="hidden" name="page_number" value="<?= $current_page_number ?>">
                <?php if ($pilot_login_v      !== ''): ?>
                <input type="hidden" name="gvv_pilot_login"      value="<?= html_escape($pilot_login_v) ?>">
                <?php endif; ?>
                <?php if ($instructor_login_v !== ''): ?>
                <input type="hidden" name="gvv_instructor_login" value="<?= html_escape($instructor_login_v) ?>">
                <?php endif; ?>

                <?php if ($raw_html !== ''): ?>
                    <?= $raw_html /* HTML composé par un admin — confiance explicite accordée au rôle admin */ ?>
                <?php else: ?>
                    <div class="alert alert-warning"><?= $this->lang->line('forms_alert_no_content') ?></div>
                <?php endif; ?>

                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <?php if ($current_page_number > 1): ?>
                            <a class="btn btn-outline-secondary"
                               href="<?= site_url('forms/' . rawurlencode($form['public_slug'])) ?>?page=<?= $current_page_number - 1 . $gvv_qs ?>">
                                <?= $this->lang->line('forms_button_previous_page') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($current_page_number < $page_count): ?>
                            <a class="btn btn-primary"
                               href="<?= site_url('forms/' . rawurlencode($form['public_slug'])) ?>?page=<?= $current_page_number + 1 . $gvv_qs ?>">
                                <?= $this->lang->line('forms_button_next_page') ?>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-success" type="submit"><?= $this->lang->line('forms_button_submit') ?></button>
                            <?php if (!empty($form['allow_upload_response'])): ?>
                                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#uploadResponseModal">
                                    <?= $this->lang->line('forms_button_upload_response') ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($form['allow_upload_response']) && $current_page_number >= $page_count): ?>
<div class="modal fade" id="uploadResponseModal" tabindex="-1" aria-labelledby="uploadResponseModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data"
            action="<?= site_url('forms/upload/' . rawurlencode($form['public_slug'])) ?>">
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
