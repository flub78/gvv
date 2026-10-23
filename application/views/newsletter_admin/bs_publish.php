<!-- VIEW: application/views/newsletter_admin/bs_publish.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container-fluid">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><?= $this->lang->line('newsletter_publish_title') ?></h3>
    <a href="<?= site_url('newsletter_admin') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-users"></i> <?= $this->lang->line('newsletter_subscribers') ?>
    </a>
</div>

<?php if (!empty($message)) echo $message; ?>
<?php if (!empty($form_errors)) echo $form_errors; ?>

<div class="card mb-4">
<div class="card-body">
<form method="post" action="<?= site_url('newsletter_admin/publish') ?>" enctype="multipart/form-data">
    <?= $this->security->get_csrf_hash() ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">' : '' ?>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label"><?= $this->lang->line('newsletter_publish_newsletter_title') ?> *</label>
            <input type="text" class="form-control" name="nl_title"
                   value="<?= htmlspecialchars(isset($form_data['nl_title']) ? $form_data['nl_title'] : '') ?>" required>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label"><?= $this->lang->line('newsletter_publish_period_start') ?> *</label>
            <input type="date" class="form-control" name="period_start"
                   value="<?= htmlspecialchars(isset($form_data['period_start']) ? $form_data['period_start'] : '') ?>" required>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label"><?= $this->lang->line('newsletter_publish_period_end') ?> *</label>
            <input type="date" class="form-control" name="period_end"
                   value="<?= htmlspecialchars(isset($form_data['period_end']) ? $form_data['period_end'] : '') ?>" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= $this->lang->line('newsletter_publish_pdf') ?> *</label>
        <input type="file" class="form-control" name="pdf_file" accept="application/pdf" required>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-paper-plane"></i> <?= $this->lang->line('newsletter_publish_btn') ?>
    </button>
</form>
</div>
</div>

<?php if (!empty($newsletters)): ?>
<h5><?= $this->lang->line('newsletter_archive_title') ?></h5>
<div class="table-responsive">
<table class="datatable_nopaging table table-striped table-hover table-sm">
    <thead>
        <tr>
            <th><?= $this->lang->line('newsletter_publish_newsletter_title') ?></th>
            <th><?= $this->lang->line('newsletter_period') ?></th>
            <th><?= $this->lang->line('newsletter_subscriber_status') ?></th>
            <th><?= $this->lang->line('newsletter_published_at') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($newsletters as $nl): ?>
        <tr>
            <td><?= htmlspecialchars($nl['title']) ?></td>
            <td><?= htmlspecialchars(substr($nl['period_start'], 0, 7)) ?> – <?= htmlspecialchars(substr($nl['period_end'], 0, 7)) ?></td>
            <td><span class="badge bg-<?= $nl['status'] === 'published' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($nl['status']) ?></span></td>
            <td><?= $nl['published_at'] ? htmlspecialchars(substr($nl['published_at'], 0, 10)) : '—' ?></td>
            <td>
                <?php if ($nl['status'] === 'published'): ?>
                <a href="<?= site_url('newsletter_admin/send/' . $nl['id']) ?>" class="btn btn-sm btn-outline-primary"
                   onclick="return confirm('Renvoyer aux abonnés ?')">
                    <i class="fas fa-paper-plane"></i>
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
