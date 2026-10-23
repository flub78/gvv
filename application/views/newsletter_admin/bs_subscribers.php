<!-- VIEW: application/views/newsletter_admin/bs_subscribers.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container-fluid">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><?= $this->lang->line('newsletter_admin_title') ?></h3>
    <a href="<?= site_url('newsletter_admin/publish') ?>" class="btn btn-success btn-sm">
        <i class="fas fa-plus"></i> <?= $this->lang->line('newsletter_publish_title') ?>
    </a>
</div>

<?php if (!empty($message)) echo $message; ?>

<h5><?= $this->lang->line('newsletter_subscribers') ?></h5>

<?php if (empty($subscribers)): ?>
    <p class="text-muted"><?= $this->lang->line('newsletter_no_subscribers') ?></p>
<?php else: ?>
<div class="table-responsive">
<table class="datatable_nopaging table table-striped table-hover table-sm">
    <thead>
        <tr>
            <th><?= $this->lang->line('newsletter_subscriber_name') ?></th>
            <th><?= $this->lang->line('newsletter_subscriber_email') ?></th>
            <th><?= $this->lang->line('newsletter_subscriber_status') ?></th>
            <th><?= $this->lang->line('newsletter_subscriber_date') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($subscribers as $sub): ?>
        <?php
            if ($sub['unsubscribed_at']) {
                $status = $this->lang->line('newsletter_status_unsubscribed');
                $badge  = 'secondary';
            } elseif ($sub['confirmed']) {
                $status = $this->lang->line('newsletter_status_active');
                $badge  = 'success';
            } else {
                $status = $this->lang->line('newsletter_status_pending');
                $badge  = 'warning';
            }
        ?>
        <tr>
            <td><?= htmlspecialchars($sub['last_name'] . ' ' . $sub['first_name']) ?></td>
            <td><?= htmlspecialchars($sub['email']) ?></td>
            <td><span class="badge bg-<?= $badge ?>"><?= $status ?></span></td>
            <td><?= htmlspecialchars(substr($sub['created_at'], 0, 10)) ?></td>
            <td>
                <form method="post" action="<?= site_url('newsletter_admin/delete/' . $sub['id']) ?>"
                      onsubmit="return confirm('<?= $this->lang->line('newsletter_delete_confirm') ?>')">
                    <?= $this->security->get_csrf_hash() ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">' : '' ?>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

</div>
