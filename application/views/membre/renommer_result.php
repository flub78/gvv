<!-- VIEW: application/views/membre/renommer_result.php -->
<?php
$success = $result['success'];
$message = $result['message'];
$stats = isset($result['stats']) ? $result['stats'] : [];
$total_updated = isset($result['total_updated']) ? $result['total_updated'] : 0;
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12">
            <h4>
                <?php if ($success): ?>
                    <i class="fas fa-check-circle text-success"></i>
                    Renommage d'utilisateur réussi
                <?php else: ?>
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    Erreur lors du renommage d'utilisateur
                <?php endif; ?>
            </h4>
        </div>
    </div>

    <!-- Message de résultat -->
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i>
        <strong>Succès !</strong> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php else: ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Erreur !</strong> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <!-- Résumé de l'opération -->
    <div class="card mb-3">
        <div class="card-header bg-success text-white">
            <i class="fas fa-info-circle"></i>
            Résumé de l'opération
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-3"><strong>Ancien identifiant :</strong></div>
                <div class="col-md-9"><code><?= htmlspecialchars($old_mlogin) ?></code></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-3"><strong>Nouvel identifiant :</strong></div>
                <div class="col-md-9"><code class="text-success"><?= htmlspecialchars($new_mlogin) ?></code></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-3"><strong>Effectué par :</strong></div>
                <div class="col-md-9"><?= htmlspecialchars($performed_by) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-3"><strong>Date et heure :</strong></div>
                <div class="col-md-9"><?= date('d/m/Y H:i:s') ?></div>
            </div>
            <div class="row">
                <div class="col-md-3"><strong>Total modifié :</strong></div>
                <div class="col-md-9"><span class="badge bg-primary"><?= $total_updated ?> enregistrements</span></div>
            </div>
        </div>
    </div>

    <!-- Détails par table -->
    <?php if (!empty($stats)): ?>
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-database"></i>
            Détails des modifications par table
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Table</th>
                            <th>Colonne</th>
                            <th class="text-end">Enregistrements modifiés</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats as $table => $columns): ?>
                            <?php foreach ($columns as $column => $count): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($table) ?></code></td>
                                <td><code><?= htmlspecialchars($column) ?></code></td>
                                <td class="text-end"><span class="badge bg-secondary"><?= $count ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2" class="text-end">Total :</th>
                            <th class="text-end"><span class="badge bg-primary"><?= $total_updated ?></span></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Information sur la traçabilité -->
    <div class="alert alert-info">
        <i class="fas fa-file-alt"></i>
        <strong>Traçabilité :</strong> Cette opération a été enregistrée dans les journaux système.
        Les administrateurs peuvent consulter les logs pour plus de détails.
    </div>

    <!-- Actions -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-directions"></i>
            Que faire maintenant ?
        </div>
        <div class="card-body">
            <p>L'identifiant de l'utilisateur a été modifié avec succès. Vous pouvez maintenant :</p>
            <div class="d-flex gap-2">
                <a href="<?= controller_url('membre/edit/' . $new_mlogin) ?>" class="btn btn-primary">
                    <i class="fas fa-user-edit"></i>
                    Voir la fiche membre
                </a>
                <a href="<?= controller_url('membre/page') ?>" class="btn btn-secondary">
                    <i class="fas fa-list"></i>
                    Liste des membres
                </a>
                <a href="<?= controller_url('membre/renommer') ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i>
                    Renommer un autre utilisateur
                </a>
                <a href="<?= controller_url('welcome') ?>" class="btn btn-secondary">
                    <i class="fas fa-home"></i>
                    Tableau de bord
                </a>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- En cas d'erreur, proposer de réessayer -->
    <div class="card border-danger">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-redo"></i>
            Que faire ?
        </div>
        <div class="card-body">
            <p>Le renommage n'a pas pu être effectué. Aucune modification n'a été apportée à la base de données (transaction annulée).</p>

            <div class="d-flex gap-2">
                <a href="<?= controller_url('membre/renommer') ?>" class="btn btn-warning">
                    <i class="fas fa-redo"></i>
                    Réessayer
                </a>
                <a href="<?= controller_url('welcome') ?>" class="btn btn-secondary">
                    <i class="fas fa-home"></i>
                    Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
