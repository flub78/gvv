<?php

/**
 * Parametres
 *
 * Page Configuration unique, organisée en onglets. Les onglets et leurs paramètres
 * sont déclarés dans config/parametres_registry.php ; seuls ceux que l'utilisateur
 * peut lire sont affichés (CA, club-admin, dev_user).
 *
 * Routes :
 *   GET  parametres                 premier onglet accessible
 *   GET  parametres/index/<onglet>  onglet donné (adresse partageable)
 *   POST parametres/save/<onglet>   enregistrement d'un onglet
 *
 * @see doc/prds/configuration_unifiee_prd.md
 * @see doc/design_notes/configuration_unifiee_design.md
 */
class Parametres extends MY_Controller {

    function __construct() {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
            return;
        }

        $this->lang->load('parametres');
        $this->lang->load('tableaux_de_bord');
        $this->load->library('Parametres_store');
        $this->load->helper('validation');

        $this->load->vars(array(
            'nav_back_url'   => 'welcome/section/admin_club',
            'nav_back_label' => $this->lang->line('db_section_admin_club'),
        ));
    }

    /**
     * Affiche un onglet.
     *
     * @param string|null $tab onglet ; le premier onglet accessible par défaut
     */
    public function index($tab = NULL) {
        $visible = $this->parametres_store->visible_tabs();
        if ($tab === NULL) {
            if (empty($visible)) {
                $this->_message($this->lang->line('parametres_no_tab'));
                return;
            }
            $tab = key($visible);
        }
        if (!$this->_check_tab($tab, $visible)) {
            return;
        }
        $this->_render($tab, $visible);
    }

    /**
     * Enregistre les paramètres modifiables d'un onglet.
     * Succès : retour à l'onglet avec le compte rendu. Erreur : l'onglet est
     * réaffiché avec toutes les saisies et le motif à côté de chaque champ refusé.
     */
    public function save($tab = NULL) {
        $visible = $this->parametres_store->visible_tabs();
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('parametres/index/' . $tab);
            return;
        }
        if (!$this->_check_tab($tab, $visible)) {
            return;
        }

        $posted = $this->input->post('p');
        $posted = is_array($posted) ? $posted : array();

        $saved = array();
        $errors = array();
        foreach ($this->parametres_store->tab_keys($tab) as $key) {
            if (!$this->parametres_store->can_write($key)) {
                continue;
            }
            $def = $this->parametres_store->definition($key);
            $label = $this->lang->line($def['label']);
            $field = $this->_field_name($key);

            $langs = empty($def['per_lang']) ? array(NULL) : Parametres_store::LANGUAGES;
            foreach ($langs as $lang) {
                $value = $this->_posted_value($posted, $field, $lang, $def);
                if ($value === NULL) {
                    continue;
                }
                $res = $this->parametres_store->save($key, $value, $lang);
                $name = $lang ? $label . ' (' . $this->lang->line('parametres_lang_' . $lang) . ')' : $label;
                if ($res['status'] === 'saved') {
                    $saved[] = $name;
                } elseif ($res['status'] === 'error') {
                    $errors[$field . ($lang ? '.' . $lang : '')] = array(
                        'name'    => $name,
                        'message' => $this->_error_text($res),
                    );
                }
            }
        }

        if (!empty($errors)) {
            $this->_render($tab, $visible, array(
                'saved'  => $saved,
                'errors' => $errors,
                'posted' => $posted,
            ));
            return;
        }

        $this->session->set_flashdata('parametres_saved', $saved);
        redirect('parametres/index/' . $tab);
    }

    // ------------------------------------------------------------------

    /**
     * Vérifie que l'onglet existe et est accessible ; sinon affiche un refus explicite.
     */
    private function _check_tab($tab, $visible) {
        $tabs = $this->parametres_store->tabs();
        if (!isset($tabs[$tab])) {
            $this->_message(sprintf($this->lang->line('parametres_unknown_tab'), htmlspecialchars((string) $tab)));
            return FALSE;
        }
        if (!isset($visible[$tab])) {
            $this->_message(sprintf($this->lang->line('parametres_forbidden_tab'),
                $this->lang->line($tabs[$tab]['label'])));
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Nom de champ HTML d'un paramètre (les points ne sont pas conservés par PHP).
     */
    private function _field_name($key) {
        return str_replace('.', '__', $key);
    }

    /**
     * Valeur postée pour un paramètre, ou NULL si le champ n'a pas été envoyé.
     * Une case à cocher non cochée n'est pas envoyée : un champ caché la complète.
     */
    private function _posted_value($posted, $field, $lang, $def) {
        if (!array_key_exists($field, $posted)) {
            return NULL;
        }
        $value = $posted[$field];
        if ($lang !== NULL) {
            if (!is_array($value) || !array_key_exists($lang, $value)) {
                return NULL;
            }
            $value = $value[$lang];
        }
        if (is_array($value)) {
            return NULL;
        }
        if ($def['type'] === 'bool') {
            return $value === '1';
        }
        return $value;
    }

    private function _error_text($res) {
        $text = $this->lang->line($res['error']);
        if ($text === FALSE || $text === '') {
            $text = $res['error'];
        }
        if ($res['detail'] !== '') {
            $text = sprintf($text, $res['detail'], $res['detail']);
        }
        return $text;
    }

    /**
     * Prépare les valeurs de chaque paramètre de l'onglet et affiche la page.
     */
    private function _render($tab, $visible, $result = array()) {
        $params = array();
        foreach ($this->parametres_store->tab_keys($tab) as $key) {
            if (!$this->parametres_store->can_read($key)) {
                continue;
            }
            $def = $this->parametres_store->definition($key);
            $langs = empty($def['per_lang']) ? array(NULL) : Parametres_store::LANGUAGES;
            $values = array();
            foreach ($langs as $lang) {
                $values[$lang === NULL ? '' : $lang] = $this->parametres_store->get($key, $lang);
            }
            $params[$key] = array(
                'def'      => $def,
                'field'    => $this->_field_name($key),
                'values'   => $values,
                'writable' => $this->parametres_store->can_write($key),
            );
        }

        $statuses = array();
        foreach (array_keys($visible) as $t) {
            $statuses[$t] = $this->parametres_store->tab_status($t);
        }

        $data = array(
            'title'      => $this->lang->line('parametres_title'),
            'tab'        => $tab,
            'tabs'       => $visible,
            'statuses'   => $statuses,
            'params'     => $params,
            'writable'   => $this->parametres_store->can_write_tab($tab),
            'saved'      => isset($result['saved']) ? $result['saved'] : $this->session->flashdata('parametres_saved'),
            'errors'     => isset($result['errors']) ? $result['errors'] : array(),
            'posted'     => isset($result['posted']) ? $result['posted'] : NULL,
            'languages'  => Parametres_store::LANGUAGES,
        );

        if ($tab === 'pages_publiques') {
            $this->load->library('Legal_pages');
            $data['public_pages'] = array(
                'legal.confidentialite' => site_url('legal/confidentialite'),
                'legal.conditions'      => site_url('legal/conditions'),
            );
            $data['generic_texts'] = array();
            foreach (Legal_pages::PAGES as $page => $key) {
                foreach (Parametres_store::LANGUAGES as $lang) {
                    $data['generic_texts'][$key][$lang] = $this->legal_pages->template($page, $lang);
                }
            }
            $data['newsletter_url'] = $this->config->item('veille') ? site_url('newsletter') : NULL;
        }

        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view('parametres/bs_index', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Message explicite à la place de la page (refus, aucun onglet…).
     */
    private function _message($text) {
        $data = array('title' => $this->lang->line('parametres_title'), 'message' => $text);
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view('parametres/bs_message', $data);
        $this->load->view('bs_footer');
    }
}

/* End of file parametres.php */
