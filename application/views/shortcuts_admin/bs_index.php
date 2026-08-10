<?php $this->lang->load('shortcuts'); ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1"><?= $this->lang->line('shortcuts_title') ?></h1>
            <p class="text-muted mb-0"><?= $this->lang->line('shortcuts_subtitle') ?></p>
        </div>
        <a class="btn btn-primary" href="<?= site_url('shortcuts_admin/create') ?>"><?= $this->lang->line('shortcuts_button_new') ?></a>
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
                            <th><?= $this->lang->line('shortcuts_label_dashboard') ?></th>
                            <th><?= $this->lang->line('shortcuts_label_section') ?></th>
                            <th><?= $this->lang->line('shortcuts_label_title') ?></th>
                            <th><?= $this->lang->line('shortcuts_label_role') ?></th>
                            <th><?= $this->lang->line('shortcuts_label_scope') ?></th>
                            <th><?= $this->lang->line('shortcuts_label_active') ?></th>
                            <th class="text-end"><?= $this->lang->line('shortcuts_label_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shortcuts)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4"><?= $this->lang->line('shortcuts_empty') ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($shortcuts as $s): ?>
                                <tr>
                                    <td><code><?= html_escape($s['dashboard']) ?></code></td>
                                    <td><?= html_escape($s['section'] ?: '—') ?></td>
                                    <td><?= html_escape($s['title']) ?></td>
                                    <td>
                                        <?php if ($s['role_required']): ?>
                                            <?= html_escape($s['role_required']) ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?= $this->lang->line('shortcuts_scope_all_roles') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($s['club_id']): ?>
                                            <?= html_escape(!empty($s['section_name']) ? $s['section_name'] : $s['club_id']) ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?= $this->lang->line('shortcuts_scope_global') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= site_url('shortcuts_admin/toggle/' . $s['id']) ?>" style="display:contents">
                                            <button type="submit" class="btn btn-sm <?= $s['active'] ? 'btn-success' : 'btn-outline-secondary' ?>">
                                                <?= $this->lang->line($s['active'] ? 'shortcuts_status_active' : 'shortcuts_status_inactive') ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end gap-1">
                                            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('shortcuts_admin/edit/' . $s['id']) ?>"><?= $this->lang->line('shortcuts_button_edit') ?></a>
                                            <form method="post" action="<?= site_url('shortcuts_admin/delete/' . $s['id']) ?>" style="display:contents" onsubmit="return confirm('<?= $this->lang->line('shortcuts_confirm_delete') ?>');">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><?= $this->lang->line('shortcuts_button_delete') ?></button>
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
</div>
