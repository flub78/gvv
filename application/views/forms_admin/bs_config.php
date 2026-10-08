<?php $this->lang->load('forms'); ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1"><?= $this->lang->line('forms_config_title') ?></h1>
            <p class="text-muted mb-0"><?= $this->lang->line('forms_config_subtitle') ?></p>
        </div>
        <a class="btn btn-primary" href="<?= site_url('forms_admin/config_create') ?>"><?= $this->lang->line('forms_config_button_new') ?></a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= html_escape($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($warning)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?= html_escape($warning) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= html_escape($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= $this->lang->line('forms_config_label_key') ?></th>
                            <th><?= $this->lang->line('forms_config_label_label') ?></th>
                            <th><?= $this->lang->line('forms_config_label_value') ?></th>
                            <th><?= $this->lang->line('forms_config_label_scope') ?></th>
                            <th class="text-end"><?= $this->lang->line('forms_label_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($params)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4"><?= $this->lang->line('forms_config_empty') ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($params as $p): ?>
                                <tr>
                                    <td><code><?= html_escape($p['param_key']) ?></code></td>
                                    <td><?= html_escape($p['param_label']) ?></td>
                                    <td>
                                        <?php if ($p['param_value'] !== ''): ?>
                                            <?= html_escape(mb_strimwidth($p['param_value'], 0, 80, '…')) ?>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['club_id']): ?>
                                            <?= html_escape(!empty($p['section_name']) ? $p['section_name'] : $p['club_id']) ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?= $this->lang->line('forms_config_scope_global') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end gap-1">
                                            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('forms_admin/config_edit/' . $p['id']) ?>"><?= $this->lang->line('forms_config_button_edit') ?></a>
                                            <form method="post" action="<?= site_url('forms_admin/config_delete/' . $p['id']) ?>" style="display:contents" onsubmit="return confirm(<?= html_escape(json_encode($this->lang->line('forms_config_confirm_delete'), JSON_UNESCAPED_UNICODE)) ?>);">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><?= $this->lang->line('forms_config_button_delete') ?></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <small class="text-muted">
            <?= $this->lang->line('forms_config_help_source') ?>
        </small>
    </div>

    <div class="card shadow-sm mt-4" id="stamps">
        <div class="card-header">
            <h2 class="h5 mb-0"><?= $this->lang->line('forms_stamp_title') ?></h2>
        </div>
        <div class="card-body">
            <p class="text-muted small"><?= $this->lang->line('forms_stamp_help') ?></p>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= $this->lang->line('forms_config_label_scope') ?></th>
                            <th><?= $this->lang->line('forms_stamp_label_preview') ?></th>
                            <th class="text-end"><?= $this->lang->line('forms_label_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stamps as $st): ?>
                            <tr>
                                <td><?= html_escape($st['label']) ?></td>
                                <td>
                                    <?php if ($st['data_uri']): ?>
                                        <img src="<?= $st['data_uri'] ?>" alt="" style="max-height:90px; max-width:200px; background:repeating-conic-gradient(#e9ecef 0% 25%, #fff 0% 50%) 50% / 16px 16px;">
                                    <?php else: ?>
                                        <span class="text-muted fst-italic"><?= $this->lang->line('forms_stamp_none') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end flex-wrap gap-1">
                                        <form method="post" enctype="multipart/form-data" action="<?= site_url('forms_admin/stamp_upload/' . $st['scope']) ?>" class="d-flex gap-1">
                                            <input type="file" name="stamp" accept="image/png" class="form-control form-control-sm" required>
                                            <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">
                                                <?= $this->lang->line($st['data_uri'] ? 'forms_stamp_button_replace' : 'forms_stamp_button_upload') ?>
                                            </button>
                                        </form>
                                        <?php if ($st['data_uri']): ?>
                                            <form method="post" action="<?= site_url('forms_admin/stamp_delete/' . $st['scope']) ?>" style="display:contents" onsubmit="return confirm(<?= html_escape(json_encode($this->lang->line('forms_stamp_confirm_delete'), JSON_UNESCAPED_UNICODE)) ?>);">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><?= $this->lang->line('forms_config_button_delete') ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <small class="text-muted"><?= $this->lang->line('forms_stamp_help_html') ?></small>
        </div>
    </div>
</div>
