<!-- VIEW: application/views/acceptance/bs_readView.php -->
<?php
/**
 * Member read/accept/refuse screen for a single acceptance item.
 *
 * Full-read gating (PRD, Processus de lecture obligatoire): the PDF is shown
 * in a plain iframe (native browser PDF viewer, not instrumentable for
 * in-document scroll position), so completion is approximated by the user
 * scrolling the surrounding GVV page down to a marker placed right after the
 * iframe (IntersectionObserver). Accept/Refuse stay hidden until then.
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('acceptance');

$already_decided = !empty($record) && in_array($record['status'], array('accepted', 'refused'), true);
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-book-open"></i> <?= htmlspecialchars($item['title']) ?></h3>

<?php if ($this->session->flashdata('message')): ?>
    <?= $this->session->flashdata('message') ?>
<?php endif; ?>

<?php if ($already_decided): ?>
    <?php if ($record['status'] === 'accepted'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= sprintf($this->lang->line('acceptance_already_accepted'), date('d/m/Y', strtotime($record['acted_at']))) ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-times-circle"></i>
            <?= sprintf($this->lang->line('acceptance_already_refused'), date('d/m/Y', strtotime($record['acted_at']))) ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($has_pdf): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?= $this->lang->line('acceptance_read_instruction') ?></div>

    <div class="card mb-2">
        <div class="card-body p-0">
            <iframe src="<?= site_url('acceptance/pdf/' . $item['id']) ?>"
                    title="<?= htmlspecialchars($item['title']) ?>"
                    style="width: 100%; height: 65vh; border: 0;"></iframe>
        </div>
    </div>
    <div id="acceptanceReadSentinel"></div>
<?php endif; ?>

<div id="acceptanceActions" class="d-flex gap-2 mt-3 <?= $has_pdf ? 'd-none' : '' ?>">
    <a href="<?= site_url('acceptance/accept/' . $item['id']) ?>" class="btn btn-primary" id="acceptanceAcceptBtn">
        <i class="fas fa-check"></i> <?= $this->lang->line('acceptance_btn_accept') ?>
    </a>
    <a href="<?= site_url('acceptance/refuse/' . $item['id']) ?>" class="btn btn-outline-danger" id="acceptanceRefuseBtn"
       onclick="return confirm('<?= $this->lang->line('acceptance_confirm_refuse') ?>');">
        <i class="fas fa-times"></i> <?= $this->lang->line('acceptance_btn_refuse') ?>
    </a>
</div>

<a href="<?= site_url('acceptance') ?>" class="btn btn-link mt-3 ps-0"><i class="fas fa-arrow-left"></i> <?= $this->lang->line('acceptance_back_to_list') ?></a>

</div>

<?php if ($has_pdf): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var sentinel = document.getElementById('acceptanceReadSentinel');
    var actions = document.getElementById('acceptanceActions');
    if (!sentinel || !actions || typeof IntersectionObserver === 'undefined') {
        // No observer support: fail open rather than permanently hiding the
        // action buttons (never block the user silently, cf. instructions).
        if (actions) { actions.classList.remove('d-none'); }
        return;
    }
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                actions.classList.remove('d-none');
                observer.disconnect();
            }
        });
    }, { threshold: 1.0 });
    observer.observe(sentinel);
});
</script>
<?php endif; ?>
