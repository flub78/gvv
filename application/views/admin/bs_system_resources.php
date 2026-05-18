<!-- VIEW: application/views/admin/bs_system_resources.php -->
<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 * Vue des ressources système du serveur.
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('admin');

function sysres_fmt_bytes($bytes) {
    if ($bytes === null || $bytes < 0) return '—';
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' Go';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 1)    . ' Mo';
    if ($bytes >= 1024)       return round($bytes / 1024, 0)       . ' Ko';
    return $bytes . ' o';
}

function sysres_bar_class($pct) {
    if ($pct >= 90) return 'bg-danger';
    if ($pct >= 75) return 'bg-warning';
    return 'bg-success';
}
?>

<style>
.sysres-card {
    border-radius: 8px;
    margin-bottom: 1.25rem;
}
.sysres-label {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.15rem;
}
.sysres-value {
    font-size: 1.1rem;
    font-weight: 600;
}
.metric-row td {
    padding: 0.3rem 0.5rem;
    font-size: 0.9rem;
}
</style>

<div id="body" class="body container-fluid py-3">

    <!-- En-tête -->
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-server text-info"></i>
                <?= $this->lang->line('gvv_sysres_title') ?>
            </h2>
            <a href="<?= controller_url('welcome/section/admin_sys') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_back') ?>
            </a>
        </div>
    </div>

    <?php $flash_ok  = $this->session->flashdata('sysres_success'); ?>
    <?php $flash_err = $this->session->flashdata('sysres_error'); ?>
    <?php if ($flash_ok) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= $flash_ok ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($flash_err) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= $flash_err ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3">

        <!-- ===================== Espace disque ===================== -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card sysres-card h-100">
                <div class="card-header">
                    <i class="fas fa-hdd text-primary"></i>
                    <strong><?= $this->lang->line('gvv_sysres_disk') ?></strong>
                </div>
                <div class="card-body">
                    <?php if (isset($disk) && $disk['total'] > 0) : ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="sysres-label"><?= sysres_fmt_bytes($disk['used']) ?> utilisés / <?= sysres_fmt_bytes($disk['total']) ?></span>
                            <span class="fw-bold <?= $disk['pct'] >= 90 ? 'text-danger' : ($disk['pct'] >= 75 ? 'text-warning' : 'text-success') ?>">
                                <?= $disk['pct'] ?> %
                            </span>
                        </div>
                        <div class="progress mb-3" style="height: 18px;">
                            <div class="progress-bar <?= sysres_bar_class($disk['pct']) ?>"
                                 role="progressbar"
                                 style="width: <?= $disk['pct'] ?>%"
                                 aria-valuenow="<?= $disk['pct'] ?>"
                                 aria-valuemin="0" aria-valuemax="100">
                                <?= $disk['pct'] ?> %
                            </div>
                        </div>
                        <table class="table table-sm mb-0 metric-row">
                            <tr>
                                <td class="text-muted">Espace total</td>
                                <td class="fw-semibold"><?= sysres_fmt_bytes($disk['total']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Espace utilisé</td>
                                <td class="fw-semibold"><?= sysres_fmt_bytes($disk['used']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Espace libre</td>
                                <td class="fw-semibold text-success"><?= sysres_fmt_bytes($disk['free']) ?></td>
                            </tr>
                        </table>
                    <?php else : ?>
                        <p class="text-muted">Information non disponible.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===================== Charge CPU ===================== -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card sysres-card h-100">
                <div class="card-header">
                    <i class="fas fa-microchip text-warning"></i>
                    <strong><?= $this->lang->line('gvv_sysres_cpu') ?></strong>
                </div>
                <div class="card-body">
                    <?php if (isset($load) && is_array($load)) :
                        $max_load = max($load[0], 0.01);
                        $pct1  = min(round($load[0] * 100), 100);
                        $pct5  = min(round($load[1] * 100), 100);
                        $pct15 = min(round($load[2] * 100), 100);
                    ?>
                        <table class="table table-sm mb-2 metric-row">
                            <tr>
                                <td class="text-muted">Charge 1 min</td>
                                <td>
                                    <span class="fw-semibold <?= $load[0] >= 2 ? 'text-danger' : ($load[0] >= 1 ? 'text-warning' : 'text-success') ?>">
                                        <?= number_format($load[0], 2) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Charge 5 min</td>
                                <td>
                                    <span class="fw-semibold <?= $load[1] >= 2 ? 'text-danger' : ($load[1] >= 1 ? 'text-warning' : 'text-success') ?>">
                                        <?= number_format($load[1], 2) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Charge 15 min</td>
                                <td>
                                    <span class="fw-semibold <?= $load[2] >= 2 ? 'text-danger' : ($load[2] >= 1 ? 'text-warning' : 'text-success') ?>">
                                        <?= number_format($load[2], 2) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                        <div class="sysres-label mb-1">Charge 1 / 5 / 15 min</div>
                        <div class="progress mb-1" style="height:10px;">
                            <div class="progress-bar <?= sysres_bar_class($pct1) ?>" style="width:<?= $pct1 ?>%"></div>
                        </div>
                        <div class="progress mb-1" style="height:10px;">
                            <div class="progress-bar <?= sysres_bar_class($pct5) ?>" style="width:<?= $pct5 ?>%"></div>
                        </div>
                        <div class="progress" style="height:10px;">
                            <div class="progress-bar <?= sysres_bar_class($pct15) ?>" style="width:<?= $pct15 ?>%"></div>
                        </div>
                        <div class="text-muted mt-1" style="font-size:0.75rem;">100% = charge de 1,00</div>
                    <?php else : ?>
                        <p class="text-muted">Information non disponible.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===================== Mémoire système ===================== -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card sysres-card h-100">
                <div class="card-header">
                    <i class="fas fa-memory text-info"></i>
                    <strong><?= $this->lang->line('gvv_sysres_memory') ?></strong>
                </div>
                <div class="card-body">
                    <?php if (isset($memory) && !empty($memory)) : ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="sysres-label"><?= sysres_fmt_bytes($memory['used']) ?> / <?= sysres_fmt_bytes($memory['total']) ?></span>
                            <span class="fw-bold <?= $memory['pct'] >= 90 ? 'text-danger' : ($memory['pct'] >= 75 ? 'text-warning' : 'text-success') ?>">
                                <?= $memory['pct'] ?> %
                            </span>
                        </div>
                        <div class="progress mb-3" style="height: 18px;">
                            <div class="progress-bar <?= sysres_bar_class($memory['pct']) ?>"
                                 style="width: <?= $memory['pct'] ?>%">
                                <?= $memory['pct'] ?> %
                            </div>
                        </div>
                        <table class="table table-sm mb-0 metric-row">
                            <tr>
                                <td class="text-muted">Mémoire totale</td>
                                <td class="fw-semibold"><?= sysres_fmt_bytes($memory['total']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Mémoire utilisée</td>
                                <td class="fw-semibold"><?= sysres_fmt_bytes($memory['used']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Mémoire disponible</td>
                                <td class="fw-semibold text-success"><?= sysres_fmt_bytes($memory['available']) ?></td>
                            </tr>
                        </table>
                    <?php else : ?>
                        <p class="text-muted">Information non disponible (Linux requis).</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===================== Réseau ===================== -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card sysres-card h-100">
                <div class="card-header">
                    <i class="fas fa-network-wired text-success"></i>
                    <strong><?= $this->lang->line('gvv_sysres_network') ?></strong>
                </div>
                <div class="card-body">
                    <?php if (isset($network) && !empty($network)) : ?>
                        <table class="table table-sm mb-0 metric-row">
                            <thead>
                                <tr>
                                    <th class="text-muted">Interface</th>
                                    <th class="text-muted">Reçu</th>
                                    <th class="text-muted">Émis</th>
                                    <th class="text-muted">Erreurs</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($network as $iface => $stats) : ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($iface) ?></code></td>
                                    <td><?= sysres_fmt_bytes($stats['rx_bytes']) ?></td>
                                    <td><?= sysres_fmt_bytes($stats['tx_bytes']) ?></td>
                                    <td>
                                        <?php $errs = $stats['rx_errors'] + $stats['tx_errors']; ?>
                                        <span class="<?= $errs > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                            <?= $errs ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-muted mt-2" style="font-size:0.75rem;">Cumul depuis le démarrage du serveur.</div>
                    <?php else : ?>
                        <p class="text-muted">Information non disponible (Linux requis).</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===================== PHP & Serveur ===================== -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card sysres-card h-100">
                <div class="card-header">
                    <i class="fab fa-php text-primary"></i>
                    <strong><?= $this->lang->line('gvv_sysres_php') ?></strong>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0 metric-row">
                        <tr>
                            <td class="text-muted">Version PHP</td>
                            <td class="fw-semibold"><?= htmlspecialchars($php['version']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Système</td>
                            <td style="font-size:0.8rem;"><?= htmlspecialchars($php['os']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Limite mémoire PHP</td>
                            <td class="fw-semibold"><?= htmlspecialchars($php['memory_limit']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Mémoire PHP utilisée</td>
                            <td class="fw-semibold"><?= sysres_fmt_bytes($php['memory_usage']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Pic mémoire PHP</td>
                            <td class="fw-semibold"><?= sysres_fmt_bytes($php['peak_usage']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Temps exec max</td>
                            <td class="fw-semibold"><?= htmlspecialchars($php['max_exec_time']) ?> s</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Upload max</td>
                            <td class="fw-semibold"><?= htmlspecialchars($php['upload_max']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===================== Disponibilité (Uptime) ===================== -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card sysres-card h-100">
                <div class="card-header">
                    <i class="fas fa-clock text-secondary"></i>
                    <strong><?= $this->lang->line('gvv_sysres_uptime') ?></strong>
                </div>
                <div class="card-body">
                    <?php if (isset($uptime) && $uptime !== null) : ?>
                        <div class="text-center py-2">
                            <div style="font-size: 2rem; font-weight: 700;" class="text-secondary">
                                <?= $uptime['days'] ?>j <?= $uptime['hours'] ?>h <?= $uptime['minutes'] ?>min
                            </div>
                            <div class="text-muted mt-1" style="font-size:0.85rem;">
                                Serveur en ligne depuis <?= number_format($uptime['seconds']) ?> secondes
                            </div>
                        </div>
                    <?php else : ?>
                        <p class="text-muted">Information non disponible (Linux requis).</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===================== Dernière sauvegarde ===================== -->
        <div class="col-12">
            <div class="card sysres-card">
                <div class="card-header">
                    <i class="fas fa-save text-dark"></i>
                    <strong><?= $this->lang->line('gvv_sysres_backup') ?></strong>
                </div>
                <div class="card-body">
                    <?php if (isset($last_backup) && $last_backup !== null) :
                        $backup_date = date('d/m/Y H:i', $last_backup);
                        $days_ago = (int)floor((time() - $last_backup) / 86400);
                        $is_old = $days_ago > 10;
                    ?>
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-<?= $is_old ? 'exclamation-triangle text-danger' : 'check-circle text-success' ?> fa-2x"></i>
                            <div>
                                <div class="<?= $is_old ? 'text-danger fw-bold fs-5' : 'text-success fw-bold fs-5' ?>">
                                    <?= $backup_date ?>
                                </div>
                                <div class="<?= $is_old ? 'text-danger' : 'text-muted' ?>" style="font-size:0.9rem;">
                                    <?php if ($days_ago === 0) : ?>
                                        Sauvegardé aujourd'hui
                                    <?php elseif ($days_ago === 1) : ?>
                                        Il y a 1 jour
                                    <?php else : ?>
                                        Il y a <?= $days_ago ?> jours
                                        <?php if ($is_old) : ?>
                                            — <strong>Sauvegarde en retard !</strong>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ms-auto">
                                <form method="post" action="<?= controller_url('admin/backup_server') ?>" style="display:inline;">
                                    <button type="submit" class="btn btn-primary btn-sm"
                                            onclick="return confirm('Lancer une sauvegarde automatique sur le serveur ?');">
                                        <i class="fas fa-save"></i> Sauvegarder maintenant
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                            <div>
                                <div class="text-danger fw-bold fs-5">Aucune sauvegarde trouvée</div>
                                <div class="text-muted" style="font-size:0.9rem;">Aucun fichier dans le répertoire <code>backups/</code></div>
                            </div>
                            <div class="ms-auto">
                                <form method="post" action="<?= controller_url('admin/backup_server') ?>" style="display:inline;">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Lancer une sauvegarde automatique sur le serveur ?');">
                                        <i class="fas fa-save"></i> Sauvegarder maintenant
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /.row -->
</div><!-- /.body -->
