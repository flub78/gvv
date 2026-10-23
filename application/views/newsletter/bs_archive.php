<!-- VIEW: application/views/newsletter/bs_archive.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container-fluid">

<h3><?= $this->lang->line('newsletter_archive_title') ?></h3>

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
            <td><?= htmlspecialchars(substr($nl['period_start'], 0, 7)) ?> – <?= htmlspecialchars(substr($nl['period_end'], 0, 7)) ?></td>
            <td><?= htmlspecialchars(substr($nl['published_at'], 0, 10)) ?></td>
            <td>
                <?php if (!empty($nl['pdf_path']) && file_exists($nl['pdf_path'])): ?>
                <a href="<?= site_url('newsletter/pdf/' . $nl['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-file-pdf"></i> <?= $this->lang->line('newsletter_open_pdf') ?>
                </a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

</div>
