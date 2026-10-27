<!-- VIEW: application/views/newsletter_admin/bs_edition.php -->
<?php
$this->lang->load('newsletter');
$csrf = $this->security->get_csrf_hash()
    ? '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'
    : '';
$status    = $nl['status'];
$editable  = in_array($status, array('draft', 'reviewing'), TRUE);
$decisions = array('en_attente', 'acceptee', 'modifiee', 'rejetee', 'reportee');
$default_label = $this->lang->line('nl_annotation_default_label');
$has_directives = $annotations || $retained;
?>

<div id="body" class="body container-fluid">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <h3 class="mb-0"><?= htmlspecialchars($nl['title']) ?>
        <?php $this->load->view('newsletter_admin/_status_badge', array('status' => $status)); ?></h3>
    <a href="<?= site_url('newsletter_admin/editions') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> <?= $this->lang->line('nl_editions_title') ?></a>
</div>
<p class="text-muted mb-2">
    <?= $this->lang->line('newsletter_period') ?> : <?= date('d/m/Y', strtotime($nl['period_start'])) ?> – <?= date('d/m/Y', strtotime($nl['period_end'])) ?>
    · <?= $this->lang->line('nl_current_version') ?> : <strong>v<?= (int) $version ?></strong>
    · <?= $this->lang->line('nl_round') ?> : <?= (int) $nl['review_iteration'] ?>
    · <?= $this->lang->line('nl_versions') ?> :
    <?php foreach ($versions as $v): ?>
        <a href="<?= site_url('newsletter_admin/version/' . $nl['id'] . '/' . $v['version']) ?>"
           class="badge <?= $v['version'] === (int) $version ? 'bg-primary' : 'bg-light text-dark border' ?> text-decoration-none">v<?= $v['version'] ?></a>
    <?php endforeach; ?>
</p>

<?php if (!empty($message)) echo $message; ?>

<!-- ── Compte rendu du relevé des réponses ──────────────────────────────── -->
<?php if (!empty($collect)): ?>
<div class="alert alert-<?= $collect['retained'] ? 'success' : 'info' ?> alert-dismissible fade show" role="alert">
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

<!-- ── Étape suivante ────────────────────────────────────────────────────── -->
<div class="card mb-3">
<div class="card-header"><i class="fas fa-tasks"></i> <?= $this->lang->line('nl_next_steps') ?></div>
<div class="card-body">

<?php if ($status === 'draft'): ?>
    <p><?= $this->lang->line('nl_help_draft') ?></p>
    <?php if (!$reviewers): ?>
        <div class="alert alert-warning small"><?= sprintf($this->lang->line('nl_no_reviewers'), site_url('newsletter_admin/configuration')) ?></div>
    <?php endif; ?>
<?php elseif ($status === 'reviewing'): ?>
    <p><?= sprintf($this->lang->line('nl_help_reviewing'),
            $nl['review_sent_at'] ? date('d/m/Y H:i', strtotime($nl['review_sent_at'])) : '?',
            $nl['review_deadline'] ? date('d/m/Y', strtotime($nl['review_deadline'])) : '?') ?></p>
    <?php if ($nl['review_deadline'] && date('Y-m-d') < $nl['review_deadline']): ?>
        <div class="alert alert-info small py-2"><?= $this->lang->line('nl_deadline_not_reached') ?></div>
    <?php endif; ?>
<?php elseif ($status === 'approved'): ?>
    <p><?= sprintf($this->lang->line('nl_help_approved'), htmlspecialchars($approver),
            $nl['approved_at'] ? date('d/m/Y H:i', strtotime($nl['approved_at'])) : '') ?></p>
<?php elseif ($status === 'published'): ?>
    <p><?= sprintf($this->lang->line('nl_help_published'), $nl['published_at'] ? date('d/m/Y H:i', strtotime($nl['published_at'])) : '') ?></p>
    <?php if ($unsent): ?>
        <div class="alert alert-warning small py-2"><?= sprintf($this->lang->line('nl_unsent_count'), (int) $unsent) ?></div>
    <?php endif; ?>
<?php else: ?>
    <p class="text-muted"><?= $this->lang->line('nl_help_other') ?></p>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2">
    <a href="<?= site_url('newsletter_admin/edition_pdf/' . $nl['id']) ?>" target="_blank" class="btn btn-outline-secondary">
        <i class="fas fa-file-pdf"></i> <?= $this->lang->line('nl_preview_pdf') ?></a>

    <?php if ($status === 'draft'): ?>
    <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/send_for_review/' . $nl['id']) ?>">
        <?= $csrf ?>
        <button type="submit" class="btn btn-primary" <?= $reviewers ? '' : 'disabled' ?>>
            <i class="fas fa-paper-plane"></i> <?= sprintf($this->lang->line('nl_btn_send_review'), (int) $version, count($reviewers)) ?>
        </button>
    </form>
    <?php endif; ?>

    <?php if ($can_collect): ?>
    <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/collect_replies/' . $nl['id']) ?>">
        <?= $csrf ?>
        <button type="submit" class="btn btn-<?= $status === 'reviewing' ? 'primary' : 'outline-primary' ?>"><i class="fas fa-inbox"></i> <?= $this->lang->line('nl_btn_collect') ?></button>
    </form>
    <?php endif; ?>

    <?php if ($editable): ?>
    <a href="<?= site_url('newsletter_admin/directives/' . $nl['id']) ?>"
       class="btn btn-<?= $status === 'reviewing' ? 'warning' : 'outline-warning' ?><?= $has_directives ? '' : ' disabled' ?>"
       title="<?= htmlspecialchars($this->lang->line('nl_help_directives')) ?>">
        <i class="fas fa-download"></i> <?= sprintf($this->lang->line('nl_btn_directives'), count($annotations), (int) $retained) ?></a>
    <a href="<?= site_url('newsletter_admin/import') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-file-import"></i> <?= $this->lang->line('nl_btn_import_next') ?></a>
    <?php endif; ?>

    <?php if ($status === 'draft'): ?>
    <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/validate_and_publish/' . $nl['id']) ?>"
          onsubmit="return confirm(<?= htmlspecialchars(json_encode(sprintf($this->lang->line('nl_confirm_validate'), (int) $version, count($mentions))), ENT_QUOTES) ?>);">
        <?= $csrf ?>
        <button type="submit" class="btn btn-success"><i class="fas fa-bullhorn"></i> <?= sprintf($this->lang->line('nl_btn_validate'), (int) $version) ?></button>
    </form>
    <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/delete_edition/' . $nl['id']) ?>"
          onsubmit="return confirm(<?= htmlspecialchars(json_encode($this->lang->line('nl_confirm_delete')), ENT_QUOTES) ?>);">
        <?= $csrf ?>
        <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash"></i> <?= $this->lang->line('nl_btn_delete') ?></button>
    </form>
    <?php elseif ($status === 'approved'): ?>
    <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/publish_edition/' . $nl['id']) ?>"
          onsubmit="return confirm(<?= htmlspecialchars(json_encode($this->lang->line('nl_confirm_publish')), ENT_QUOTES) ?>);">
        <?= $csrf ?>
        <button type="submit" class="btn btn-success"><i class="fas fa-bullhorn"></i> <?= $this->lang->line('nl_btn_publish') ?></button>
    </form>
    <?php elseif ($status === 'published'): ?>
    <a href="<?= site_url('newsletter/pdf/' . $nl['id']) ?>" target="_blank" class="btn btn-outline-primary">
        <i class="fas fa-file-pdf"></i> <?= $this->lang->line('nl_published_pdf') ?></a>
    <?php if ($unsent): ?>
    <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/resend_to_subscribers/' . $nl['id']) ?>"
          onsubmit="return confirm(<?= htmlspecialchars(json_encode(sprintf($this->lang->line('nl_confirm_resend'), (int) $unsent)), ENT_QUOTES) ?>);">
        <?= $csrf ?>
        <button type="submit" class="btn btn-warning"><i class="fas fa-redo"></i> <?= sprintf($this->lang->line('nl_btn_resend'), (int) $unsent) ?></button>
    </form>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($pending): ?>
    <div class="small text-warning mt-2"><i class="fas fa-exclamation-triangle"></i> <?= sprintf($this->lang->line('nl_pending_count'), (int) $pending) ?></div>
<?php endif; ?>
<?php if ($editable): ?>
    <div class="small text-muted mt-2"><?= $this->lang->line('nl_help_directives') ?></div>
<?php endif; ?>

<?php if ($mentions && $status !== 'published'): ?>
<div class="alert alert-warning small py-2 mt-3 mb-0">
    <strong><?= sprintf($this->lang->line('nl_open_mentions'), count($mentions)) ?></strong>
    <ul class="mb-0 ps-3">
    <?php foreach (array_slice($mentions, 0, 15) as $m): ?><li><?= htmlspecialchars(mb_strimwidth($m, 0, 140, '…')) ?></li><?php endforeach; ?>
    <?php if (count($mentions) > 15): ?><li>…</li><?php endif; ?>
    </ul>
</div>
<?php endif; ?>

</div>
</div>

<!-- ── Remarques ─────────────────────────────────────────────────────────── -->
<h5 id="remarques"><i class="fas fa-comments"></i> <?= $this->lang->line('nl_remarks') ?></h5>
<?php if (!$remarks): ?>
    <p class="text-muted"><?= $this->lang->line('nl_no_remarks') ?></p>
<?php else: ?>
<?php foreach ($remarks as $r): ?>
<div class="card mb-2" id="remarque-<?= (int) $r['id'] ?>">
<div class="card-body py-2">
    <div class="d-flex flex-wrap justify-content-between small text-muted mb-1">
        <span><strong><?= htmlspecialchars($r['reviewer_name'] ?: $r['reviewer_email']) ?></strong>
            &lt;<?= htmlspecialchars($r['reviewer_email']) ?>&gt; · <?= htmlspecialchars(substr($r['created_at'], 0, 16)) ?>
            · <?= $this->lang->line('nl_round') ?> <?= (int) $r['iteration'] ?>
            <?php if ($r['has_attachments']): ?> · <i class="fas fa-paperclip"></i>
                <?php foreach ($attachments[$r['id']] ?? array() as $att): ?>
                    <a href="<?= site_url('newsletter_admin/attachment/' . $r['id'] . '/' . rawurlencode($att)) ?>"><?= htmlspecialchars($att) ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </span>
        <span><?= htmlspecialchars($this->lang->line('nl_decision_' . $r['decision'])) ?></span>
    </div>
    <div class="small" style="white-space: pre-wrap;"><?= htmlspecialchars($r['content']) ?></div>
    <?php if ($editable): ?>
    <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/decide/' . $r['id']) ?>" class="row g-2 align-items-end mt-1">
        <?= $csrf ?>
        <div class="col-sm-5 col-md-3">
            <label class="form-label small mb-0"><?= $this->lang->line('nl_decision') ?></label>
            <select name="decision" class="form-select form-select-sm">
                <?php foreach ($decisions as $d): ?>
                <option value="<?= $d ?>" <?= $r['decision'] === $d ? 'selected' : '' ?>><?= $this->lang->line('nl_decision_' . $d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-7">
            <label class="form-label small mb-0"><?= $this->lang->line('nl_admin_text') ?></label>
            <textarea name="admin_text" rows="2" class="form-control form-control-sm"><?= htmlspecialchars((string) $r['admin_text']) ?></textarea>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-save"></i> <?= $this->lang->line('nl_btn_decide') ?></button>
        </div>
    </form>
    <?php elseif ($r['admin_text']): ?>
    <div class="small mt-1"><em><?= $this->lang->line('nl_admin_text') ?> :</em> <?= nl2br(htmlspecialchars($r['admin_text'])) ?></div>
    <?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($postponed && $editable): ?>
<h6 class="mt-3"><i class="fas fa-hourglass-half"></i> <?= $this->lang->line('nl_postponed') ?></h6>
<?php foreach ($postponed as $r): ?>
<div class="card mb-2 border-info">
<div class="card-body py-2 d-flex flex-wrap justify-content-between gap-2">
    <div class="small">
        <strong><?= htmlspecialchars($r['reviewer_name'] ?: $r['reviewer_email']) ?></strong> · <?= htmlspecialchars(substr($r['created_at'], 0, 10)) ?>
        <div style="white-space: pre-wrap;"><?= htmlspecialchars($r['admin_text'] ?: $r['content']) ?></div>
    </div>
    <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/attach_postponed/' . $r['id'] . '/' . $nl['id']) ?>">
        <?= $csrf ?>
        <button type="submit" class="btn btn-outline-info btn-sm"><i class="fas fa-reply"></i> <?= $this->lang->line('nl_btn_attach') ?></button>
    </form>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ── Brouillon annoté ──────────────────────────────────────────────────── -->
<h5 class="mt-4" id="brouillon"><i class="fas fa-file-alt"></i> <?= $this->lang->line('nl_annotated_draft') ?> — v<?= (int) $version ?></h5>
<?php if ($blocks === NULL): ?>
<div class="alert alert-danger"><?= $this->lang->line('nl_draft_missing') ?></div>
<?php else: ?>
<?php if ($editable): ?><p class="small text-muted"><?= $this->lang->line('nl_annotated_help') ?></p><?php endif; ?>
<div class="border rounded p-3 nl-draft">
<?php foreach ($blocks as $i => $b): ?>
<div class="nl-block position-relative" id="bloc-<?= (int) $i ?>">
    <?php if ($editable): ?>
    <button type="button" class="btn btn-sm btn-link text-secondary p-0 float-end ms-2 nl-comment-btn" data-bs-toggle="collapse"
            data-bs-target="#comment-form-<?= (int) $i ?>" title="<?= htmlspecialchars($this->lang->line('nl_btn_add_comment')) ?>">
        <i class="far fa-comment-dots"></i></button>
    <?php endif; ?>
    <?= $b['html'] ?>
    <?php foreach ($b['annotations'] as $a): ?>
    <div class="nl-annotation border-start border-4 border-warning bg-warning-subtle rounded-end px-3 py-2 mb-3">
        <?php if ($editable): ?>
        <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/delete_annotation/' . $a['id']) ?>" class="float-end ms-2"
              onsubmit="return confirm(<?= htmlspecialchars(json_encode($this->lang->line('nl_confirm_delete_comment')), ENT_QUOTES) ?>);">
            <?= $csrf ?>
            <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="<?= htmlspecialchars($this->lang->line('nl_btn_delete')) ?>"><i class="fas fa-trash"></i></button>
        </form>
        <?php endif; ?>
        <strong><?= htmlspecialchars($a['label']) ?> :</strong> <?= nl2br(htmlspecialchars($a['content'])) ?>
    </div>
    <?php endforeach; ?>
    <?php if ($editable): ?>
    <div class="collapse" id="comment-form-<?= (int) $i ?>">
        <form method="post" class="nl-inline" action="<?= site_url('newsletter_admin/annotate/' . $nl['id']) ?>" class="card card-body bg-light mb-3 py-2">
            <?= $csrf ?>
            <input type="hidden" name="block_index" value="<?= (int) $i ?>">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small mb-0"><?= $this->lang->line('nl_annotation_label') ?></label>
                    <input type="text" name="label" class="form-control form-control-sm" maxlength="100" required value="<?= htmlspecialchars($default_label) ?>">
                </div>
                <div class="col-md-9">
                    <label class="form-label small mb-0"><?= $this->lang->line('nl_annotation_content') ?></label>
                    <textarea name="content" rows="2" class="form-control form-control-sm" required></textarea>
                </div>
            </div>
            <div class="mt-2 d-flex gap-2">
                <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-comment-medical"></i> <?= $this->lang->line('nl_btn_save_comment') ?></button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#comment-form-<?= (int) $i ?>"><?= $this->lang->line('nl_btn_cancel') ?></button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>

<style>
form.nl-inline { background: none; border: 0; padding: 0; margin: 0; }
.nl-draft .nl-block { padding: 0 .25rem; border-radius: .25rem; }
.nl-draft .nl-block:hover { background: var(--bs-tertiary-bg, #f8f9fa); }
.nl-draft .nl-comment-btn { opacity: .35; }
.nl-draft .nl-block:hover .nl-comment-btn { opacity: 1; }
.nl-draft blockquote { border-left: 4px solid var(--bs-primary); background: var(--bs-light); padding: .5rem 1rem; }
.nl-draft img { max-width: 100%; }
</style>
