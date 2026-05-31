<div class="container mt-4 mb-5">
    <?php
        $form = isset($form) ? $form : array('title' => '', 'description' => '', 'public_slug' => '');
        $current_page = isset($current_page) ? $current_page : array('title' => '', 'content_html' => '');
        $current_page_number = isset($current_page_number) ? (int) $current_page_number : 1;
        $page_count = isset($page_count) ? (int) $page_count : 1;
        $fields = isset($fields) ? $fields : array();
        $render_fields = isset($render_fields) ? $render_fields : array();
        $old_values = isset($old_values) ? $old_values : array();
        $css_scope = trim(isset($form['css_scope']) ? (string) $form['css_scope'] : '');
        $scope_class = 'forms-public-root';
        if ($css_scope !== '') {
            $scope_class .= ' ' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $css_scope);
        }
    ?>
    <?php if (!empty($form['global_css'])): ?>
        <style>
            <?= $form['global_css'] ?>
        </style>
    <?php endif; ?>
    <div class="mb-4">
        <h1 class="h3 mb-1"><?= html_escape($form['title']) ?></h1>
        <?php if (!empty($form['description'])): ?>
            <p class="text-muted mb-0"><?= nl2br(html_escape($form['description'])) ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body <?= html_escape($scope_class) ?>">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-0"><?= !empty($current_page['title']) ? html_escape($current_page['title']) : 'Page ' . (int) $current_page_number ?></h2>
                </div>
                <span class="badge bg-secondary">Page <?= (int) $current_page_number ?> / <?= (int) $page_count ?></span>
            </div>

            <?php if (!empty($current_page['content_html'])): ?>
                <div class="mb-4 border rounded p-3 bg-light">
                    <?= html_entity_decode($current_page['content_html'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" action="<?= site_url('forms/submit/' . rawurlencode($form['public_slug'])) ?>">
                <input type="hidden" name="page_number" value="<?= (int) $current_page_number ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="submitter_name">Nom (optionnel)</label>
                        <input class="form-control" type="text" name="submitter_name" id="submitter_name" maxlength="255">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="submitter_email">Email (optionnel)</label>
                        <input class="form-control" type="email" name="submitter_email" id="submitter_email" maxlength="255">
                    </div>
                </div>

                <?php if (empty($render_fields)): ?>
                    <div class="alert alert-warning">Aucun champ n'est configure sur cette page.</div>
                <?php else: ?>
                    <?php foreach ($render_fields as $field): ?>
                        <div class="mb-3">
                            <label class="form-label" for="<?= $field['name'] ?>">
                                <?= html_escape($field['label']) ?>
                                <?php if (!empty($field['required'])): ?><span class="text-danger">*</span><?php endif; ?>
                            </label>

                            <?php if ($field['type'] === 'textarea'): ?>
                                <textarea class="form-control" id="<?= $field['name'] ?>" name="<?= $field['name'] ?>" rows="4" <?= !empty($field['required']) ? 'required' : '' ?>><?= html_escape((string) $field['old_value']) ?></textarea>
                            <?php elseif ($field['type'] === 'select'): ?>
                                <select class="form-select" id="<?= $field['name'] ?>" name="<?= $field['name'] ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                                    <option value="">Selectionner...</option>
                                    <?php foreach ($field['options'] as $option): ?>
                                        <?php $selected = ((string) $field['old_value'] === (string) $option) ? 'selected' : ''; ?>
                                        <option value="<?= html_escape((string) $option) ?>" <?= $selected ?>><?= html_escape((string) $option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($field['type'] === 'radio'): ?>
                                <div>
                                    <?php foreach ($field['options'] as $idx => $option): ?>
                                        <?php $radio_id = $field['name'] . '_' . $idx; ?>
                                        <?php $checked = ((string) $field['old_value'] === (string) $option) ? 'checked' : ''; ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="<?= $radio_id ?>" name="<?= $field['name'] ?>" value="<?= html_escape((string) $option) ?>" <?= $checked ?> <?= !empty($field['required']) ? 'required' : '' ?>>
                                            <label class="form-check-label" for="<?= $radio_id ?>"><?= html_escape((string) $option) ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($field['type'] === 'checkbox'): ?>
                                <div>
                                    <?php foreach ($field['options'] as $idx => $option): ?>
                                        <?php $checkbox_id = $field['name'] . '_' . $idx; ?>
                                        <?php $checked = in_array((string) $option, $field['old_value'], true) ? 'checked' : ''; ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="<?= $checkbox_id ?>" name="<?= $field['name'] ?>[]" value="<?= html_escape((string) $option) ?>" <?= $checked ?>>
                                            <label class="form-check-label" for="<?= $checkbox_id ?>"><?= html_escape((string) $option) ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <input class="form-control" id="<?= $field['name'] ?>" name="<?= $field['name'] ?>" type="<?= $field['html_type'] ?>" value="<?= $field['html_type'] === 'file' ? '' : html_escape((string) $field['old_value']) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <?php if ((int) $current_page_number > 1): ?>
                            <a class="btn btn-outline-secondary" href="<?= site_url('forms/' . rawurlencode($form['public_slug'])) ?>?page=<?= (int) $current_page_number - 1 ?>">Page precedente</a>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ((int) $current_page_number < (int) $page_count): ?>
                            <a class="btn btn-primary" href="<?= site_url('forms/' . rawurlencode($form['public_slug'])) ?>?page=<?= (int) $current_page_number + 1 ?>">Page suivante</a>
                        <?php else: ?>
                            <button class="btn btn-success" type="submit">Envoyer ma reponse</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
