<!-- VIEW: application/views/authorization/migration/overview.php -->
<?php
/**
 * Migration Dashboard - Overview Tab
 *
 * Displays migration status summary, pilot users, and alerts.
 * Part of Phase 6: Progressive Migration
 *
 * @see /doc/plans_and_progress/phase6_migration_dashboard_mockups.md
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration du système d'autorisation - GVV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .migration-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .alert-item {
            border-left: 4px solid #dc3545;
            margin-bottom: 0.5rem;
        }
        .pilot-user-row {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }
        .pilot-user-row:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-in_progress { background-color: #0dcaf0; color: #000; }
        .status-completed { background-color: #198754; color: #fff; }
        .status-failed { background-color: #dc3545; color: #fff; }
    </style>
</head>
<body>

<div class="migration-header">
    <div class="container">
        <h1><i class="bi bi-shield-lock"></i> Migration du système d'autorisation</h1>
        <p class="mb-0">Migration progressive de DX_Auth vers Gvv_Authorization</p>
    </div>
</div>

<div class="container">
    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" href="<?php echo site_url('authorization/migration'); ?>">
                <i class="bi bi-speedometer2"></i> Vue d'ensemble
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo site_url('authorization/migration_pilot_users'); ?>">
                <i class="bi bi-people"></i> Utilisateurs pilotes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo site_url('authorization/migration_comparison_log'); ?>">
                <i class="bi bi-journal-text"></i> Journal de comparaison
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo site_url('authorization/migration_statistics'); ?>">
                <i class="bi bi-graph-up"></i> Statistiques
            </a>
        </li>
    </ul>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <h6 class="card-subtitle mb-2 text-muted">Utilisateurs Total</h6>
                    <div class="stat-number text-primary"><?php echo $total_users; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <h6 class="card-subtitle mb-2 text-muted">En Migration</h6>
                    <div class="stat-number text-info"><?php echo $in_progress_count; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <h6 class="card-subtitle mb-2 text-muted">Migrés</h6>
                    <div class="stat-number text-success"><?php echo $migrated_count; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Progression globale</h5>
            <div class="progress" style="height: 30px;">
                <div class="progress-bar bg-success" role="progressbar"
                     style="width: <?php echo $progress_percentage; ?>%;"
                     aria-valuenow="<?php echo $progress_percentage; ?>"
                     aria-valuemin="0"
                     aria-valuemax="100">
                    <?php echo $progress_percentage; ?>%
                </div>
            </div>
            <small class="text-muted mt-2 d-block">
                <?php echo $migrated_count; ?> utilisateurs sur <?php echo $total_users; ?> migrés
            </small>
        </div>
    </div>

    <!-- Pilot Users Summary -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-people-fill"></i> Utilisateurs Pilotes (Test)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Statut</th>
                            <th>Depuis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pilot_users)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="bi bi-inbox"></i> Aucun utilisateur pilote trouvé
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($pilot_users as $user): ?>
                            <tr>
                                <td>
                                    <i class="bi bi-person-circle"></i>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php
                                    $status = $user['migration_status'] ?? 'pending';
                                    $status_labels = array(
                                        'pending' => '<span class="status-badge status-pending"><i class="bi bi-pause-circle"></i> En attente</span>',
                                        'in_progress' => '<span class="status-badge status-in_progress"><i class="bi bi-play-circle"></i> En cours</span>',
                                        'completed' => '<span class="status-badge status-completed"><i class="bi bi-check-circle"></i> Terminé</span>',
                                        'failed' => '<span class="status-badge status-failed"><i class="bi bi-x-circle"></i> Échoué</span>'
                                    );
                                    echo $status_labels[$status] ?? $status_labels['pending'];
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($user['migrated_at']) {
                                        echo date('d/m/Y H:i', strtotime($user['migrated_at']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Alerts and Warnings -->
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Avertissements et Erreurs</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recent_alerts)): ?>
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle"></i> Aucun problème détecté
                </div>
            <?php else: ?>
                <?php foreach ($recent_alerts as $alert): ?>
                <div class="alert alert-danger alert-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?php echo htmlspecialchars($alert['username']); ?></strong>
                            <span class="text-muted">•</span>
                            <code><?php echo htmlspecialchars($alert['controller'] . '/' . $alert['action']); ?></code>
                        </div>
                        <small class="text-muted"><?php echo date('d/m H:i', strtotime($alert['created_at'])); ?></small>
                    </div>
                    <small class="d-block mt-1">
                        Ancien système: <?php echo $alert['legacy_system_result'] ? '✅ Accordé' : '❌ Refusé'; ?>
                        •
                        Nouveau système: <?php echo $alert['new_system_result'] ? '✅ Accordé' : '❌ Refusé'; ?>
                    </small>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="text-center mb-5">
        <a href="<?php echo site_url('authorization/migration_pilot_users'); ?>" class="btn btn-primary btn-lg">
            <i class="bi bi-rocket-takeoff"></i> Démarrer la migration pilote
        </a>
        <a href="<?php echo site_url('authorization'); ?>" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left"></i> Retour au tableau de bord
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
