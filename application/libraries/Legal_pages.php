<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Legal_pages
 *
 * Construit le contenu des pages publiques « Règles de confidentialité » et
 * « Conditions d'utilisation » :
 *   - texte propre au club (page Configuration, par langue, repli sur le français),
 *   - à défaut, gabarit générique livré avec GVV (views/legal/<langue>/<page>.md),
 *   - insertion des informations du club ({nom_club}, {email_club}…),
 *   - blocs conditionnels [[si paiements]]…[[fin]] gardés seulement si le module est actif.
 *
 * @see doc/prds/configuration_unifiee_prd.md §6 bis
 */
class Legal_pages {

    /** Pages publiques et clé du paramètre qui contient leur texte propre au club. */
    const PAGES = array(
        'confidentialite' => 'legal.confidentialite',
        'conditions'      => 'legal.conditions',
    );

    /** Informations du club insérables dans les textes. */
    const CLUB_FIELDS = array('sigle_club', 'nom_club', 'adresse_club', 'cp_club', 'ville_club',
        'tel_club', 'email_club', 'url_club', 'contact_rgpd');

    protected $CI;

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->library('Parametres_store');
        $this->CI->load->helper('markdown');
    }

    /**
     * Contenu d'une page.
     *
     * @param string $page  'confidentialite' ou 'conditions'
     * @param string $lang  langue CodeIgniter (french, english, dutch)
     * @return array|null   markdown, html, updated_at (NULL pour le texte générique), is_club_text
     */
    public function page($page, $lang) {
        if (!isset(self::PAGES[$page])) {
            return NULL;
        }
        $club_text = $this->CI->parametres_store->get_with_fallback(self::PAGES[$page], $lang);
        if (!$club_text['is_default'] && trim($club_text['value']) !== '') {
            $source = $club_text['value'];
            $updated_at = $club_text['updated_at'];
            $is_club_text = TRUE;
        } else {
            $source = $this->template($page, $lang);
            $updated_at = NULL;
            $is_club_text = FALSE;
        }

        $markdown = $this->render($source, $this->club_values(), $this->conditions());
        return array(
            'markdown'     => $markdown,
            'html'         => markdown($markdown),
            'updated_at'   => $updated_at,
            'is_club_text' => $is_club_text,
        );
    }

    /**
     * Gabarit générique d'une page, dans la langue demandée ou en français.
     */
    public function template($page, $lang) {
        foreach (array($lang, Parametres_store::FALLBACK_LANGUAGE) as $l) {
            $file = APPPATH . 'views/legal/' . basename((string) $l) . '/' . basename($page) . '.md';
            if (is_file($file)) {
                return file_get_contents($file);
            }
        }
        return '';
    }

    /**
     * Applique les blocs conditionnels puis remplace les champs du club.
     *
     * @param string $text
     * @param array  $values     nom => valeur
     * @param array  $conditions nom => bool
     * @return string
     */
    public function render($text, $values, $conditions) {
        $text = preg_replace_callback('/\[\[si (!?)(\w+)\]\](.*?)\[\[fin\]\]\n?/s',
            function ($m) use ($conditions) {
                $active = !empty($conditions[$m[2]]);
                if ($m[1] === '!') {
                    $active = !$active;
                }
                return $active ? $m[3] : '';
            }, $text);

        return preg_replace_callback('/\{(\w+)\}/', function ($m) use ($values) {
            return array_key_exists($m[1], $values) ? $values[$m[1]] : $m[0];
        }, $text);
    }

    /**
     * Informations du club. Le contact RGPD vaut « le président » par défaut.
     */
    public function club_values() {
        $values = array();
        foreach (self::CLUB_FIELDS as $field) {
            $values[$field] = trim((string) $this->CI->config->item($field));
        }
        if ($values['contact_rgpd'] === '') {
            $this->CI->lang->load('legal');
            $values['contact_rgpd'] = $this->CI->lang->line('legal_default_contact_rgpd');
        }
        return $values;
    }

    /**
     * Conditions disponibles dans les gabarits.
     */
    public function conditions() {
        return array(
            'paiements' => $this->online_payments_enabled(),
            'site'      => trim((string) $this->CI->config->item('url_club')) !== '',
            'telephone' => trim((string) $this->CI->config->item('tel_club')) !== '',
        );
    }

    /**
     * TRUE si le paiement en ligne est activé dans au moins une section.
     */
    public function online_payments_enabled() {
        if (!$this->CI->db->table_exists('paiements_en_ligne_config')) {
            return FALSE;
        }
        return $this->CI->db->where('plateforme', 'helloasso')
            ->where('param_key', 'enabled')
            ->where('param_value', '1')
            ->count_all_results('paiements_en_ligne_config') > 0;
    }
}

/* End of file Legal_pages.php */
