<div class="container mt-4">
    <?php
        $form = isset($form) ? $form : array('id' => 0, 'title' => '', 'code' => '');
        $submissions = isset($submissions) ? $submissions : array();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Reponses du formulaire</h1>
            <p class="text-muted mb-0"><?= html_escape($form['title']) ?> (<?= html_escape($form['code']) ?>)</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/edit/' . (int) $form['id']) ?>">Retour formulaire</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>UUID</th>
                            <th>Statut</th>
                            <th>Soumis par</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Aucune reponse enregistree.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td><?= (int) $submission['id'] ?></td>
                                    <td><code><?= html_escape((string) $submission['submission_uuid']) ?></code></td>
                                    <td><?= html_escape((string) $submission['status']) ?></td>
                                    <td>
                                        <?php
                                            $name = trim((string) $submission['submitter_name']);
                                            $email = trim((string) $submission['submitter_email']);
                                        ?>
                                        <?php if ($name !== '' || $email !== ''): ?>
                                            <?= html_escape($name !== '' ? $name : $email) ?>
                                            <?php if ($name !== '' && $email !== ''): ?>
                                                <br><span class="text-muted"><?= html_escape($email) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Anonyme</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= html_escape((string) $submission['submitted_at']) ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('forms_admin/submission/' . (int) $form['id'] . '/' . (int) $submission['id']) ?>">Ouvrir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
