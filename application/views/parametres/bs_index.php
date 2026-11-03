<!-- VIEW: application/views/parametres/bs_index.php -->
<?php
/**
 * Page Configuration à onglets.
 *
 * @var string $tab       onglet affiché
 * @var array  $tabs      onglets accessibles (clé => icon, label, help)
 * @var array  $statuses  état de chaque onglet (complete, incomplete, optional)
 * @var array  $params    paramètres de l'onglet : def, field, values[lang|''], writable
 * @var bool   $writable  au moins un paramètre de l'onglet est modifiable
 * @var array  $saved     libellés des paramètres enregistrés
 * @var array  $errors    champ(.langue) => name, message
 * @var array  $posted    saisies à réafficher après une erreur
 * @var array  $warnings  modules actifs auxquels il manque des paramètres : module, tab, label
 * @var array  $test_email   résultat du dernier e-mail de test (onglet Messagerie)
 */
$status_badges = array(
    'complete'   => array('bg-success', 'fa-check'),
    'incomplete' => array('bg-danger', 'fa-exclamation'),
    'optional'   => array('bg-secondary', 'fa-minus'),
);

/** Valeur à afficher : saisie postée après une erreur, sinon valeur en vigueur. */
$shown = function ($p, $lang) use ($posted) {
    if (is_array($posted) && isset($posted[$p['field']])) {
        $v = $posted[$p['field']];
        if ($lang !== '' && is_array($v) && isset($v[$lang])) {
            return $v[$lang];
        }
        if ($lang === '' && !is_array($v)) {
            return $v;
        }
    }
    return $p['values'][$lang]['value'];
};
?>
<div id="body" class="body container-fluid">

<h3><i class="fas fa-sliders-h text-primary me-2"></i><?= $this->lang->line('parametres_title') ?></h3>

<?php if (!empty($saved)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert" id="parametres-saved">
    <i class="fas fa-check-circle me-1"></i> <?= $this->lang->line('parametres_saved') ?>
    <?= htmlspecialchars(implode(', ', $saved)) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php elseif (is_array($saved) && empty($errors)): ?>
<div class="alert alert-info alert-dismissible fade show" role="alert" id="parametres-unchanged">
    <i class="fas fa-info-circle me-1"></i> <?= $this->lang->line('parametres_nothing_changed') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert" id="parametres-errors">
    <i class="fas fa-times-circle me-1"></i> <?= $this->lang->line('parametres_not_saved') ?>
    <ul class="mb-0">
    <?php foreach ($errors as $err): ?>
        <li><?= htmlspecialchars($err['name']) ?> : <?= htmlspecialchars($err['message']) ?></li>
    <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3" id="parametres-tabs">
<?php foreach ($tabs as $key => $info): list($badge, $icon) = $status_badges[$statuses[$key]]; ?>
    <li class="nav-item">
        <a class="nav-link<?= $key === $tab ? ' active' : '' ?>" data-tab="<?= $key ?>"
           href="<?= site_url('parametres/index/' . $key) ?>">
            <i class="<?= $info['icon'] ?> me-1"></i><?= $this->lang->line($info['label']) ?>
            <span class="badge <?= $badge ?> ms-1" title="<?= $this->lang->line('parametres_status_' . $statuses[$key]) ?>">
                <i class="fas <?= $icon ?>"></i>
            </span>
        </a>
    </li>
<?php endforeach; ?>
</ul>

<?php $info = $tabs[$tab]; ?>
<div class="d-flex flex-wrap align-items-start gap-2 mb-3">
<p class="text-muted mb-0 flex-grow-1">
    <?= $this->lang->line($info['help']) ?>
    <span class="ms-2 small">(<?= $this->lang->line('parametres_status_' . $statuses[$tab]) ?>)</span>
</p>
<?php if ($writable): /* Historique réservé à qui peut modifier l'onglet (EX-HIS-02). */ ?>
<a href="<?= site_url('parametres/historique/' . $tab) ?>" class="btn btn-outline-secondary btn-sm" id="parametres-history-link">
    <i class="fas fa-history me-1"></i><?= $this->lang->line('parametres_history') ?>
</a>
<?php endif; ?>
</div>

<?php if (!empty($warnings)): ?>
<div class="alert alert-warning" role="alert" id="parametres-warnings">
    <i class="fas fa-exclamation-triangle me-1"></i>
    <?php foreach ($warnings as $w): ?>
    <div><?= sprintf($this->lang->line('parametres_module_incomplete'), htmlspecialchars($w['module']),
        anchor('parametres/index/' . $w['tab'], htmlspecialchars($w['label']))) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($test_email)): ?>
<div class="alert alert-<?= $test_email['ok'] ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert" id="parametres-test-result">
    <i class="fas <?= $test_email['ok'] ? 'fa-check-circle' : 'fa-times-circle' ?> me-1"></i>
    <?= htmlspecialchars($test_email['text']) ?>
    <?php if (!empty($test_email['debug'])): ?>
    <details class="mt-2"><summary class="small"><?= $this->lang->line('parametres_test_email_debug') ?></summary>
        <pre class="small bg-dark text-light p-2 mt-1" style="white-space: pre-wrap; word-break: break-all;"><?= htmlspecialchars(strip_tags(str_replace(array('<br />', '<br>'), "\n", $test_email['debug']))) ?></pre>
    </details>
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!$writable): ?>
<div class="alert alert-secondary" id="parametres-read-only">
    <i class="fas fa-lock me-1"></i> <?= $this->lang->line('parametres_read_only') ?>
</div>
<?php endif; ?>

<form method="post" action="<?= site_url('parametres/save/' . $tab) ?>" id="parametres-form" enctype="multipart/form-data" novalidate>
<input type="hidden" name="parametres_token" value="<?= htmlspecialchars($token) ?>">

<?php foreach ($params as $key => $p):
    $def = $p['def'];
    $langs = empty($def['per_lang']) ? array('') : $languages;
?>
<div class="card mb-3" data-param="<?= htmlspecialchars($key) ?>">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <strong><?= $this->lang->line($def['label']) ?></strong>
        <?php if (!empty($def['required'])): ?><span class="text-danger" title="<?= $this->lang->line('parametres_required') ?>">*</span><?php endif; ?>
        <?php if (isset($public_pages[$key])): ?>
        <?php $this->load->view('parametres/bs_public_url', array('url' => $public_pages[$key], 'preview' => TRUE)); ?>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!empty($def['help']) && $this->lang->line($def['help']) !== ''): ?>
        <p class="form-text mt-0"><?= htmlspecialchars($this->lang->line($def['help'])) ?></p>
        <?php endif; ?>

        <?php if (count($langs) > 1): ?>
        <ul class="nav nav-pills nav-sm mb-2" role="tablist">
            <?php foreach ($langs as $i => $lang): ?>
            <li class="nav-item">
                <button type="button" class="nav-link py-1<?= $i === 0 ? ' active' : '' ?>" data-bs-toggle="pill"
                        data-bs-target="#pane-<?= $p['field'] ?>-<?= $lang ?>"><?= $this->lang->line('parametres_lang_' . $lang) ?></button>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <div class="tab-content">
        <?php foreach ($langs as $i => $lang):
            $name = 'p[' . $p['field'] . ']' . ($lang !== '' ? '[' . $lang . ']' : '');
            $id = 'f-' . $p['field'] . ($lang !== '' ? '-' . $lang : '');
            $error_key = $p['field'] . ($lang !== '' ? '.' . $lang : '');
            $current = $p['values'][$lang];
            $value = $shown($p, $lang);
            $invalid = isset($errors[$error_key]) ? ' is-invalid' : '';
        ?>
            <div class="tab-pane<?= ($i === 0) ? ' show active' : '' ?>" id="pane-<?= $p['field'] ?>-<?= $lang ?>">
            <?php if (!$p['writable']): /* Lecture seule : valeur affichée, jamais un champ (EX-PRO-05). */ ?>
                <div class="parametres-ro" id="<?= $id ?>">
                <?php if (!empty($def['secret'])): ?>
                    <?php $this->load->view('parametres/bs_secret_status', array('defined' => !$current['is_default'] && $current['value'] !== '')); ?>
                <?php elseif ($def['type'] === 'bool'): ?>
                    <span class="badge <?= $value ? 'bg-success' : 'bg-secondary' ?>"><?= $this->lang->line($value ? 'parametres_yes' : 'parametres_no') ?></span>
                <?php elseif ($def['type'] === 'image'): ?>
                    <?= $value ? img(array('src' => $value, 'style' => 'max-height:80px', 'alt' => '')) : '—' ?>
                <?php elseif ($def['type'] === 'markdown' || $def['type'] === 'textarea'): ?>
                    <pre class="small bg-light p-2 border mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars((string) $value) ?></pre>
                <?php elseif ($def['type'] === 'select'): ?>
                    <?= htmlspecialchars(isset($def['options'][(string) $value]) ? $def['options'][(string) $value] : (string) $value) ?>
                <?php else: ?>
                    <?= ((string) $value !== '') ? htmlspecialchars((string) $value) : '—' ?>
                <?php endif; ?>
                </div>
            <?php elseif ($def['type'] === 'markdown' || $def['type'] === 'textarea'): ?>
                <textarea class="form-control font-monospace<?= $invalid ?>" rows="14" id="<?= $id ?>"
                          name="<?= $name ?>"><?= htmlspecialchars((string) $value) ?></textarea>
            <?php elseif ($def['type'] === 'bool'): ?>
                <input type="hidden" name="<?= $name ?>" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input<?= $invalid ?>" type="checkbox" id="<?= $id ?>" name="<?= $name ?>"
                           value="1"<?= $value ? ' checked' : '' ?>>
                </div>
            <?php elseif ($def['type'] === 'select'): ?>
                <select class="form-select<?= $invalid ?>" id="<?= $id ?>" name="<?= $name ?>">
                <?php foreach ($def['options'] as $opt => $opt_label): ?>
                    <option value="<?= htmlspecialchars((string) $opt) ?>"<?= (string) $opt === (string) $value ? ' selected' : '' ?>><?= htmlspecialchars($opt_label) ?></option>
                <?php endforeach; ?>
                </select>
            <?php elseif ($def['type'] === 'image'): ?>
                <?php if ($current['value']): ?>
                <div class="mb-2"><?= img(array('src' => $current['value'], 'style' => 'max-height:80px', 'alt' => '')) ?></div>
                <?php endif; ?>
                <input class="form-control<?= $invalid ?>" type="file" accept="image/png,image/jpeg,image/gif"
                       id="<?= $id ?>" name="img_<?= $p['field'] ?>">
            <?php elseif (!empty($def['secret'])): ?>
                <?php $this->load->view('parametres/bs_secret_status', array('defined' => !$current['is_default'] && $current['value'] !== '')); ?>
                <input class="form-control mt-1<?= $invalid ?>" id="<?= $id ?>" name="<?= $name ?>" type="password"
                       autocomplete="new-password" value="" placeholder="<?= htmlspecialchars($this->lang->line('parametres_secret_placeholder')) ?>">
                <?php if (!$current['is_default'] && $current['value'] !== ''): ?>
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" id="clear-<?= $p['field'] ?>" name="clear[<?= $p['field'] ?>]" value="1">
                    <label class="form-check-label small" for="clear-<?= $p['field'] ?>"><?= $this->lang->line('parametres_secret_clear') ?></label>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <input class="form-control<?= $invalid ?>" id="<?= $id ?>" name="<?= $name ?>"
                       type="<?= in_array($def['type'], array('email', 'url')) ? $def['type'] : ($def['type'] === 'int' ? 'number' : 'text') ?>"
                       value="<?= htmlspecialchars((string) $value) ?>">
            <?php endif; ?>

            <?php if ($invalid): ?>
                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors[$error_key]['message']) ?></div>
            <?php endif; ?>

            <div class="form-text">
            <?php if ($current['is_default'] && !empty($def['secret'])): ?>
            <?php elseif ($current['is_default']): ?>
                <i class="fas fa-info-circle"></i> <?= $this->lang->line('parametres_default_value') ?>
            <?php elseif (!empty($current['updated_at'])): ?>
                <?= sprintf($this->lang->line('parametres_last_update'),
                    date_db2ht(substr($current['updated_at'], 0, 10)) . ' ' . substr($current['updated_at'], 11, 5),
                    htmlspecialchars((string) $current['updated_by'])) ?>
            <?php endif; ?>
            </div>

            <?php if (isset($generic_texts[$key][$lang]) && $generic_texts[$key][$lang] !== ''): ?>
                <details class="mt-2">
                    <summary class="small"><?= $this->lang->line('parametres_generic_text') ?></summary>
                    <pre class="small bg-light p-2 border mt-1" style="white-space: pre-wrap;"><?= htmlspecialchars($generic_texts[$key][$lang]) ?></pre>
                </details>
            <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php if (!empty($newsletter_url)): ?>
<div class="card mb-3" data-param="newsletter">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <strong><?= $this->lang->line('parametres_newsletter_page') ?></strong>
        <?php $this->load->view('parametres/bs_public_url', array('url' => $newsletter_url, 'preview' => FALSE)); ?>
    </div>
</div>
<?php endif; ?>

<?php if ($writable): ?>
<button type="submit" class="btn btn-primary" id="parametres-save">
    <i class="fas fa-save me-1"></i><?= $this->lang->line('parametres_save') ?>
</button>
<?php endif; ?>
</form>

<?php if ($tab === 'messagerie' && $writable): ?>
<div class="card mt-4" id="parametres-test-email">
    <div class="card-header"><i class="fas fa-paper-plane me-1"></i><strong><?= $this->lang->line('parametres_test_email_title') ?></strong></div>
    <div class="card-body">
        <p class="form-text mt-0"><?= $this->lang->line('parametres_test_email_card_help') ?></p>
        <form method="post" action="<?= site_url('parametres/test_email') ?>" class="row g-2 align-items-end">
            <input type="hidden" name="parametres_token" value="<?= htmlspecialchars($token) ?>">
            <div class="col-sm-8 col-md-6">
                <label class="form-label" for="test-email-to"><?= $this->lang->line('parametres_test_email_to') ?></label>
                <input type="email" class="form-control" id="test-email-to" name="to" required
                       value="<?= htmlspecialchars(!empty($test_email['to']) ? $test_email['to'] : (string) $test_email_to) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary" id="parametres-test-email-send">
                    <i class="fas fa-paper-plane me-1"></i><?= $this->lang->line('parametres_test_email_send') ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
</div>

<script>
(function () {
    var form = document.getElementById('parametres-form');
    var dirty = false;
    if (form) {
        form.addEventListener('input', function () { dirty = true; });
        form.addEventListener('change', function () { dirty = true; });
        form.addEventListener('submit', function () { dirty = false; });
    }
    // Changer d'onglet avec des saisies non enregistrées demande confirmation.
    document.querySelectorAll('#parametres-tabs a.nav-link, #parametres-history-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            if (dirty && !window.confirm(<?= json_encode($this->lang->line('parametres_unsaved_confirm')) ?>)) {
                e.preventDefault();
            }
        });
    });
    document.querySelectorAll('.copy-url').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var done = function () {
                btn.innerHTML = '<i class="fas fa-check"></i> ' + <?= json_encode($this->lang->line('parametres_copied')) ?>;
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(btn.dataset.url).then(done);
            } else {
                var t = document.createElement('textarea');
                t.value = btn.dataset.url;
                document.body.appendChild(t);
                t.select();
                document.execCommand('copy');
                document.body.removeChild(t);
                done();
            }
        });
    });
})();
</script>
