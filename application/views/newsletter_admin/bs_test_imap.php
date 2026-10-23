<!-- VIEW: application/views/newsletter_admin/bs_test_imap.php -->
<?php $this->lang->load('newsletter'); ?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-inbox text-info"></i> <?= $this->lang->line('newsletter_test_imap_title') ?></h3>

<p class="text-muted">
  Boîte :
  <strong><?= htmlspecialchars($cfg['newsletter_imap_user'] ?? '(non configuré)') ?></strong>
  sur <strong><?= htmlspecialchars(($cfg['newsletter_imap_host'] ?? '') . ':' . ($cfg['newsletter_imap_port'] ?? '993')) ?></strong>
</p>

<?php if (empty($cfg['newsletter_imap_host'])): ?>
<div class="alert alert-warning">
  IMAP non configuré. <a href="<?= site_url('newsletter_admin/configuration') ?>">Configurer →</a>
</div>
<?php else: ?>

<form method="post" action="<?= site_url('newsletter_admin/test_imap') ?>" class="row g-2 align-items-end mb-4">
  <?= $this->security->get_csrf_hash() ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">' : '' ?>
  <div class="col-md-3">
    <label class="form-label"><?= $this->lang->line('newsletter_test_since') ?></label>
    <input type="text" class="form-control form-control-sm" name="since_date"
           value="<?= htmlspecialchars(isset($result['since']) ? $result['since'] : date('d-M-Y', strtotime('-30 days'))) ?>"
           placeholder="01-Jan-2026">
  </div>
  <div class="col-md-4">
    <label class="form-label"><?= $this->lang->line('newsletter_test_pattern') ?></label>
    <input type="text" class="form-control form-control-sm" name="subject_pattern"
           value="<?= htmlspecialchars(isset($result['pattern']) ? $result['pattern'] : '') ?>">
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-info btn-sm">
      <i class="fas fa-play"></i> <?= $this->lang->line('newsletter_test_run') ?>
    </button>
  </div>
</form>

<?php if (isset($result['error'])): ?>
<div class="alert alert-danger"><?= htmlspecialchars($result['error']) ?></div>
<?php endif; ?>

<?php if (!empty($result['steps'])): ?>
<div class="list-group mb-4">
<?php foreach ($result['steps'] as $step): ?>
  <div class="list-group-item p-2">

    <div class="d-flex align-items-center gap-2 mb-1">
      <span class="badge bg-<?= $step['ok'] ? 'success' : 'danger' ?>">
        <?= $step['ok'] ? $this->lang->line('newsletter_test_ok') : $this->lang->line('newsletter_test_fail') ?>
      </span>
      <strong><?= htmlspecialchars($step['label']) ?></strong>
      <span class="text-muted small"><?= htmlspecialchars($step['msg']) ?></span>
    </div>

    <?php if (!empty($step['headers'])): ?>
    <div class="table-responsive mt-2">
    <table class="table table-sm table-bordered table-hover mb-0" style="font-size:.85rem">
      <thead class="table-light">
        <tr>
          <th style="width:3rem">UID</th>
          <th>Sujet</th>
          <th>De</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($step['headers'] as $h): ?>
        <tr>
          <td class="text-muted"><?= (int) $h['uid'] ?></td>
          <td><?= htmlspecialchars($h['subject']) ?></td>
          <td class="text-muted" style="max-width:14rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
              title="<?= htmlspecialchars($h['from']) ?>"><?= htmlspecialchars($h['from']) ?></td>
          <td class="text-muted text-nowrap"><?= htmlspecialchars(substr($h['date'], 0, 16)) ?></td>
          <td class="text-nowrap">
            <!-- Lire l'email -->
            <a href="<?= site_url('newsletter_admin/test_imap_body/' . (int) $h['uid']) ?>"
               target="_blank" class="btn btn-outline-primary btn-sm me-1" title="Lire l'email">
              <i class="fas fa-envelope-open"></i>
            </a>
            <!-- Pièces jointes -->
            <?php foreach ($h['attachments'] as $att): ?>
              <?php $safe_name = rawurlencode($att['name']); ?>
              <a href="<?= site_url('newsletter_admin/test_imap_attachment/' . (int) $h['uid'] . '/' . urlencode($att['part']) . '/' . $safe_name) ?>"
                 target="_blank" class="btn btn-outline-secondary btn-sm me-1"
                 title="<?= htmlspecialchars($att['name']) ?>">
                <i class="fas fa-paperclip"></i> <?= htmlspecialchars(mb_strimwidth($att['name'], 0, 20, '…')) ?>
              </a>
            <?php endforeach; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>

  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<a href="<?= site_url('newsletter_admin/configuration') ?>" class="btn btn-outline-secondary btn-sm">Configuration</a>

</div>
