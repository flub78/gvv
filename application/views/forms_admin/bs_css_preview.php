<div class="container mt-4 mb-5">
    <?php
        $form = isset($form) ? $form : array('title' => '', 'code' => '', 'global_css' => '', 'css_scope' => '', 'description' => '');
        $current_page = isset($current_page) ? $current_page : array('title' => '', 'content_html' => '');
        $current_page_number = isset($current_page_number) ? (int) $current_page_number : 1;
        $page_count = isset($page_count) ? (int) $page_count : 0;
        $render_fields = isset($render_fields) ? $render_fields : array();
        $css_scope = trim(isset($form['css_scope']) ? (string) $form['css_scope'] : '');
        $scope_class = 'forms-public-root';
        if ($css_scope !== '') {
            $scope_class .= ' ' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $css_scope);
        }
        $preview_base = site_url('forms_admin/css_preview/' . (int) $form['id']);
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Prévisualisation — <?= html_escape($form['title']) ?></h1>
            <p class="text-muted mb-0"><?= html_escape($form['code']) ?></p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/edit/' . (int) $form['id']) ?>">Retour édition</a>
    </div>

    <div class="alert alert-info py-2 mb-3">
        <strong>Mode prévisualisation</strong> — le formulaire n'est pas soumissible ici.
    </div>

    <?php if (!empty($form['global_css'])): ?>
        <style>
            <?= $form['global_css'] ?>
        </style>
    <?php endif; ?>

    <?php if (!empty($form['description'])): ?>
        <p class="text-muted mb-4"><?= nl2br(html_escape($form['description'])) ?></p>
    <?php endif; ?>

    <?php if ($page_count === 0): ?>
        <div class="alert alert-warning">Ce formulaire ne contient aucune page.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body <?= html_escape($scope_class) ?>">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">
                        <?= !empty($current_page['title']) ? html_escape($current_page['title']) : 'Page ' . $current_page_number ?>
                    </h2>
                    <span class="badge bg-secondary">Page <?= $current_page_number ?> / <?= $page_count ?></span>
                </div>

                <?php if (!empty($current_page['content_html'])): ?>
                    <div class="mb-4 border rounded p-3 bg-light">
                        <?= html_entity_decode($current_page['content_html'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <fieldset disabled>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom (optionnel)</label>
                            <input class="form-control" type="text">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email (optionnel)</label>
                            <input class="form-control" type="email">
                        </div>
                    </div>

                    <?php if (empty($render_fields)): ?>
                        <div class="alert alert-warning">Aucun champ configuré sur cette page.</div>
                    <?php else: ?>
                        <?php foreach ($render_fields as $field): ?>
                            <div class="mb-3">
                                <label class="form-label">
                                    <?= html_escape($field['label']) ?>
                                    <?php if (!empty($field['required'])): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>

                                <?php if ($field['type'] === 'textarea'): ?>
                                    <textarea class="form-control" rows="4"></textarea>
                                <?php elseif ($field['type'] === 'select'): ?>
                                    <select class="form-select">
                                        <option value="">Sélectionner...</option>
                                        <?php foreach ($field['options'] as $option): ?>
                                            <option><?= html_escape((string) $option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($field['type'] === 'radio'): ?>
                                    <div>
                                        <?php foreach ($field['options'] as $idx => $option): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="preview_radio_<?= (int) $field['id'] ?>">
                                                <label class="form-check-label"><?= html_escape((string) $option) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($field['type'] === 'checkbox'): ?>
                                    <div>
                                        <?php foreach ($field['options'] as $idx => $option): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox">
                                                <label class="form-check-label"><?= html_escape((string) $option) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <input class="form-control" type="<?= html_escape($field['html_type']) ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <?php if ($current_page_number > 1): ?>
                                <a class="btn btn-outline-secondary" href="<?= $preview_base ?>?page=<?= $current_page_number - 1 ?>">Page précédente</a>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($current_page_number < $page_count): ?>
                                <a class="btn btn-primary" href="<?= $preview_base ?>?page=<?= $current_page_number + 1 ?>">Page suivante</a>
                            <?php else: ?>
                                <button class="btn btn-success" type="button" disabled>Envoyer ma réponse</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    <?php endif; ?>
</div>
