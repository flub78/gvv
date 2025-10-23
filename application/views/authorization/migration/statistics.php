<!-- VIEW: application/views/authorization/migration/statistics.php -->
<?php
/**
 * Migration Dashboard - Statistics Tab
 *
 * Displays migration statistics, charts, and metrics.
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
    <title>Statistiques de Migration - GVV</title>
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
        .wave-progress {
            margin-bottom: 1.5rem;
        }
        .wave-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

<div class="migration-header">
    <div class="container">
        <h1><i class="bi bi-graph-up"></i> Statistiques de Migration</h1>
        <p class="mb-0">Métriques et tendances de la migration progressive</p>
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
            <a class="nav-link" href="<?php echo site_url('authorization/migration_comparison_log'); ?>">
                <i class="bi bi-journal-text"></i> Journal de comparaison
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="<?php echo site_url('authorization/migration_statistics'); ?>">
                <i class="bi bi-graph-up"></i> Statistiques
            </a>
        </li>
    </ul>

    <!-- Wave Progress -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-layers"></i> Progression par Vague</h5>
        </div>
        <div class="card-body">
            <?php
            $waves = array(
                'wave1' => array('label' => 'Vague 1 (testuser)', 'total_days' => 7),
                'wave2' => array('label' => 'Vague 2 (testplanchiste)', 'total_days' => 7),
                'wave3' => array('label' => 'Vague 3 (testadmin)', 'total_days' => 7)
            );

            foreach ($waves as $wave_key => $wave_info):
                $wave_data = $wave_progress[$wave_key] ?? null;
                $days_elapsed = 0;
                $progress = 0;

                if ($wave_data && $wave_data['migrated_at']) {
                    $migrated_date = new DateTime($wave_data['migrated_at']);
                    $now = new DateTime();
                    $interval = $migrated_date->diff($now);
                    $days_elapsed = min($interval->days, $wave_info['total_days']);
                    $progress = round(($days_elapsed / $wave_info['total_days']) * 100);
                }
            ?>
            <div class="wave-progress">
                <div class="wave-label">
                    <?php echo $wave_info['label']; ?>
                    <?php if ($wave_data && $wave_data['migration_status']): ?>
                        <span class="badge bg-info"><?php echo $wave_data['migration_status']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar <?php echo $progress == 100 ? 'bg-success' : 'bg-primary'; ?>"
                         role="progressbar"
                         style="width: <?php echo $progress; ?>%;"
                         aria-valuenow="<?php echo $progress; ?>"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        <?php echo $progress; ?>% (<?php echo $days_elapsed; ?>/<?php echo $wave_info['total_days']; ?>j)
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <h6 class="card-subtitle mb-2 text-muted">Comparaisons Totales</h6>
                    <div class="stat-number text-primary"><?php echo $total_comparisons; ?></div>
                    <small class="text-muted">7 derniers jours</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <h6 class="card-subtitle mb-2 text-muted">Divergences Détectées</h6>
                    <div class="stat-number text-danger"><?php echo $total_divergences; ?></div>
                    <small class="text-muted">7 derniers jours</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <h6 class="card-subtitle mb-2 text-muted">Taux de Concordance</h6>
                    <div class="stat-number text-success"><?php echo $concordance_rate; ?>%</div>
                    <small class="text-muted">7 derniers jours</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparison Trends Chart -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Comparaisons d'Autorisation (7 derniers jours)</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="comparisonChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Controllers with Divergences -->
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Top 5 Contrôleurs avec Divergences</h5>
        </div>
        <div class="card-body">
            <?php if (empty($top_divergences)): ?>
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle"></i> Aucune divergence détectée
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($top_divergences as $index => $item): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?php echo ($index + 1); ?>. <code><?php echo htmlspecialchars($item['controller']); ?></code></h6>
                        </div>
                        <span class="badge bg-danger rounded-pill"><?php echo $item['divergence_count']; ?> divergence(s)</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Export Report -->
    <div class="text-center mb-5">
        <button class="btn btn-success btn-lg" onclick="alert('Export PDF non implémenté')">
            <i class="bi bi-file-pdf"></i> Exporter Rapport Complet (PDF)
        </button>
        <button class="btn btn-outline-success btn-lg" onclick="alert('Export CSV non implémenté')">
            <i class="bi bi-file-excel"></i> Exporter CSV
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>

<script>
// Prepare data for Chart.js
const comparisonStats = <?php echo json_encode($comparison_stats); ?>;

const labels = comparisonStats.map(stat => {
    const date = new Date(stat.date);
    return date.toLocaleDateString('fr-FR', { month: 'short', day: 'numeric' });
});

const totalData = comparisonStats.map(stat => stat.total);
const divergenceData = comparisonStats.map(stat => stat.divergences);

// Create comparison trend chart
const ctx = document.getElementById('comparisonChart').getContext('2d');
const comparisonChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Comparaisons totales',
                data: totalData,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Divergences',
                data: divergenceData,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

</body>
</html>
