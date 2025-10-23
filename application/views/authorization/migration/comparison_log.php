<!-- VIEW: application/views/authorization/migration/comparison_log.php -->
<?php
/**
 * Migration Dashboard - Comparison Log Tab
 *
 * Displays authorization comparison log showing discrepancies
 * between old and new systems.
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
    <title>Journal de Comparaison - Migration GVV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        .migration-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .mismatch-row {
            background-color: #fff3cd !important;
            border-left: 4px solid #dc3545;
        }
        .match-row {
            border-left: 4px solid #198754;
        }
        .result-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .result-granted {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .result-denied {
            background-color: #f8d7da;
            color: #842029;
        }
        .details-json {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85rem;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="migration-header">
    <div class="container">
        <h1><i class="bi bi-journal-text"></i> Journal de Comparaison des Autorisations</h1>
        <p class="mb-0">Comparaison entre systèmes ancien et nouveau</p>
    </div>
</div>

<div class="container">
    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo site_url('authorization/migration'); ?>">
                <i class="bi bi-speedometer2"></i> Vue d'ensemble
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo site_url('authorization/migration_pilot_users'); ?>">
                <i class="bi bi-people"></i> Utilisateurs pilotes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="<?php echo site_url('authorization/migration_comparison_log'); ?>">
                <i class="bi bi-journal-text"></i> Journal de comparaison
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo site_url('authorization/migration_statistics'); ?>">
                <i class="bi bi-graph-up"></i> Statistiques
            </a>
        </li>
    </ul>

    <!-- Alert Banner -->
    <?php if ($recent_mismatches > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        <strong><?php echo $recent_mismatches; ?></strong> divergence(s) détectée(s) dans les dernières 24h
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Advanced Search -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-search"></i> Recherche Avancée</h5>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo site_url('authorization/migration_comparison_log'); ?>">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="user_id" class="form-label">Utilisateur</label>
                        <select class="form-select" name="user_id" id="user_id">
                            <option value="">Tous</option>
                            <?php foreach ($pilot_users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo ($this->input->get('user_id') == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="controller" class="form-label">Contrôleur</label>
                        <input type="text" class="form-control" name="controller" id="controller"
                               value="<?php echo htmlspecialchars($this->input->get('controller') ?? ''); ?>"
                               placeholder="Ex: membres">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="action" class="form-label">Action</label>
                        <input type="text" class="form-control" name="action" id="action"
                               value="<?php echo htmlspecialchars($this->input->get('action') ?? ''); ?>"
                               placeholder="Ex: edit">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="date_from" class="form-label">Du</label>
                        <input type="date" class="form-control" name="date_from" id="date_from"
                               value="<?php echo htmlspecialchars($this->input->get('date_from') ?? ''); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="date_to" class="form-label">Au</label>
                        <input type="date" class="form-control" name="date_to" id="date_to"
                               value="<?php echo htmlspecialchars($this->input->get('date_to') ?? ''); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="mismatches_only" id="mismatches_only" value="1"
                                   <?php echo ($this->input->get('mismatches_only') === '1') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="mismatches_only">
                                Divergences seulement
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Rechercher
                        </button>
                        <a href="<?php echo site_url('authorization/migration_comparison_log'); ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Comparison Log Table -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="bi bi-list-ul"></i> Journal de Comparaison
                <span class="badge bg-secondary"><?php echo count($comparison_logs); ?> entrée(s)</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="comparisonTable">
                    <thead class="table-light">
                        <tr>
                            <th>Date/Heure</th>
                            <th>Utilisateur</th>
                            <th>Contrôleur</th>
                            <th>Action</th>
                            <th>Ancien Système</th>
                            <th>Nouveau Système</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comparison_logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox"></i> Aucune entrée trouvée
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($comparison_logs as $log): ?>
                            <?php
                            $is_mismatch = $log['new_system_result'] != $log['legacy_system_result'];
                            $row_class = $is_mismatch ? 'mismatch-row' : 'match-row';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td>
                                    <small><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['username']); ?></strong><br>
                                    <small class="text-muted">ID: <?php echo $log['user_id']; ?></small>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($log['controller']); ?></code>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($log['action']); ?></code>
                                </td>
                                <td>
                                    <?php if ($log['legacy_system_result']): ?>
                                        <span class="result-badge result-granted">
                                            <i class="bi bi-check-circle"></i> Accordé
                                        </span>
                                    <?php else: ?>
                                        <span class="result-badge result-denied">
                                            <i class="bi bi-x-circle"></i> Refusé
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['new_system_result']): ?>
                                        <span class="result-badge result-granted">
                                            <i class="bi bi-check-circle"></i> Accordé
                                        </span>
                                    <?php else: ?>
                                        <span class="result-badge result-denied">
                                            <i class="bi bi-x-circle"></i> Refusé
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"
                                            onclick="showDetails(<?php echo htmlspecialchars(json_encode($log)); ?>)">
                                        <i class="bi bi-eye"></i> Détails
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Export Actions -->
    <div class="mb-5">
        <button class="btn btn-success" onclick="alert('Export CSV non implémenté')">
            <i class="bi bi-download"></i> Exporter CSV
        </button>
        <button class="btn btn-danger" onclick="if(confirm('Supprimer les logs de plus de 30 jours?')) alert('Purge non implémentée')">
            <i class="bi bi-trash"></i> Purger anciens logs
        </button>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailsModalLabel">
                    <i class="bi bi-info-circle"></i> Détails de la Comparaison
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Utilisateur:</strong> <span id="detailUsername"></span> (ID: <span id="detailUserId"></span>)
                    </div>
                    <div class="col-md-6">
                        <strong>Date:</strong> <span id="detailDate"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Contrôleur:</strong> <code id="detailController"></code>
                    </div>
                    <div class="col-md-6">
                        <strong>Action:</strong> <code id="detailAction"></code>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0">Ancien Système (DX_Auth)</h6>
                            </div>
                            <div class="card-body">
                                <h5 id="detailLegacyResult"></h5>
                                <div class="details-json" id="detailLegacyDetails"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Nouveau Système (Gvv_Authorization)</h6>
                            </div>
                            <div class="card-body">
                                <h5 id="detailNewResult"></h5>
                                <div class="details-json" id="detailNewDetails"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="analysisSection" class="mt-3" style="display: none;">
                    <hr>
                    <div class="alert alert-warning">
                        <h6><i class="bi bi-exclamation-triangle"></i> ANALYSE:</h6>
                        <p id="analysisText"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
let detailsModal = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#comparisonTable').DataTable({
        order: [[0, 'desc']], // Sort by date descending
        pageLength: 25,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
        }
    });

    // Initialize modal
    detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
});

function showDetails(log) {
    // Populate basic info
    document.getElementById('detailUsername').textContent = log.username;
    document.getElementById('detailUserId').textContent = log.user_id;
    document.getElementById('detailDate').textContent = log.created_at;
    document.getElementById('detailController').textContent = log.controller;
    document.getElementById('detailAction').textContent = log.action;

    // Legacy system result
    const legacyResultHtml = log.legacy_system_result == 1
        ? '<span class="result-badge result-granted"><i class="bi bi-check-circle"></i> ACCÈS ACCORDÉ</span>'
        : '<span class="result-badge result-denied"><i class="bi bi-x-circle"></i> ACCÈS REFUSÉ</span>';
    document.getElementById('detailLegacyResult').innerHTML = legacyResultHtml;

    // New system result
    const newResultHtml = log.new_system_result == 1
        ? '<span class="result-badge result-granted"><i class="bi bi-check-circle"></i> ACCÈS ACCORDÉ</span>'
        : '<span class="result-badge result-denied"><i class="bi bi-x-circle"></i> ACCÈS REFUSÉ</span>';
    document.getElementById('detailNewResult').innerHTML = newResultHtml;

    // Parse and display JSON details
    try {
        if (log.legacy_system_details) {
            const legacyDetails = JSON.parse(log.legacy_system_details);
            document.getElementById('detailLegacyDetails').textContent = JSON.stringify(legacyDetails, null, 2);
        }
    } catch (e) {
        document.getElementById('detailLegacyDetails').textContent = log.legacy_system_details || 'N/A';
    }

    try {
        if (log.new_system_details) {
            const newDetails = JSON.parse(log.new_system_details);
            document.getElementById('detailNewDetails').textContent = JSON.stringify(newDetails, null, 2);
        }
    } catch (e) {
        document.getElementById('detailNewDetails').textContent = log.new_system_details || 'N/A';
    }

    // Show analysis if there's a mismatch
    if (log.new_system_result != log.legacy_system_result) {
        document.getElementById('analysisSection').style.display = 'block';
        const analysisText = 'Divergence détectée: le résultat diffère entre les deux systèmes. ' +
                           'Vérifiez les permissions et règles d\'accès dans le nouveau système.';
        document.getElementById('analysisText').textContent = analysisText;
    } else {
        document.getElementById('analysisSection').style.display = 'none';
    }

    detailsModal.show();
}
</script>

</body>
</html>
