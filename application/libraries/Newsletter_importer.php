<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_importer
 *
 * Import d'un brouillon produit par l'agent de rédaction : un fichier
 * Markdown, ou une archive zip contenant un Markdown et ses images.
 *
 * Le titre, la période et la version sont lus dans le Markdown (parse_meta).
 * Le brouillon devient la version suivante de l'édition non publiée de même
 * période, ou crée une nouvelle édition. Les images référencées sont rangées
 * sous img/ de la version, et leurs références réécrites en conséquence.
 * En cas d'échec, rien n'est laissé derrière.
 *
 * Le chargeur doit fournir l'instance de Newsletter_files à utiliser.
 *
 * @see doc/design_notes/veille_documentaire_design.md (Fichiers échangés)
 */
class Newsletter_importer {

    const MAX_ZIP_SIZE   = 31457280;
    const MAX_ZIP_FILES  = 300;
    const MAX_IMAGE_SIZE = 10485760;

    const MONTHS = array('janvier' => 1, 'février' => 2, 'fevrier' => 2, 'mars' => 3, 'avril' => 4, 'mai' => 5,
                         'juin' => 6, 'juillet' => 7, 'août' => 8, 'aout' => 8, 'septembre' => 9, 'octobre' => 10,
                         'novembre' => 11, 'décembre' => 12, 'decembre' => 12);

    private $CI;
    private $files;

    public function __construct($params = array()) {
        $this->CI =& get_instance();
        $this->CI->load->model('newsletter_model');
        $this->CI->lang->load('newsletter');
        $this->files = $params['files'] ?? NULL;
    }

    public function set_files(Newsletter_files $files) {
        $this->files = $files;
    }

    private function msg($key, ...$args) {
        return vsprintf($this->CI->lang->line($key), $args);
    }

    // ── Lecture des métadonnées ───────────────────────────────────────────

    /**
     * Titre, période et version d'un brouillon.
     *
     * @return array  title, period_start, period_end (AAAA-MM-JJ), version (int) ;
     *                NULL pour chaque donnée introuvable
     */
    public static function parse_meta($markdown) {
        $text = (string) $markdown;
        $meta = array('title' => NULL, 'period_start' => NULL, 'period_end' => NULL, 'version' => NULL);

        if (preg_match('/^#[ \t]+(.+?)[ \t#]*$/m', $text, $m)) {
            $meta['title'] = trim($m[1]);
        }
        if (preg_match('/BROUILLON\s+v(\d+)/iu', $text, $m)) {
            $meta['version'] = (int) $m[1];
        }
        // « Période : du 21 septembre au 21 octobre 2026 (…) », dates en lettres ou numériques
        if (preg_match('/P[ée]riode(?:\s+couverte)?[\s*:]*du\s+(.+?)\s+au\s+(.+?)(?=\s*[(.;,*]|\s*$)/imu', $text, $m)) {
            $end = self::parse_date($m[2], NULL);
            if ($end) {
                $start = self::parse_date($m[1], (int) substr($end, 0, 4));
                if ($start && $start > $end) {
                    // Année omise au début d'une période à cheval sur deux années
                    $start = self::parse_date($m[1], (int) substr($end, 0, 4) - 1);
                }
                if ($start && $start <= $end) {
                    $meta['period_start'] = $start;
                    $meta['period_end'] = $end;
                }
            }
        }
        return $meta;
    }

    /**
     * Date en français (« 1er septembre 2026 », « 21 octobre ») ou numérique
     * (« 21/10/2026 », « 2026-10-21 »).
     *
     * @param string   $s
     * @param int|null $default_year  Année si elle est omise ; NULL : obligatoire
     * @return string|null            AAAA-MM-JJ
     */
    public static function parse_date($s, $default_year) {
        $s = trim(mb_strtolower(strip_tags((string) $s)), " \t*.");
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $s : NULL;
        }
        if (preg_match('#^(\d{1,2})/(\d{1,2})(?:/(\d{4}))?$#', $s, $m)) {
            $y = isset($m[3]) ? (int) $m[3] : $default_year;
            return ($y && checkdate((int) $m[2], (int) $m[1], $y)) ? sprintf('%04d-%02d-%02d', $y, $m[2], $m[1]) : NULL;
        }
        if (preg_match('/^(\d{1,2})(?:er)?\s+(\p{L}+)(?:\s+(\d{4}))?$/u', $s, $m) && isset(self::MONTHS[$m[2]])) {
            $y = isset($m[3]) ? (int) $m[3] : $default_year;
            $mo = self::MONTHS[$m[2]];
            return ($y && checkdate($mo, (int) $m[1], $y)) ? sprintf('%04d-%02d-%02d', $y, $mo, $m[1]) : NULL;
        }
        return NULL;
    }

    // ── Lecture du fichier importé ────────────────────────────────────────

    /**
     * Markdown et fichiers d'un envoi (.md ou .zip).
     *
     * @return array  markdown, base (répertoire du Markdown dans l'archive),
     *                entries [chemin normalisé => contenu], errors[]
     */
    public function read_upload($filename, $content) {
        $out = array('markdown' => NULL, 'base' => '', 'entries' => array(), 'errors' => array());
        $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));

        if ($ext === 'md' || $ext === 'markdown') {
            $out['markdown'] = self::to_utf8($content);
            return $out;
        }
        if ($ext !== 'zip') {
            $out['errors'][] = $this->msg('nl_import_bad_type', $filename);
            return $out;
        }
        if (strlen($content) > self::MAX_ZIP_SIZE) {
            $out['errors'][] = $this->msg('nl_upload_too_big', $filename);
            return $out;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'nlimp');
        file_put_contents($tmp, $content);
        $zip = new ZipArchive();
        if ($zip->open($tmp) !== TRUE) {
            @unlink($tmp);
            $out['errors'][] = $this->msg('nl_import_bad_zip', $filename);
            return $out;
        }
        $markdowns = array();
        for ($i = 0; $i < $zip->numFiles && $i < self::MAX_ZIP_FILES; $i++) {
            $name = self::normalize_path($zip->getNameIndex($i));
            if ($name === NULL || substr($name, -1) === '/' || strpos($name, '__MACOSX/') === 0
                || strpos(basename($name), '.') === 0) {
                continue;
            }
            $e = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($e === 'md' || $e === 'markdown') {
                $markdowns[$name] = $zip->getFromIndex($i);
            } elseif (Newsletter_files::is_image($name)) {
                $stat = $zip->statIndex($i);
                if ($stat['size'] <= self::MAX_IMAGE_SIZE) {
                    $out['entries'][$name] = $zip->getFromIndex($i);
                }
            }
        }
        $zip->close();
        @unlink($tmp);

        if (count($markdowns) !== 1) {
            $out['errors'][] = $this->msg(count($markdowns) ? 'nl_import_zip_many_md' : 'nl_import_zip_no_md',
                                          implode(', ', array_keys($markdowns)));
            return $out;
        }
        $out['markdown'] = self::to_utf8(reset($markdowns));
        $dir = dirname(key($markdowns));
        $out['base'] = ($dir === '.') ? '' : $dir . '/';
        return $out;
    }

    private static function to_utf8($content) {
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        return preg_replace('/^\xEF\xBB\xBF/', '', $content);
    }

    /**
     * Chemin relatif normalisé (sans « . » ni « .. ») ; NULL s'il sort de la racine.
     */
    public static function normalize_path($path) {
        $parts = array();
        foreach (explode('/', str_replace('\\', '/', (string) $path)) as $p) {
            if ($p === '' || $p === '.') continue;
            if ($p === '..') {
                if (!$parts) return NULL;
                array_pop($parts);
                continue;
            }
            $parts[] = $p;
        }
        $norm = implode('/', $parts);
        return (substr((string) $path, -1) === '/' && $norm !== '') ? $norm . '/' : $norm;
    }

    /**
     * Rattache les images référencées aux fichiers de l'archive et réécrit
     * leurs références vers img/<nom>.
     *
     * @return array  markdown, images [nom => contenu], missing[]
     */
    public static function resolve_images($markdown, $base, array $entries) {
        $images = array();
        $missing = array();
        $by_name = array();
        foreach ($entries as $path => $data) $by_name[basename($path)][] = $path;

        foreach (Newsletter_files::image_refs($markdown) as $ref) {
            $path = self::normalize_path($base . rawurldecode($ref));
            if ($path === NULL || !isset($entries[$path])) {
                // Référence hors de l'archive : un fichier de même nom, s'il est unique
                $candidates = $by_name[basename(rawurldecode($ref))] ?? array();
                $path = (count($candidates) === 1) ? $candidates[0] : NULL;
            }
            if ($path === NULL || !Newsletter_files::is_image($path)) {
                $missing[] = $ref;
                continue;
            }
            $name = Newsletter_files::safe_name(basename($path));
            if (isset($images[$name]) && $images[$name] !== $entries[$path]) {
                $name = substr(md5($path), 0, 6) . '_' . $name;
            }
            $images[$name] = $entries[$path];
            $markdown = preg_replace('/(!\[[^\]]*\]\(\s*<?)' . preg_quote($ref, '/') . '(?=[>\s)])/u',
                                     '${1}img/' . $name, $markdown);
        }
        return array('markdown' => $markdown, 'images' => $images, 'missing' => $missing);
    }

    // ── Import ────────────────────────────────────────────────────────────

    /**
     * Importe un brouillon.
     *
     * @param string $filename  Nom du fichier envoyé (.md ou .zip)
     * @param string $content   Contenu
     * @return array  ok, errors[], warnings[], newsletter_id, version, created, meta
     */
    public function import($filename, $content) {
        $result = array('ok' => FALSE, 'errors' => array(), 'warnings' => array(),
                        'newsletter_id' => NULL, 'version' => NULL, 'created' => FALSE, 'meta' => NULL);

        $upload = $this->read_upload($filename, $content);
        if ($upload['errors']) {
            $result['errors'] = $upload['errors'];
            return $result;
        }
        if (trim($upload['markdown']) === '') {
            $result['errors'][] = $this->msg('nl_import_empty');
            return $result;
        }

        $meta = self::parse_meta($upload['markdown']);
        $result['meta'] = $meta;
        if ($meta['title'] === NULL)        $result['errors'][] = $this->msg('nl_import_no_title');
        if ($meta['period_start'] === NULL) $result['errors'][] = $this->msg('nl_import_no_period');
        if ($meta['version'] === NULL)      $result['errors'][] = $this->msg('nl_import_no_version');
        if ($result['errors']) return $result;

        $res = self::resolve_images($upload['markdown'], $upload['base'], $upload['entries']);
        foreach ($res['missing'] as $ref) {
            $result['warnings'][] = $this->msg('nl_import_missing_image', $ref);
        }

        // Édition en cours de même période, ou nouvelle édition
        $nl = $this->CI->db->where('period_start', $meta['period_start'])
                           ->where('period_end', $meta['period_end'])
                           ->where('status !=', Newsletter_model::STATUS_PUBLISHED)
                           ->order_by('id', 'DESC')->limit(1)
                           ->get($this->CI->newsletter_model->table())->row_array();
        if ($nl) {
            if ($nl['status'] === Newsletter_model::STATUS_APPROVED || $nl['status'] === Newsletter_model::STATUS_VALIDATING) {
                $result['errors'][] = $this->msg('nl_import_already_validated', $nl['title']);
                return $result;
            }
            $versions = $this->files->list_versions($nl['id']);
            $last = $versions ? end($versions)['version'] : 0;
            if ($meta['version'] <= $last) {
                $result['errors'][] = $this->msg('nl_import_version_exists', $meta['version'], $nl['title'], $last);
                return $result;
            }
        }

        $created = FALSE;
        if (!$nl) {
            $id = $this->CI->newsletter_model->create(array(
                'title' => mb_substr($meta['title'], 0, 255),
                'period_start' => $meta['period_start'],
                'period_end' => $meta['period_end'],
            ));
            if (!$id) {
                $result['errors'][] = $this->msg('nl_import_db_error');
                return $result;
            }
            $nl = $this->CI->newsletter_model->get($id);
            $created = TRUE;
        }

        $path = $this->files->save_version($nl['id'], $meta['version'], $res['markdown'], $res['images']);
        if ($path === FALSE) {
            if ($created) $this->CI->newsletter_model->delete($nl['id']);
            $result['errors'][] = $this->msg('nl_import_storage_error', $this->files->base_path());
            return $result;
        }

        $this->CI->newsletter_model->update($nl['id'], array('draft_path' => $path, 'title' => mb_substr($meta['title'], 0, 255)));
        if ($nl['status'] === Newsletter_model::STATUS_REVIEWING
            && !$this->CI->newsletter_model->transition($nl['id'], Newsletter_model::STATUS_DRAFT)) {
            $result['warnings'][] = $this->msg('nl_import_status_unchanged');
        }

        $result['ok'] = TRUE;
        $result['newsletter_id'] = (int) $nl['id'];
        $result['version'] = $meta['version'];
        $result['created'] = $created;
        return $result;
    }
}
