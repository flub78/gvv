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
                                            <form method="post" action="<?= site_url('forms_admin/config_delete/' . $p['id']) ?>" style="display:contents" onsubmit="return confirm('<?= $this->lang->line('forms_config_confirm_delete') ?>');">
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
</div>
