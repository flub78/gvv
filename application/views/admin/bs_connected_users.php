<!-- VIEW: application/views/admin/bs_connected_users.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-users text-primary"></i>
                <?= $this->lang->line('admin_connected_users_title') ?>
            </h2>
            <p class="text-muted mb-2"><?= sprintf($this->lang->line('admin_connected_users_window'), round($sess_expiration / 60)) ?></p>
            <a href="<?= controller_url('welcome') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_back') ?>
            </a>
        </div>
    </div>

    <?php if (empty($connected_users)) : ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <?= $this->lang->line('admin_connected_users_none') ?>
        </div>
    <?php else : ?>
    <div class="card">
        <div class="card-header">
            <span class="badge bg-primary fs-6"><?= count($connected_users) ?></span>
            <?= $this->lang->line('admin_connected_users_active') ?>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><i class="fas fa-user me-1"></i><?= $this->lang->line('admin_connected_col_user') ?></th>
                        <th><i class="fas fa-tag me-1"></i><?= $this->lang->line('admin_connected_col_role') ?></th>
                        <th><i class="fas fa-network-wired me-1"></i><?= $this->lang->line('admin_connected_col_ip') ?></th>
                        <th><i class="fas fa-clock me-1"></i><?= $this->lang->line('admin_connected_col_last_activity') ?></th>
                        <th><i class="fas fa-desktop me-1"></i><?= $this->lang->line('admin_connected_col_browser') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($connected_users as $u) : ?>
                    <?php
                        $idle_sec = time() - $u['last_activity'];
                        $idle_min = floor($idle_sec / 60);
                        $badge_class = $idle_min < 5 ? 'success' : ($idle_min < 15 ? 'warning' : 'secondary');
                        $idle_label = $idle_min < 1
                            ? $this->lang->line('admin_connected_just_now')
                            : sprintf($this->lang->line('admin_connected_minutes_ago'), $idle_min);
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($u['role']) ?></span></td>
                        <td><code><?= htmlspecialchars($u['ip_address']) ?></code></td>
                        <td>
                            <span class="badge bg-<?= $badge_class ?>">
                                <?= htmlspecialchars($idle_label) ?>
                            </span>
                            <small class="text-muted ms-1"><?= date('H:i:s', $u['last_activity']) ?></small>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars(substr($u['user_agent'], 0, 60)) . (strlen($u['user_agent']) > 60 ? '…' : '') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>
