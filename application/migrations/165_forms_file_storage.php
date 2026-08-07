<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 165: bascule le contenu des formulaires (HTML des pages + CSS
 * global) du stockage en base vers le stockage fichier (uploads/formulaires/).
 *
 * Idempotente : un formulaire dont le fichier existe déjà sur disque est
 * ignoré. Cette migration n'est jamais supprimée du projet — une fois toutes
 * les installations migrées elle devient un no-op permanent, plutôt que
 * d'être retirée (voir doc/design_notes/formulaires_sync_fichiers_design.md,
 * section Sécurité : le runner de migration GVV s'arrête silencieusement à
 * la première étape numérotée manquante).
 *
 * @see doc/prds/remplissage_formulaires_prd.md (EF2-bis, EF2-ter)
 */
class Migration_Forms_file_storage extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 165;
        $this->load->library('forms_file_storage');
    }

    public function up() {
        $forms = $this->db->select('id, code, global_css')->get('forms')->result_array();

        $forms_migrated = 0;
        $pages_migrated = 0;

        foreach ($forms as $form) {
            $code = (string) $form['code'];
            if ($code === '') {
                continue;
            }

            if ((string) $form['global_css'] !== '' && $this->forms_file_storage->read_css($code) === null) {
                $this->forms_file_storage->write_css($code, $form['global_css']);
                $forms_migrated++;
            }

            $pages = $this->db
                ->select('page_number, content_html')
                ->where('form_id', (int) $form['id'])
                ->get('form_pages')
                ->result_array();

            foreach ($pages as $page) {
                $num = (int) $page['page_number'];
                if ((string) $page['content_html'] === '') {
                    continue;
                }
                if ($this->forms_file_storage->page_exists($code, $num)) {
                    continue;
                }
                $this->forms_file_storage->write_page($code, $num, $page['content_html']);
                $pages_migrated++;
            }
        }

        gvv_info("Migration " . $this->migration_number . ": $forms_migrated CSS et $pages_migrated page(s) écrites sur disque");
        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        // Le contenu écrit sur disque n'est pas supprimé : la base reste la
        // source de contenu tant que le refactor du stockage fichier n'est
        // pas terminé (voir Lot 2-bis), donc rien à annuler ici.
        gvv_info("Migration database down to " . ($this->migration_number - 1) . " (aucune suppression de fichier)");
        return true;
    }
}

/* End of file 165_forms_file_storage.php */
/* Location: ./application/migrations/165_forms_file_storage.php */
