<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_importer
 *
 * Import d'un paquet d'exécution de l'agent de veille, commun aux deux
 * étapes de déploiement : brouillon d'une période (obligatoire), rapport
 * d'activité et inventaire (joints ; leur absence est signalée).
 *
 * L'import archive le paquet, crée l'édition à l'état draft (version 1 du
 * brouillon) et enregistre l'exécution associée. En cas d'échec, rien n'est
 * laissé derrière.
 *
 * Le chargeur doit fournir l'instance de Newsletter_files à utiliser.
 *
 * @see doc/design_notes/veille_documentaire_design.md (Paquet d'exécution)
 */
class Newsletter_importer {

    private $CI;
    private $files;

    public function __construct($params = array()) {
        $this->CI =& get_instance();
        $this->CI->load->model('newsletter_model');
        $this->CI->load->model('newsletter_runs_model');
        $this->CI->load->model('newsletter_contexts_model');
        $this->files = $params['files'] ?? NULL;
    }

    public function set_files(Newsletter_files $files) {
        $this->files = $files;
    }

    /**
     * Importe un paquet.
     *
     * @param array $p  title, period_start, period_end (AAAA-MM-JJ),
     *                  draft (contenu Markdown), report, inventory (contenus, optionnels),
     *                  origin, model, cost_usd, context_id (défaut : consignes actives)
     * @return array    ok, errors[], warnings[], newsletter_id, run_id
     */
    public function import($p) {
        $result = array('ok' => FALSE, 'errors' => array(), 'warnings' => array(),
                        'newsletter_id' => NULL, 'run_id' => NULL);

        $title  = trim($p['title'] ?? '');
        $start  = trim($p['period_start'] ?? '');
        $end    = trim($p['period_end'] ?? '');
        $draft  = (string) ($p['draft'] ?? '');
        $report = (string) ($p['report'] ?? '');
        $inv    = (string) ($p['inventory'] ?? '');
        $origin = $p['origin'] ?? Newsletter_runs_model::ORIGIN_DESKTOP;

        if ($title === '') $result['errors'][] = 'Le titre est obligatoire.';
        if (!$this->valid_date($start)) $result['errors'][] = 'Date de début de période invalide.';
        if (!$this->valid_date($end))   $result['errors'][] = 'Date de fin de période invalide.';
        if ($this->valid_date($start) && $this->valid_date($end) && $end < $start) {
            $result['errors'][] = 'La fin de la période précède son début.';
        }
        if (trim($draft) === '') $result['errors'][] = 'Le brouillon est vide ou absent.';
        if (!$this->files) $result['errors'][] = 'Stockage des fichiers non configuré.';
        if ($result['errors']) return $result;

        if (trim($report) === '') $result['warnings'][] = "Aucun rapport d'activité joint : la preuve de veille de cette édition sera incomplète.";
        if (trim($inv) === '')    $result['warnings'][] = "Aucun inventaire joint : l'inventaire courant reste celui de l'exécution précédente.";

        $context = isset($p['context_id']) ? $this->CI->newsletter_contexts_model->get($p['context_id'])
                                           : $this->CI->newsletter_contexts_model->get_active();
        if (!$context) $result['warnings'][] = "Aucune version des consignes n'est enregistrée dans GVV : l'exécution n'y sera pas rattachée.";

        $run_id = $this->CI->newsletter_runs_model->generate_run_id();

        // 1. Paquet archivé
        $stored = $this->files->store_run_file($run_id, Newsletter_files::DRAFT_FILE, $draft);
        if ($stored && trim($report) !== '') $stored = $this->files->store_run_file($run_id, Newsletter_files::REPORT_FILE, $report);
        if ($stored && trim($inv) !== '')    $stored = $this->files->store_run_file($run_id, Newsletter_files::INVENTORY_FILE, $inv);
        if (!$stored) {
            $this->files->delete_run($run_id);
            $result['errors'][] = "Impossible d'enregistrer les fichiers dans " . $this->files->base_path() . ' (droits d\'écriture ?).';
            return $result;
        }

        // 2. Édition à l'état draft, version 1
        $newsletter_id = $this->CI->newsletter_model->create(array(
            'title' => $title, 'period_start' => $start, 'period_end' => $end,
        ));
        $version = $newsletter_id ? $this->files->save_version($newsletter_id, $draft) : FALSE;
        if (!$version) {
            if ($newsletter_id) {
                $this->files->delete_edition($newsletter_id);
                $this->CI->newsletter_model->delete($newsletter_id);
            }
            $this->files->delete_run($run_id);
            $result['errors'][] = "Impossible de créer l'édition.";
            return $result;
        }
        $this->CI->newsletter_model->update($newsletter_id, array('draft_path' => $version['path']));

        // 3. Exécution
        $now = date('Y-m-d H:i:s');
        $run = $this->CI->newsletter_runs_model->create(array(
            'run_id'        => $run_id,
            'origin'        => $origin,
            'status'        => Newsletter_runs_model::STATUS_SUCCEEDED,
            'context_id'    => $context ? $context['id'] : NULL,
            'newsletter_id' => $newsletter_id,
            'started_at'    => $p['started_at'] ?? $now,
            'finished_at'   => $p['finished_at'] ?? $now,
            'model'         => ($p['model'] ?? '') !== '' ? mb_substr($p['model'], 0, 100) : NULL,
            'cost_usd'      => is_numeric($p['cost_usd'] ?? NULL) ? $p['cost_usd'] : NULL,
            'package_path'  => $this->files->run_dir($run_id),
        ));
        if (!$run) {
            $this->files->delete_edition($newsletter_id);
            $this->CI->newsletter_model->delete($newsletter_id);
            $this->files->delete_run($run_id);
            $result['errors'][] = "Impossible d'enregistrer l'exécution.";
            return $result;
        }

        $result['ok'] = TRUE;
        $result['newsletter_id'] = $newsletter_id;
        $result['run_id'] = $run_id;
        return $result;
    }

    /**
     * Inventaire courant : celui de la dernière exécution réussie qui en a fourni un.
     *
     * @return array|null  ['run_id' => string, 'content' => string]
     */
    public function current_inventory() {
        foreach ($this->CI->newsletter_runs_model->get_all() as $run) {
            if ($run['status'] !== Newsletter_runs_model::STATUS_SUCCEEDED) continue;
            $path = $this->files->run_file($run['run_id'], Newsletter_files::INVENTORY_FILE);
            if ($path) {
                return array('run_id' => $run['run_id'], 'content' => file_get_contents($path));
            }
        }
        return NULL;
    }

    private function valid_date($d) {
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt && $dt->format('Y-m-d') === $d;
    }
}
