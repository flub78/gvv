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
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importHtmlModal">
                <?= $this->lang->line('forms_button_import_html') ?>
            </button>
            <a class="btn btn-primary" href="<?= site_url('forms_admin/create') ?>"><?= $this->lang->line('forms_button_new_form') ?></a>
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
                <table class="table table-hover mb-0 datatable" id="dt-forms">
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
                                    <td>
                                        <div class="d-flex justify-content-end align-items-center flex-wrap gap-1">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('forms_admin/edit/' . $form['id']) ?>"><?= $this->lang->line('forms_button_edit') ?></a>
                                        <a class="btn btn-sm btn-outline-dark" href="<?= site_url('forms_admin/pages/' . $form['id']) ?>"><?= $this->lang->line('forms_button_pages') ?></a>
                                        <?php $cnt = isset($submission_counts[$form['id']]) ? (int) $submission_counts[$form['id']] : 0; ?>
                                        <a class="btn btn-sm btn-outline-info" href="<?= site_url('forms_admin/submissions/' . $form['id']) ?>">
                                            <?= $this->lang->line('forms_button_responses') ?><?= $cnt > 0 ? ' <span class="badge bg-info text-dark ms-1">' . $cnt . '</span>' : '' ?>
                                        </a>
                                        <?php if (!empty($form['required_params']) && $form['required_params'] !== 'none'): ?>
                                        <a class="btn btn-sm btn-outline-success" href="<?= site_url('forms_admin/generate/' . rawurlencode($form['public_slug'])) ?>"><?= $this->lang->line('forms_button_generate') ?></a>
                                        <?php endif; ?>
                                        <form method="post" action="<?= site_url('forms_admin/duplicate/' . $form['id']) ?>" style="display:contents">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary"><?= $this->lang->line('forms_button_duplicate') ?></button>
                                        </form>
                                        <?php if ($form['status'] !== 'published'): ?>
                                            <form method="post" action="<?= site_url('forms_admin/publish/' . $form['id']) ?>" style="display:contents">
                                                <button type="submit" class="btn btn-sm btn-outline-success"><?= $this->lang->line('forms_button_publish') ?></button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="<?= site_url('forms_admin/delete/' . $form['id']) ?>" style="display:contents" onsubmit="return confirm('<?= $this->lang->line('forms_confirm_delete_form') ?>');">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= $this->lang->line('forms_button_delete') ?></button>
                                        </form>
                                        </div>
                                    </td>
                                </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal import depuis HTML -->
<div class="modal fade" id="importHtmlModal" tabindex="-1" aria-labelledby="importHtmlModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importHtmlModalLabel"><?= $this->lang->line('forms_title_import_html') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data" action="<?= site_url('forms_admin/form_import_html') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="html_file">Fichier HTML <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="html_file" name="html_file" accept=".html,.htm" required>
                        <div class="form-text">
                            Le CSS contenu dans les balises <code>&lt;style&gt;</code> est extrait automatiquement.
                            Le contenu du <code>&lt;body&gt;</code> devient la page du formulaire.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="import_code">Code technique <small class="text-muted">(optionnel)</small></label>
                        <input type="text" class="form-control" id="import_code" name="import_code"
                               maxlength="50" placeholder="auto-généré depuis le titre de la page HTML"
                               pattern="[a-zA-Z0-9_\-]+">
                        <div class="form-text"><?= $this->lang->line('forms_help_code') ?></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= $this->lang->line('forms_button_cancel') ?></button>
                    <button type="submit" class="btn btn-primary"><?= $this->lang->line('forms_button_import_html') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
