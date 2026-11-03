<!-- VIEW: application/views/legal/bs_page.php -->
<div id="body" class="body container py-4" style="max-width: 900px;">
    <article class="legal-page">
        <?= $html ?>
    </article>

    <?php if (!empty($updated_at)): ?>
    <p class="text-muted small mt-4">
        <?= sprintf($this->lang->line('legal_updated_at'), date_db2ht(substr($updated_at, 0, 10))) ?>
    </p>
    <?php endif; ?>

    <p class="mt-4 small">
        <a href="<?= site_url('legal/' . ($page === 'confidentialite' ? 'conditions' : 'confidentialite')) ?>">
            <?= $this->lang->line($page === 'confidentialite' ? 'legal_conditions' : 'legal_confidentialite') ?>
        </a>
        &middot;
        <a href="<?= site_url('auth/login') ?>"><?= $this->lang->line('legal_login') ?></a>
    </p>
</div>
