<!-- VIEW: application/views/newsletter_admin/bs_editions.php -->
<?php
$this->lang->load('newsletter');
$csrf = $this->security->get_csrf_hash()
    ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'
    : '';
?>

<div id="body" class="body container-fluid">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0"><i class="fas fa-newspaper text-info"></i> <?= $this->lang->line('nl_editions_title') ?></h3>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= site_url('newsletter_admin/import') ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-file-import"></i> <?= $this->lang->line('nl_import_title') ?></a>
        <a href="<?= site_url('newsletter_admin/postponed') ?>" class="btn btn-outline-secondary btn-sm<?= $postponed ? '' : ' disabled' ?>"
           title="<?= htmlspecialchars($this->lang->line('nl_postponed_help')) ?>">
            <i class="fas fa-download"></i> <?= sprintf($this->lang->line('nl_btn_postponed'), (int) $postponed) ?></a>
        <a href="<?= site_url('newsletter_admin') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-users"></i> <?= $this->lang->line('newsletter_subscribers') ?></a>
    </div>
</div>

<?php if (!empty($message)) echo $message; ?>

<?php if (empty($newsletters)): ?>
<p class="text-muted"><?= $this->lang->line('nl_no_editions') ?></p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-sm align-middle">
    <thead>
        <tr>
            <th><?= $this->lang->line('newsletter_publish_newsletter_title') ?></th>
            <th><?= $this->lang->line('newsletter_period') ?></th>
            <th><?= $this->lang->line('nl_status') ?></th>
            <th><?= $this->lang->line('nl_round') ?></th>
            <th><?= $this->lang->line('nl_updated_at') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($newsletters as $n): ?>
        <tr>
            <td><a href="<?= site_url('newsletter_admin/edition/' . $n['id']) ?>"><?= htmlspecialchars($n['title']) ?></a></td>
            <td><?= date('d/m/Y', strtotime($n['period_start'])) ?> – <?= date('d/m/Y', strtotime($n['period_end'])) ?></td>
            <td><?php $this->load->view('newsletter_admin/_status_badge', array('status' => $n['status'])); ?></td>
            <td><?= (int) $n['review_iteration'] ?></td>
            <td><?= htmlspecialchars(substr((string) $n['updated_at'], 0, 16)) ?></td>
            <td class="text-end">
                <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                <?php if ($n['can_collect']): ?>
                <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/collect_replies/' . $n['id']) ?>">
                    <?= $csrf ?>
                    <input type="hidden" name="back" value="editions">
                    <button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-inbox"></i> <?= $this->lang->line('nl_btn_collect') ?></button>
                </form>
                <?php endif; ?>
                <a href="<?= site_url('newsletter_admin/edition/' . $n['id']) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-folder-open"></i> <?= $this->lang->line('nl_open') ?></a>
                <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/delete_edition/' . $n['id']) ?>"
                      onsubmit="return confirm(<?= htmlspecialchars(json_encode(sprintf($this->lang->line(
                          $n['status'] === 'published' ? 'nl_confirm_delete_published' : 'nl_confirm_delete_edition'), $n['title'])), ENT_QUOTES) ?>);">
                    <?= $csrf ?>
                    <input type="hidden" name="back" value="editions">
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> <?= $this->lang->line('nl_btn_delete') ?></button>
                </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

</div>

<style>
form.nl-inline { background: none; border: 0; padding: 0; margin: 0; }
</style>
