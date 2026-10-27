<!-- VIEW: application/views/newsletter/bs_archive.php -->
<?php
$this->lang->load('newsletter');
$this->load->helper('validation');
$can_delete = has_role('club-admin');
$csrf = $this->security->get_csrf_hash()
    ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'
    : '';
?>

<div id="body" class="body container-fluid">

<h3><?= $this->lang->line('newsletter_archive_title') ?></h3>

<?php if (!empty($message)) echo $message; ?>

<?php if (empty($newsletters)): ?>
    <p class="text-muted"><?= $this->lang->line('newsletter_no_newsletters') ?></p>
<?php else: ?>
<div class="table-responsive">
<table class="datatable_nopaging table table-striped table-hover">
    <thead>
        <tr>
            <th><?= $this->lang->line('newsletter_publish_newsletter_title') ?></th>
            <th><?= $this->lang->line('newsletter_period') ?></th>
            <th><?= $this->lang->line('newsletter_published_at') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($newsletters as $nl): ?>
        <tr>
            <td><?= htmlspecialchars($nl['title']) ?></td>
            <td data-order="<?= htmlspecialchars($nl['period_start']) ?>"><?= date_db2ht($nl['period_start']) ?> – <?= date_db2ht($nl['period_end']) ?></td>
            <td data-order="<?= htmlspecialchars((string) $nl['published_at']) ?>"><?= date_db2ht(substr((string) $nl['published_at'], 0, 10)) ?></td>
            <td class="text-end">
                <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                <?php if (!empty($nl['pdf_path']) && file_exists($nl['pdf_path'])): ?>
                <a href="<?= site_url('newsletter/pdf/' . $nl['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-file-pdf"></i> <?= $this->lang->line('newsletter_open_pdf') ?>
                </a>
                <?php endif; ?>
                <?php if ($can_delete): ?>
                <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/delete_edition/' . $nl['id']) ?>"
                      onsubmit="return confirm(<?= htmlspecialchars(json_encode(sprintf($this->lang->line('nl_confirm_delete_published'), $nl['title'])), ENT_QUOTES) ?>);">
                    <?= $csrf ?>
                    <input type="hidden" name="back" value="archive">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> <?= $this->lang->line('nl_btn_delete') ?></button>
                </form>
                <?php endif; ?>
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
