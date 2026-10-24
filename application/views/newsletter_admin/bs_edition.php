<!-- VIEW: application/views/newsletter_admin/bs_edition.php -->
<?php
$this->lang->load('newsletter');
$csrf = $this->security->get_csrf_hash()
    ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'
    : '';
$status    = $nl['status'];
$editable  = in_array($status, array('draft', 'reviewing'), TRUE);
$decisions = array('en_attente', 'acceptee', 'modifiee', 'rejetee', 'reportee');
?>

<div id="body" class="body container-fluid">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <h3 class="mb-0"><?= htmlspecialchars($nl['title']) ?>
        <?php $this->load->view('newsletter_admin/_status_badge', array('status' => $status)); ?></h3>
    <a href="<?= site_url('newsletter_admin/editions') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> <?= $this->lang->line('nl_editions_title') ?></a>
</div>
<p class="text-muted mb-3">
    <?= $this->lang->line('newsletter_period') ?> : <?= date('d/m/Y', strtotime($nl['period_start'])) ?> – <?= date('d/m/Y', strtotime($nl['period_end'])) ?>
    · <?= $this->lang->line('nl_current_version') ?> : <strong>v<?= (int) $version ?></strong>
    · <?= $this->lang->line('nl_round') ?> : <?= (int) $nl['review_iteration'] ?>
</p>

<?php if (!empty($message)) echo $message; ?>

<div class="row g-3">

<!-- ── Brouillon ─────────────────────────────────────────────────────────── -->
<div class="col-lg-5">
<div class="card h-100">
<div class="card-header"><i class="fas fa-file-alt"></i> <?= $this->lang->line('nl_draft') ?></div>
<div class="card-body">
    <div class="d-flex flex-wrap gap-2 mb-2">
        <a href="<?= site_url('newsletter_admin/edition_pdf/' . $nl['id']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-file-pdf"></i> <?= $this->lang->line('nl_preview_pdf') ?></a>
        <?php if ($run): ?>
        <a href="<?= site_url('newsletter_admin/run/' . $run['id']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-clipboard-list"></i> <?= $this->lang->line('nl_activity_report') ?></a>
        <?php endif; ?>
    </div>
    <div class="small mb-2"><?= $this->lang->line('nl_versions') ?> :
        <?php foreach ($versions as $v): ?>
            <a href="<?= site_url('newsletter_admin/version/' . $nl['id'] . '/' . $v['version']) ?>"
               class="badge <?= $v['version'] === (int) $version ? 'bg-primary' : 'bg-light text-dark border' ?> text-decoration-none">v<?= $v['version'] ?></a>
        <?php endforeach; ?>
    </div>
    <?php if ($mentions && $status !== 'published'): ?>
    <div class="alert alert-warning small py-2 mb-2">
        <strong><?= sprintf($this->lang->line('nl_open_mentions'), count($mentions)) ?></strong>
        <ul class="mb-0 ps-3">
        <?php foreach (array_slice($mentions, 0, 15) as $m): ?><li><?= htmlspecialchars(mb_strimwidth($m, 0, 140, '…')) ?></li><?php endforeach; ?>
        <?php if (count($mentions) > 15): ?><li>…</li><?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
    <?php if ($draft_html !== NULL): ?>
    <details>
        <summary><?= $this->lang->line('nl_show_draft') ?></summary>
        <div class="border rounded p-2 mt-2 small" style="max-height: 60vh; overflow: auto;"><?= $draft_html ?></div>
    </details>
    <?php else: ?>
    <div class="alert alert-danger small"><?= $this->lang->line('nl_draft_missing') ?></div>
    <?php endif; ?>
</div>
</div>
</div>

<!-- ── Actions ───────────────────────────────────────────────────────────── -->
<div class="col-lg-7">
<div class="card h-100">
<div class="card-header"><i class="fas fa-tasks"></i> <?= $this->lang->line('nl_next_steps') ?></div>
<div class="card-body">

<?php if ($status === 'draft'): ?>
    <p><?= $this->lang->line('nl_help_draft') ?></p>
    <?php if (!$reviewers): ?>
        <div class="alert alert-warning small"><?= sprintf($this->lang->line('nl_no_reviewers'), site_url('newsletter_admin/configuration')) ?></div>
    <?php endif; ?>
    <div class="d-flex flex-wrap gap-2">
        <form method="post" action="<?= site_url('newsletter_admin/send_for_review/' . $nl['id']) ?>">
            <?= $csrf ?>
            <button type="submit" class="btn btn-primary" <?= $reviewers ? '' : 'disabled' ?>>
                <i class="fas fa-paper-plane"></i> <?= sprintf($this->lang->line('nl_btn_send_review'), (int) $version, count($reviewers)) ?>
            </button>
        </form>
        <form method="post" action="<?= site_url('newsletter_admin/validate_edition/' . $nl['id']) ?>"
              onsubmit="return confirm(<?= htmlspecialchars(json_encode(sprintf($this->lang->line('nl_confirm_validate'), (int) $version, count($mentions))), ENT_QUOTES) ?>);">
            <?= $csrf ?>
            <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> <?= sprintf($this->lang->line('nl_btn_validate'), (int) $version) ?></button>
        </form>
        <form method="post" action="<?= site_url('newsletter_admin/delete_edition/' . $nl['id']) ?>"
              onsubmit="return confirm(<?= htmlspecialchars(json_encode($this->lang->line('nl_confirm_delete')), ENT_QUOTES) ?>);">
            <?= $csrf ?>
            <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash"></i> <?= $this->lang->line('nl_btn_delete') ?></button>
        </form>
    </div>

<?php elseif ($status === 'reviewing'): ?>
    <p><?= sprintf($this->lang->line('nl_help_reviewing'),
            $nl['review_sent_at'] ? date('d/m/Y H:i', strtotime($nl['review_sent_at'])) : '?',
            $nl['review_deadline'] ? date('d/m/Y', strtotime($nl['review_deadline'])) : '?') ?></p>
    <?php if ($nl['review_deadline'] && date('Y-m-d') < $nl['review_deadline']): ?>
        <div class="alert alert-info small py-2"><?= $this->lang->line('nl_deadline_not_reached') ?></div>
    <?php endif; ?>
    <div class="d-flex flex-wrap gap-2 mb-2">
        <form method="post" action="<?= site_url('newsletter_admin/collect_replies/' . $nl['id']) ?>">
            <?= $csrf ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-inbox"></i> <?= $this->lang->line('nl_btn_collect') ?></button>
        </form>
        <form method="post" action="<?= site_url('newsletter_admin/revise/' . $nl['id']) ?>"
              onsubmit="this.querySelector('button').disabled = true; this.querySelector('.spinner-border').classList.remove('d-none');">
            <?= $csrf ?>
            <button type="submit" class="btn btn-warning" <?= $accepted ? '' : 'disabled' ?>>
                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                <i class="fas fa-robot"></i> <?= sprintf($this->lang->line('nl_btn_revise'), (int) $accepted) ?>
            </button>
        </form>
    </div>
    <?php if ($pending): ?>
        <div class="small text-warning"><i class="fas fa-exclamation-triangle"></i> <?= sprintf($this->lang->line('nl_pending_count'), (int) $pending) ?></div>
    <?php endif; ?>
    <div class="small text-muted"><?= $this->lang->line('nl_help_revise') ?></div>

<?php elseif ($status === 'approved'): ?>
    <p><?= sprintf($this->lang->line('nl_help_approved'), htmlspecialchars((string) $nl['approved_by']),
            $nl['approved_at'] ? date('d/m/Y H:i', strtotime($nl['approved_at'])) : '') ?></p>
    <form method="post" action="<?= site_url('newsletter_admin/publish_edition/' . $nl['id']) ?>"
          onsubmit="return confirm(<?= htmlspecialchars(json_encode($this->lang->line('nl_confirm_publish')), ENT_QUOTES) ?>);">
        <?= $csrf ?>
        <button type="submit" class="btn btn-success"><i class="fas fa-bullhorn"></i> <?= $this->lang->line('nl_btn_publish') ?></button>
    </form>

<?php elseif ($status === 'published'): ?>
    <p><?= sprintf($this->lang->line('nl_help_published'), $nl['published_at'] ? date('d/m/Y H:i', strtotime($nl['published_at'])) : '') ?></p>
    <a href="<?= site_url('newsletter/pdf/' . $nl['id']) ?>" target="_blank" class="btn btn-outline-primary">
        <i class="fas fa-file-pdf"></i> <?= $this->lang->line('nl_published_pdf') ?></a>

<?php else: ?>
    <p class="text-muted"><?= $this->lang->line('nl_help_other') ?></p>
<?php endif; ?>

</div>
</div>
</div>

</div>

<!-- ── Compte rendu de récupération ─────────────────────────────────────── -->
<?php if (!empty($collect)): ?>
<div class="alert alert-<?= $collect['retained'] ? 'success' : 'info' ?> alert-dismissible fade show mt-3" role="alert">
    <strong><?= sprintf($this->lang->line('nl_collect_summary'), (int) $collect['read'], count($collect['retained']), count($collect['rejected'])) ?></strong>
    <?php if ($collect['rejected']): ?>
    <ul class="mb-0 mt-1 small">
        <?php foreach ($collect['rejected'] as $r): ?>
        <li><?= htmlspecialchars($r['from']) ?> — « <?= htmlspecialchars(mb_strimwidth($r['subject'], 0, 80, '…')) ?> » : <?= htmlspecialchars($r['reason']) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- ── Suggestions ───────────────────────────────────────────────────────── -->
<h5 class="mt-4" id="suggestions"><i class="fas fa-comments"></i> <?= $this->lang->line('nl_suggestions') ?></h5>
<?php if (!$suggestions): ?>
    <p class="text-muted"><?= $this->lang->line('nl_no_suggestions') ?></p>
<?php else: ?>
<?php foreach ($suggestions as $s): ?>
<div class="card mb-2" id="suggestion-<?= (int) $s['id'] ?>">
<div class="card-body py-2">
    <div class="d-flex flex-wrap justify-content-between small text-muted mb-1">
        <span><strong><?= htmlspecialchars($s['reviewer_name'] ?: $s['reviewer_email']) ?></strong>
            &lt;<?= htmlspecialchars($s['reviewer_email']) ?>&gt; · <?= htmlspecialchars(substr($s['created_at'], 0, 16)) ?>
            · <?= $this->lang->line('nl_round') ?> <?= (int) $s['iteration'] ?>
            <?php if ($s['has_attachments']): ?> · <i class="fas fa-paperclip"></i>
                <?php foreach ($attachments[$s['id']] ?? array() as $att): ?>
                    <a href="<?= site_url('newsletter_admin/attachment/' . $s['id'] . '/' . rawurlencode($att)) ?>"><?= htmlspecialchars($att) ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </span>
        <span><?= htmlspecialchars($this->lang->line('nl_decision_' . $s['decision'])) ?></span>
    </div>
    <div class="small" style="white-space: pre-wrap;"><?= htmlspecialchars($s['content']) ?></div>
    <?php if ($editable): ?>
    <form method="post" action="<?= site_url('newsletter_admin/decide/' . $s['id']) ?>" class="row g-2 align-items-end mt-1">
        <?= $csrf ?>
        <div class="col-sm-3 col-md-2">
            <label class="form-label small mb-0"><?= $this->lang->line('nl_kind') ?></label>
            <select name="kind" class="form-select form-select-sm">
                <option value="remarque" <?= $s['kind'] === 'remarque' ? 'selected' : '' ?>><?= $this->lang->line('nl_kind_remarque') ?></option>
                <option value="article" <?= $s['kind'] === 'article' ? 'selected' : '' ?>><?= $this->lang->line('nl_kind_article') ?></option>
            </select>
        </div>
        <div class="col-sm-4 col-md-3">
            <label class="form-label small mb-0"><?= $this->lang->line('nl_decision') ?></label>
            <select name="decision" class="form-select form-select-sm">
                <?php foreach ($decisions as $d): ?>
                <option value="<?= $d ?>" <?= $s['decision'] === $d ? 'selected' : '' ?>><?= $this->lang->line('nl_decision_' . $d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label small mb-0"><?= $this->lang->line('nl_admin_text') ?></label>
            <textarea name="admin_text" rows="2" class="form-control form-control-sm"><?= htmlspecialchars((string) $s['admin_text']) ?></textarea>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-save"></i> <?= $this->lang->line('nl_btn_decide') ?></button>
        </div>
    </form>
    <?php elseif ($s['admin_text']): ?>
    <div class="small mt-1"><em><?= $this->lang->line('nl_admin_text') ?> :</em> <?= nl2br(htmlspecialchars($s['admin_text'])) ?></div>
    <?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ── Suggestions reportées ─────────────────────────────────────────────── -->
<?php if ($postponed && $editable): ?>
<h6 class="mt-3"><i class="fas fa-hourglass-half"></i> <?= $this->lang->line('nl_postponed') ?></h6>
<?php foreach ($postponed as $s): ?>
<div class="card mb-2 border-info">
<div class="card-body py-2 d-flex flex-wrap justify-content-between gap-2">
    <div class="small">
        <strong><?= htmlspecialchars($s['reviewer_name'] ?: $s['reviewer_email']) ?></strong> · <?= htmlspecialchars(substr($s['created_at'], 0, 10)) ?>
        <div style="white-space: pre-wrap;"><?= htmlspecialchars($s['admin_text'] ?: $s['content']) ?></div>
    </div>
    <form method="post" action="<?= site_url('newsletter_admin/attach_postponed/' . $s['id'] . '/' . $nl['id']) ?>">
        <?= $csrf ?>
        <button type="submit" class="btn btn-outline-info btn-sm"><i class="fas fa-reply"></i> <?= $this->lang->line('nl_btn_attach') ?></button>
    </form>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div>
