<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * MY_Migration — Extension CodeIgniter de la bibliothèque Migration
 *
 * Corrige deux cas où un échec de migration ne produit aucun diagnostic
 * exploitable :
 *  - `db_debug = FALSE` (application/config/database.php) fait que
 *    `$this->db->query()` retourne simplement FALSE sans lever d'erreur ;
 *    `CI_Migration::version()` n'examine jamais ce résultat, la migration
 *    est donc marquée réussie alors qu'une requête SQL a échoué en silence.
 *  - une exception levée explicitement dans une migration (pattern déjà
 *    utilisé, cf. 024_sections.php) remonte non interceptée. En
 *    ENVIRONMENT=production, error_reporting(0) supprime tout affichage :
 *    page blanche, aucun diagnostic visible.
 *
 * version() est réécrite à l'identique de CI_Migration::version(), avec une
 * vérification de l'état d'erreur SQL après chaque étape et une capture des
 * exceptions, pour lever dans les deux cas une exception explicite incluant
 * le fichier de migration, la méthode (up/down), le niveau visé et le détail
 * de l'erreur. application/controllers/migration.php::to_level() l'intercepte
 * et affiche ce détail via show_error() (qui fonctionne quel que soit
 * ENVIRONMENT/error_reporting).
 *
 * Le constructeur de CI_Migration ne peut pas être réutilisé via
 * parent::__construct() : son garde "Only run this constructor on main
 * library load" (`if (get_parent_class($this) !== FALSE) return;`) se
 * déclenche pour toute instance de sous-classe — y compris en appelant
 * explicitement parent::__construct() — et la classe de base n'exécute
 * alors jamais son initialisation (migration_path, table `migrations`...).
 * Il est donc dupliqué ici à l'identique (le chargement de dbforge est
 * simplement déplacé dans le bloc qui l'utilise réellement).
 */
class MY_Migration extends CI_Migration {

    public function __construct($config = array()) {
        foreach ($config as $key => $val) {
            $this->{'_' . $key} = $val;
        }

        log_message('debug', 'Migrations class initialized');

        if ($this->_migration_enabled !== TRUE) {
            show_error('Migrations has been loaded but is disabled or set up incorrectly.');
        }

        $this->_migration_path == '' AND $this->_migration_path = APPPATH . 'migrations/';
        $this->_migration_path = rtrim($this->_migration_path, '/') . '/';

        $this->lang->load('migration');

        if (! $this->db->table_exists('migrations')) {
            $this->load->dbforge();
            $this->dbforge->add_field(array(
                'version' => array('type' => 'INT', 'constraint' => 3),
            ));
            $this->dbforge->create_table('migrations', TRUE);
            $this->db->insert('migrations', array('version' => 0));
        }
    }

    /**
     * Migrate to a schema version — reprend CI_Migration::version() avec
     * vérification de l'état d'erreur SQL et capture des exceptions après
     * l'exécution de chaque étape.
     *
     * @param int $target_version
     * @return mixed TRUE si déjà au niveau visé, FALSE si échec structurel
     *               (fichier/classe/méthode manquants, voir error_string()),
     *               int si migration réalisée.
     * @throws Exception si une étape échoue (erreur SQL silencieuse ou
     *               exception levée par la migration), avec le détail.
     */
    public function version($target_version) {
        $start = $current_version = $this->_get_version();
        $stop = $target_version;

        if ($target_version > $current_version) {
            // Moving Up
            ++$start;
            ++$stop;
            $step = 1;
        } else {
            // Moving Down
            $step = -1;
        }

        $method = ($step === 1) ? 'up' : 'down';
        $migrations = array();
        $migration_files = array();

        for ($i = $start; $i != $stop; $i += $step) {
            $f = glob(sprintf($this->_migration_path . '%03d_*.php', $i));

            if (count($f) > 1) {
                $this->_error_string = sprintf($this->lang->line('migration_multiple_version'), $i);
                return FALSE;
            }

            if (count($f) == 0) {
                if ($step == 1) {
                    break;
                }
                $this->_error_string = sprintf($this->lang->line('migration_not_found'), $i);
                return FALSE;
            }

            $file = basename($f[0]);
            $name = basename($f[0], '.php');

            if (preg_match('/^\d{3}_(\w+)$/', $name, $match)) {
                $match[1] = strtolower($match[1]);

                if (in_array($match[1], $migrations)) {
                    $this->_error_string = sprintf($this->lang->line('migration_multiple_version'), $match[1]);
                    return FALSE;
                }

                include $f[0];
                $class = 'Migration_' . ucfirst($match[1]);

                if (! class_exists($class)) {
                    $this->_error_string = sprintf($this->lang->line('migration_class_doesnt_exist'), $class);
                    return FALSE;
                }

                if (! method_exists($class, $method)) {
                    $this->_error_string = sprintf($this->lang->line('migration_missing_' . $method . '_method'), $class);
                    return FALSE;
                }

                $migrations[] = $match[1];
                $migration_files[$match[1]] = $file;
            } else {
                $this->_error_string = sprintf($this->lang->line('migration_invalid_filename'), $file);
                return FALSE;
            }
        }

        log_message('debug', 'Current migration: ' . $current_version);

        $version = $i + ($step == 1 ? -1 : 0);

        if ($migrations === array()) {
            return TRUE;
        }

        log_message('debug', 'Migrating from ' . $method . ' to version ' . $version);

        foreach ($migrations AS $migration) {
            $class = 'Migration_' . ucfirst(strtolower($migration));
            $file = isset($migration_files[$migration]) ? $migration_files[$migration] : ($class . '.php');

            try {
                call_user_func(array(new $class, $method));
            } catch (Throwable $e) {
                throw new Exception(sprintf(
                    "Erreur dans la migration %s (méthode %s(), niveau visé %d) : %s",
                    $file, $method, $target_version, $e->getMessage()
                ), 0, $e);
            }

            $mysql_error = $this->db->_error_number();
            if ($mysql_error) {
                throw new Exception(sprintf(
                    "Erreur SQL dans la migration %s (méthode %s(), niveau visé %d) : code %s, %s",
                    $file, $method, $target_version, $mysql_error, $this->db->_error_message()
                ));
            }

            $current_version += $step;
            $this->_update_version($current_version);
        }

        log_message('debug', 'Finished migrating to ' . $current_version);

        return $current_version;
    }
}

/* End of file MY_Migration.php */
/* Location: ./application/libraries/MY_Migration.php */
