<div class="container mt-4 mb-5">
    <?php
        $form = isset($form) ? $form : array('id' => 0, 'title' => '', 'code' => '');
        $submission = isset($submission) ? $submission : array('id' => 0, 'submission_uuid' => '', 'status' => '', 'submitted_at' => '');
        $values = isset($values) ? $values : array();
        $files = isset($files) ? $files : array();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Detail de reponse</h1>
            <p class="text-muted mb-0">
                <?= html_escape($form['title']) ?> (<?= html_escape($form['code']) ?>)
                - Soumission #<?= (int) $submission['id'] ?>
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/submissions/' . (int) $form['id']) ?>">Retour reponses</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>UUID:</strong> <code><?= html_escape((string) $submission['submission_uuid']) ?></code></div>
                <div class="col-md-4"><strong>Statut:</strong> <?= html_escape((string) $submission['status']) ?></div>
                <div class="col-md-4"><strong>Date:</strong> <?= html_escape((string) $submission['submitted_at']) ?></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header"><strong>Valeurs soumises</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Champ</th>
                            <th>Type</th>
                            <th>Valeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($values)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">Aucune valeur.</td></tr>
                        <?php else: ?>
                            <?php foreach ($values as $value): ?>
                                <tr>
                                    <td><?= html_escape((string) $value['field_label']) ?></td>
                                    <td><?= html_escape((string) $value['field_type']) ?></td>
                                    <td><pre class="mb-0" style="white-space: pre-wrap;"><?= html_escape((string) $value['value_text']) ?></pre></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header"><strong>Fichiers uploades</strong></div>
        <div class="card-body">
            <?php if (empty($files)): ?>
                <div class="text-muted">Aucun fichier pour cette soumission.</div>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <?php
                        $mime = isset($file['mime_type']) ? (string) $file['mime_type'] : '';
                        $inline_url = site_url('forms_admin/submission_file/' . (int) $form['id'] . '/' . (int) $submission['id'] . '/' . (int) $file['id']) . '?inline=1';
                        $download_url = site_url('forms_admin/submission_file/' . (int) $form['id'] . '/' . (int) $submission['id'] . '/' . (int) $file['id']);
                        $is_image = strpos($mime, 'image/') === 0;
                        $is_pdf = ($mime === 'application/pdf');
                    ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong><?= html_escape((string) $file['original_name']) ?></strong>
                                <div class="text-muted small">
                                    Champ: <?= html_escape((string) $file['field_label']) ?>
                                    <?php if (!empty($file['size_bytes'])): ?>
                                        - <?= (int) $file['size_bytes'] ?> octets
                                    <?php endif; ?>
                                    <?php if ($mime !== ''): ?>
                                        - <?= html_escape($mime) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= $inline_url ?>" target="_blank">Preview</a>
                                <a class="btn btn-sm btn-outline-primary" href="<?= $download_url ?>">Telecharger</a>
                            </div>
                        </div>

                        <?php if ($is_image): ?>
                            <img src="<?= $inline_url ?>" alt="Apercu" style="max-width: 100%; max-height: 360px;" class="border rounded">
                        <?php elseif ($is_pdf): ?>
                            <iframe src="<?= $inline_url ?>" style="width:100%; height:360px; border:1px solid #dee2e6; border-radius: 0.375rem;"></iframe>
                        <?php else: ?>
                            <div class="text-muted small">Apercu inline non disponible pour ce type de fichier.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
