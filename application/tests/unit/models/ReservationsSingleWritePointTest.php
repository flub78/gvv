<?php

use PHPUnit\Framework\TestCase;

/**
 * @feature Réservations
 *
 * Garde-fou du point d'écriture unique : en dehors du modèle des réservations,
 * aucun code de l'application n'écrit directement dans la table des
 * réservations. Sinon les rappels (et plus tard l'agenda Google) ne seraient
 * pas prévenus du changement.
 */
class ReservationsSingleWritePointTest extends TestCase
{
    /** Fichiers autorisés à écrire dans la table, relatifs à application/ */
    private const ALLOWED = [
        'models/reservations_model.php',
    ];

    /** Répertoires ignorés : historique des schémas, tests, journaux, bibliothèques externes */
    private const SKIPPED_DIRS = ['migrations', 'tests', 'logs', 'third_party', 'cache'];

    /**
     * Motifs d'écriture directe dans la table reservations :
     * Active Record (update/insert/delete/replace/…('reservations'…) et SQL brut.
     */
    private const PATTERNS = [
        '/->\s*(update|insert|delete|replace|insert_batch|update_batch|truncate|empty_table)\s*\(\s*[\'"]reservations[\'"]/i',
        '/\b(UPDATE|INSERT\s+INTO|DELETE\s+FROM|REPLACE\s+INTO|TRUNCATE(\s+TABLE)?)\s+`?reservations`?\b/i',
    ];

    private function _scan(): array
    {
        $root = realpath(APPPATH);
        $found = [];
        $it = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                function ($file) {
                    return !($file->isDir() && in_array($file->getFilename(), self::SKIPPED_DIRS, true));
                }
            )
        );
        foreach ($it as $file) {
            // Fichiers non lisibles par l'utilisateur des tests (clé de chiffrement
            // réservée au serveur web) : ce ne sont pas des sources de l'application
            if ($file->getExtension() !== 'php' || !is_readable($file->getPathname())) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            if (in_array($relative, self::ALLOWED, true)) {
                continue;
            }
            foreach (file($file->getPathname()) as $num => $line) {
                foreach (self::PATTERNS as $pattern) {
                    if (preg_match($pattern, $line)) {
                        $found[] = $relative . ':' . ($num + 1) . ' ' . trim($line);
                    }
                }
            }
        }
        return $found;
    }

    /**
     * Scénario: Toute modification d'une réservation passe par un seul point
     *   Étant donné le code de l'application
     *   Quand on recherche les écritures dans la table des réservations
     *   Alors elles se trouvent toutes dans le modèle des réservations
     *   Et aucun écran ne modifie une réservation sans que les rappels en soient prévenus
     */
    public function testNoDirectWriteToReservationsOutsideModel()
    {
        $this->assertSame(
            [],
            $this->_scan(),
            "Écriture directe dans la table reservations hors de Reservations_model : passez par create_reservation(), update_reservation(), delete_reservation() ou reassign_member()."
        );
    }

    /**
     * Scénario: Le garde-fou repère une écriture directe
     *   Étant donné une ligne de code qui modifie directement une réservation
     *   Quand le garde-fou l'examine
     *   Alors il la signale
     */
    public function testPatternsDetectDirectWrites()
    {
        $samples = [
            "\$this->db->update('reservations', \$data, array('id' => 1));",
            "\$this->db->where('id', 1)->delete(\"reservations\");",
            "\$this->db->insert('reservations', \$data);",
            "\$this->db->query(\"UPDATE reservations SET notes = ''\");",
            "\$this->db->query('DELETE FROM `reservations` WHERE id = 1');",
        ];
        foreach ($samples as $sample) {
            $matched = false;
            foreach (self::PATTERNS as $pattern) {
                $matched = $matched || preg_match($pattern, $sample);
            }
            $this->assertTrue((bool) $matched, "Non détecté : $sample");
        }
        foreach (["\$this->db->get_where('reservations', array('id' => 1));", "SELECT * FROM reservations"] as $read) {
            foreach (self::PATTERNS as $pattern) {
                $this->assertSame(0, preg_match($pattern, $read), "Lecture signalée à tort : $read");
            }
        }
    }
}
