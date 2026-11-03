<?php

use PHPUnit\Framework\TestCase;

/**
 * @feature Sauvegarde et migrations
 *
 * Scripts d'envoi hors-site et de rétention des sauvegardes (tools/).
 * rclone et curl sont remplacés par de faux exécutables qui notent leurs appels :
 * aucun accès réseau ni au stockage distant réel. Tout est créé dans un dossier
 * temporaire supprimé à la fin de chaque test.
 */
class RcloneOffsiteScriptsTest extends TestCase
{
    private $dir;
    private $upload;
    private $retention;

    protected function setUp(): void
    {
        $this->upload = realpath(APPPATH . '../tools/rclone_offsite_upload.sh');
        $this->retention = realpath(APPPATH . '../tools/rclone_safe_retention.sh');
        $this->dir = sys_get_temp_dir() . '/gvv_rclone_test_' . uniqid();
        mkdir($this->dir . '/backups', 0777, TRUE);
        $this->fake_rclone(0, '');
        // Faux curl : note l'URL appelée (dernier argument) et le corps reçu.
        $this->write_exec('curl', "#!/bin/bash\necho \"\${@: -1}\" >> \"" . $this->dir . "/curl.calls\"\ncat > /dev/null\nexit 0\n");
    }

    protected function tearDown(): void
    {
        if ($this->dir && is_dir($this->dir)) {
            exec('rm -rf ' . escapeshellarg($this->dir));
        }
    }

    private function write_exec($name, $content)
    {
        file_put_contents($this->dir . '/' . $name, $content);
        chmod($this->dir . '/' . $name, 0755);
    }

    /**
     * Faux rclone : note ses arguments, affiche $lsf_output pour « lsf », sort avec $rc.
     */
    private function fake_rclone($rc, $lsf_output, $delete_rc = 0)
    {
        $this->write_exec('rclone', "#!/bin/bash\n"
            . "echo \"\$*\" >> \"" . $this->dir . "/rclone.calls\"\n"
            . "case \" \$* \" in\n"
            . "  *' lsf '*) printf '%s' " . escapeshellarg($lsf_output) . "; exit $rc ;;\n"
            . "  *' delete '*) exit $delete_rc ;;\n"
            . "esac\n"
            . "[ $rc -ne 0 ] && echo 'couldn'\"'\"'t fetch token - invalid_grant' >&2\n"
            . "exit $rc\n");
    }

    private function backup($name, $age_hours = 1, $subdir = 'backups')
    {
        if (!is_dir($this->dir . '/' . $subdir)) {
            mkdir($this->dir . '/' . $subdir, 0777, TRUE);
        }
        $path = $this->dir . '/' . $subdir . '/' . $name;
        file_put_contents($path, 'x');
        touch($path, time() - $age_hours * 3600);
        return $path;
    }

    private function run_script($script, array $args, $extra_env = '')
    {
        $env = 'RCLONE=' . escapeshellarg($this->dir . '/rclone') . ' CURL=' . escapeshellarg($this->dir . '/curl')
            . ($extra_env !== '' ? ' ' . $extra_env : '');
        exec($env . ' ' . escapeshellarg($script) . ' ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1', $out, $rc);
        return $rc;
    }

    private function read($name)
    {
        $file = $this->dir . '/' . $name;
        return is_file($file) ? file_get_contents($file) : '';
    }

    private function upload_args()
    {
        return array($this->dir . '/backups/media_backup_*.tar.gz', 'gdrive:GVV_backups/media/',
            $this->dir . '/log.txt', 'https://hc-ping.com/uuid-test');
    }

    /**
     * Scénario: La sauvegarde du jour est envoyée et la supervision en est informée
     *   Étant donné plusieurs sauvegardes locales des médias, dont une de cette nuit
     *   Quand l'envoi hors-site quotidien s'exécute
     *   Alors seule la sauvegarde la plus récente est envoyée
     *   Et le service de supervision reçoit un signal de succès
     */
    public function testUpload_SendsLatestBackupAndPingsSuccess()
    {
        $this->backup('media_backup_old.tar.gz', 30);
        $latest = $this->backup('media_backup_new.tar.gz', 1);

        $this->assertSame(0, $this->run_script($this->upload, $this->upload_args()));
        $this->assertStringContainsString('copy ' . $latest . ' gdrive:GVV_backups/media/', $this->read('rclone.calls'));
        $this->assertSame("https://hc-ping.com/uuid-test\n", $this->read('curl.calls'));
        $this->assertStringContainsString('envoi OK : media_backup_new.tar.gz', $this->read('log.txt'));
    }

    /**
     * Scénario: Un envoi refusé par le stockage distant déclenche une alerte
     *   Étant donné une sauvegarde locale de cette nuit
     *   Et une autorisation d'accès au stockage distant expirée
     *   Quand l'envoi hors-site s'exécute
     *   Alors le journal contient une alerte qui évoque le jeton expiré
     *   Et le service de supervision reçoit un signal d'échec
     */
    public function testUpload_RcloneFailurePingsFail()
    {
        $this->backup('media_backup_new.tar.gz', 1);
        $this->fake_rclone(1, '');

        $this->assertSame(1, $this->run_script($this->upload, $this->upload_args()));
        $this->assertSame("https://hc-ping.com/uuid-test/fail\n", $this->read('curl.calls'));
        $log = $this->read('log.txt');
        $this->assertStringContainsString('ALERTE : envoi hors-site ECHOUE', $log);
        $this->assertStringContainsString('invalid_grant', $log);
    }

    /**
     * Scénario: Une sauvegarde locale qui ne se fait plus est détectée
     *   Étant donné une sauvegarde locale vieille de trois jours, et aucune plus récente
     *   Quand l'envoi hors-site s'exécute
     *   Alors rien n'est envoyé
     *   Et une alerte signale que la sauvegarde locale ne tourne plus
     *   Et le service de supervision reçoit un signal d'échec
     */
    public function testUpload_StaleLocalBackupIsAnAlert()
    {
        $this->backup('media_backup_old.tar.gz', 72);

        $this->assertSame(1, $this->run_script($this->upload, $this->upload_args()));
        $this->assertSame('', $this->read('rclone.calls'));
        $this->assertStringContainsString('ne tourne plus', $this->read('log.txt'));
        $this->assertSame("https://hc-ping.com/uuid-test/fail\n", $this->read('curl.calls'));
    }

    /**
     * Scénario: L'absence de toute sauvegarde locale est signalée
     *   Étant donné aucune sauvegarde locale des médias
     *   Quand l'envoi hors-site s'exécute
     *   Alors une alerte signale qu'aucune sauvegarde ne correspond
     *   Et le service de supervision reçoit un signal d'échec
     */
    public function testUpload_NoBackupIsAnAlert()
    {
        $this->assertSame(1, $this->run_script($this->upload, $this->upload_args()));
        $this->assertStringContainsString('aucune sauvegarde locale', $this->read('log.txt'));
        $this->assertSame("https://hc-ping.com/uuid-test/fail\n", $this->read('curl.calls'));
    }

    /**
     * Scénario: Sans service de supervision configuré, l'envoi fonctionne quand même
     *   Étant donné une sauvegarde locale de cette nuit et aucun service de supervision indiqué
     *   Quand l'envoi hors-site s'exécute
     *   Alors la sauvegarde est envoyée et aucun signal n'est émis
     */
    public function testUpload_WithoutPingUrl()
    {
        $this->backup('media_backup_new.tar.gz', 1);
        $args = $this->upload_args();
        array_pop($args);

        $this->assertSame(0, $this->run_script($this->upload, $args));
        $this->assertSame('', $this->read('curl.calls'));
    }

    /**
     * Scénario: Un seuil d'ancienneté mal saisi déclenche une alerte au lieu de désactiver le contrôle
     *   Étant donné une sauvegarde locale vieille de cinq jours
     *   Et un seuil d'ancienneté qui n'est pas un nombre d'heures
     *   Quand l'envoi hors-site s'exécute
     *   Alors rien n'est envoyé
     *   Et une alerte signale le seuil invalide
     *   Et le service de supervision reçoit un signal d'échec
     */
    public function testUpload_InvalidMaxAgeIsAnAlert()
    {
        $this->backup('media_backup_old.tar.gz', 120);

        $this->assertSame(1, $this->run_script($this->upload, $this->upload_args(), 'MAX_AGE_HOURS=abc'));
        $this->assertSame('', $this->read('rclone.calls'));
        $this->assertStringContainsString("MAX_AGE_HOURS='abc'", $this->read('log.txt'));
        $this->assertSame("https://hc-ping.com/uuid-test/fail\n", $this->read('curl.calls'));
    }

    /**
     * Scénario: Les sauvegardes d'un dossier dont le chemin contient des espaces sont trouvées
     *   Étant donné un dossier de sauvegardes dont le chemin contient des espaces
     *   Et une sauvegarde de cette nuit dans ce dossier
     *   Quand l'envoi hors-site s'exécute
     *   Alors cette sauvegarde est envoyée
     */
    public function testUpload_DirectoryWithSpaces()
    {
        $latest = $this->backup('media_backup_new.tar.gz', 1, 'mes sauvegardes');
        $args = $this->upload_args();
        $args[0] = $this->dir . '/mes sauvegardes/media_backup_*.tar.gz';

        $this->assertSame(0, $this->run_script($this->upload, $args));
        $this->assertStringContainsString('copy ' . $latest . ' gdrive:', $this->read('rclone.calls'));
    }

    /**
     * Scénario: Une adresse de supervision terminée par une barre oblique fonctionne
     *   Étant donné une adresse de supervision saisie avec une barre oblique finale
     *   Et aucune sauvegarde locale
     *   Quand l'envoi hors-site s'exécute
     *   Alors le signal d'échec part vers l'adresse d'échec correcte du service
     */
    public function testUpload_PingUrlWithTrailingSlash()
    {
        $args = $this->upload_args();
        $args[3] = 'https://hc-ping.com/uuid-test/';

        $this->assertSame(1, $this->run_script($this->upload, $args));
        $this->assertSame("https://hc-ping.com/uuid-test/fail\n", $this->read('curl.calls'));
    }

    /**
     * Scénario: Les anciennes copies distantes sont purgées quand une copie récente existe
     *   Étant donné une copie distante de moins de trois jours
     *   Quand la purge quotidienne s'exécute
     *   Alors les copies de plus de trois jours sont supprimées
     */
    public function testRetention_PurgesWhenRecentCopyExists()
    {
        $this->fake_rclone(0, "media_backup_new.tar.gz\n");

        $this->assertSame(0, $this->run_script($this->retention, array('gdrive:GVV_backups/media/', $this->dir . '/log.txt')));
        // Seuls les fichiers comptent comme copies récentes, pas les sous-dossiers.
        $this->assertStringContainsString('lsf --files-only --max-age 3d gdrive:GVV_backups/media/', $this->read('rclone.calls'));
        $this->assertStringContainsString('--min-age 3d delete gdrive:GVV_backups/media/', $this->read('rclone.calls'));
        $this->assertStringContainsString('purge OK', $this->read('log.txt'));
    }

    /**
     * Scénario: Une erreur d'accès au stockage distant n'est pas confondue avec une absence de copie
     *   Étant donné une autorisation d'accès au stockage distant expirée
     *   Quand la purge quotidienne s'exécute
     *   Alors rien n'est supprimé
     *   Et l'alerte désigne l'erreur d'accès, pas une sauvegarde manquante
     */
    public function testRetention_RcloneErrorIsReportedAsSuch()
    {
        $this->fake_rclone(1, '');

        $this->assertSame(1, $this->run_script($this->retention, array('gdrive:GVV_backups/media/', $this->dir . '/log.txt')));
        $this->assertStringNotContainsString('delete', $this->read('rclone.calls'));
        $log = $this->read('log.txt');
        $this->assertStringContainsString('erreur rclone', $log);
        $this->assertStringNotContainsString('aucune sauvegarde', $log);
    }

    /**
     * Scénario: La dernière copie distante n'est jamais supprimée si les envois ont cessé
     *   Étant donné aucune copie distante de moins de trois jours
     *   Quand la purge quotidienne s'exécute
     *   Alors rien n'est supprimé
     *   Et une alerte signale que les envois ne se font plus
     */
    public function testRetention_NoRecentCopyCancelsPurge()
    {
        $this->fake_rclone(0, '');

        $this->assertSame(1, $this->run_script($this->retention, array('gdrive:GVV_backups/media/', $this->dir . '/log.txt')));
        $this->assertStringNotContainsString('delete', $this->read('rclone.calls'));
        $this->assertStringContainsString('aucune sauvegarde distante de moins de 3 jours', $this->read('log.txt'));
    }
}
