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
<p class="text-muted">
    <?= $this->lang->line($info['help']) ?>
    <span class="ms-2 small">(<?= $this->lang->line('parametres_status_' . $statuses[$tab]) ?>)</span>
</p>

<?php if (!$writable): ?>
<div class="alert alert-secondary" id="parametres-read-only">
    <i class="fas fa-lock me-1"></i> <?= $this->lang->line('parametres_read_only') ?>
</div>
<?php endif; ?>

<form method="post" action="<?= site_url('parametres/save/' . $tab) ?>" id="parametres-form">

<?php foreach ($params as $key => $p):
    $def = $p['def'];
    $disabled = $p['writable'] ? '' : ' disabled';
    $langs = empty($def['per_lang']) ? array('') : $languages;
?>
<div class="card mb-3" data-param="<?= htmlspecialchars($key) ?>">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <strong><?= $this->lang->line($def['label']) ?></strong>
        <?php if (isset($public_pages[$key])): ?>
        <span class="ms-auto small">
            <?= $this->lang->line('parametres_public_url') ?> :
            <a href="<?= $public_pages[$key] ?>" target="_blank" class="public-url"><?= $public_pages[$key] ?></a>
            <button type="button" class="btn btn-outline-secondary btn-sm ms-1 copy-url" data-url="<?= $public_pages[$key] ?>">
                <i class="fas fa-copy"></i> <?= $this->lang->line('parametres_copy') ?>
            </button>
            <a href="<?= $public_pages[$key] ?>" target="_blank" class="btn btn-outline-primary btn-sm ms-1">
                <i class="fas fa-eye"></i> <?= $this->lang->line('parametres_preview') ?>
            </a>
        </span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!empty($def['help'])): ?>
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
            <?php if ($def['type'] === 'markdown' || $def['type'] === 'textarea'): ?>
                <textarea class="form-control font-monospace<?= $invalid ?>" rows="14" id="<?= $id ?>"
                          name="<?= $name ?>"<?= $disabled ?>><?= htmlspecialchars((string) $value) ?></textarea>
            <?php elseif ($def['type'] === 'bool'): ?>
                <input type="hidden" name="<?= $name ?>" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input<?= $invalid ?>" type="checkbox" id="<?= $id ?>" name="<?= $name ?>"
                           value="1"<?= $value ? ' checked' : '' ?><?= $disabled ?>>
                </div>
            <?php else: ?>
                <input class="form-control<?= $invalid ?>" id="<?= $id ?>" name="<?= $name ?>"
                       type="<?= in_array($def['type'], array('email', 'url', 'password')) ? $def['type'] : 'text' ?>"
                       value="<?= empty($def['secret']) ? htmlspecialchars((string) $value) : '' ?>"<?= $disabled ?>>
            <?php endif; ?>

            <?php if ($invalid): ?>
                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors[$error_key]['message']) ?></div>
            <?php endif; ?>

            <div class="form-text">
            <?php if ($current['is_default']): ?>
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
        <span class="ms-auto small">
            <?= $this->lang->line('parametres_public_url') ?> :
            <a href="<?= $newsletter_url ?>" target="_blank" class="public-url"><?= $newsletter_url ?></a>
            <button type="button" class="btn btn-outline-secondary btn-sm ms-1 copy-url" data-url="<?= $newsletter_url ?>">
                <i class="fas fa-copy"></i> <?= $this->lang->line('parametres_copy') ?>
            </button>
        </span>
    </div>
</div>
<?php endif; ?>

<?php if ($writable): ?>
<button type="submit" class="btn btn-primary" id="parametres-save">
    <i class="fas fa-save me-1"></i><?= $this->lang->line('parametres_save') ?>
</button>
<?php endif; ?>
</form>
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
    document.querySelectorAll('#parametres-tabs a.nav-link').forEach(function (link) {
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
