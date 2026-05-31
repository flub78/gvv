<div class="container mt-4 mb-5">
    <?php
        $form = isset($form) ? $form : array('title' => '', 'description' => '', 'public_slug' => '');
        $current_page = isset($current_page) ? $current_page : array('title' => '', 'content_html' => '');
        $current_page_number = isset($current_page_number) ? (int) $current_page_number : 1;
        $page_count = isset($page_count) ? (int) $page_count : 1;
        $css_scope = trim(isset($form['css_scope']) ? (string) $form['css_scope'] : '');
        $scope_class = 'forms-public-root';
        if ($css_scope !== '') {
            $scope_class .= ' ' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $css_scope);
        }

        $raw_html = html_entity_decode(
            isset($current_page['content_html']) ? (string) $current_page['content_html'] : '',
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        // Strip document structure and standalone <form> wrapper from native HTML
        $raw_html = preg_replace('/<\!DOCTYPE[^>]*>/i', '', $raw_html);
        $raw_html = preg_replace('/<html[^>]*>/i', '', $raw_html);
        $raw_html = preg_replace('/<\/html>/i', '', $raw_html);
        $raw_html = preg_replace('/<head\b[^>]*>.*?<\/head>/is', '', $raw_html);
        $raw_html = preg_replace('/<body[^>]*>/i', '', $raw_html);
        $raw_html = preg_replace('/<\/body>/i', '', $raw_html);
        $raw_html = preg_replace('/<form\b[^>]*>/i', '', $raw_html);
        $raw_html = preg_replace('/<\/form>/i', '', $raw_html);
        $raw_html = preg_replace('/<button\b[^>]*\btype=["\']?(submit|reset)["\']?[^>]*>.*?<\/button>/is', '', $raw_html);
        $raw_html = preg_replace('/<input\b[^>]*\btype=["\']?(submit|reset|button)["\']?[^>]*\/?>/i', '', $raw_html);
        $raw_html = trim($raw_html);
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
                <h2 class="h5 mb-0">
                    <?= !empty($current_page['title']) ? html_escape($current_page['title']) : 'Page ' . $current_page_number ?>
                </h2>
                <span class="badge bg-secondary">Page <?= $current_page_number ?> / <?= $page_count ?></span>
            </div>

            <form method="post" enctype="multipart/form-data"
                  action="<?= site_url('forms/submit/' . rawurlencode($form['public_slug'])) ?>">
                <input type="hidden" name="page_number" value="<?= $current_page_number ?>">

                <?php if ($raw_html !== ''): ?>
                    <?= $raw_html ?>
                <?php else: ?>
                    <div class="alert alert-warning">Aucun contenu configuré sur cette page.</div>
                <?php endif; ?>

                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <?php if ($current_page_number > 1): ?>
                            <a class="btn btn-outline-secondary"
                               href="<?= site_url('forms/' . rawurlencode($form['public_slug'])) ?>?page=<?= $current_page_number - 1 ?>">
                                Page précédente
                            </a>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($current_page_number < $page_count): ?>
                            <a class="btn btn-primary"
                               href="<?= site_url('forms/' . rawurlencode($form['public_slug'])) ?>?page=<?= $current_page_number + 1 ?>">
                                Page suivante
                            </a>
                        <?php else: ?>
                            <button class="btn btn-success" type="submit">Envoyer ma réponse</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
