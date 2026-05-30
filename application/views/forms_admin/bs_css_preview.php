<div class="container mt-4 mb-5">
    <?php
        $form = isset($form) ? $form : array('title' => '', 'code' => '', 'global_css' => '', 'css_scope' => '');
        $page = isset($page) ? $page : array('title' => '', 'content_html' => '');
        $pages_count = isset($pages_count) ? (int) $pages_count : 0;
        $css_scope = trim(isset($form['css_scope']) ? (string) $form['css_scope'] : '');
        $scope_class = 'forms-public-root';
        if ($css_scope !== '') {
            $scope_class .= ' ' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $css_scope);
        }
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Preview CSS formulaire</h1>
            <p class="text-muted mb-0">
                <?= html_escape($form['title']) ?> (<?= html_escape($form['code']) ?>)
                - <?= $pages_count ?> page(s)
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/edit/' . (int) $form['id']) ?>">Retour edition</a>
    </div>

    <?php if (!empty($form['global_css'])): ?>
        <style>
            <?= $form['global_css'] ?>
        </style>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body <?= html_escape($scope_class) ?>">
            <h2 class="h5 mb-3">Apercu du rendu</h2>

            <?php if (!empty($page['title'])): ?>
                <h3 class="h6"><?= html_escape($page['title']) ?></h3>
            <?php endif; ?>

            <?php if (!empty($page['content_html'])): ?>
                <div class="border rounded p-3 mb-3 bg-light">
                    <?= $page['content_html'] ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-3">Aucun contenu de page pour ce formulaire.</div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Exemple champ texte</label>
                <input class="form-control" type="text" value="Valeur exemple" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Exemple champ select</label>
                <select class="form-select" disabled>
                    <option>Option 1</option>
                    <option>Option 2</option>
                </select>
            </div>
            <button class="btn btn-primary" type="button" disabled>Bouton exemple</button>
        </div>
    </div>
</div>
