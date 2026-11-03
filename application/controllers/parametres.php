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
 *   GET  parametres/historique/<onglet>  modifications de l'onglet
 *   POST parametres/restore/<id>    retour à la valeur précédant une modification
 *   POST parametres/test_email      envoi d'un e-mail de test (onglet Messagerie)
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
        $visible = $this->_guard_post($tab, 'parametres/index/' . $tab);
        if ($visible === FALSE) {
            return;
        }

        $posted = $this->input->post('p');
        $posted = is_array($posted) ? $posted : array();
        $clear = $this->input->post('clear');
        $clear = is_array($clear) ? $clear : array();

        $saved = array();
        $errors = array();
        foreach ($this->parametres_store->tab_keys($tab) as $key) {
            if (!$this->parametres_store->can_write($key)) {
                continue;
            }
            $def = $this->parametres_store->definition($key);
            $field = $this->_field_name($key);

            $langs = empty($def['per_lang']) ? array(NULL) : Parametres_store::LANGUAGES;
            foreach ($langs as $lang) {
                $options = array();
                if ($def['type'] === 'image') {
                    // Seul un fichier déposé change une image ; jamais un champ texte.
                    $value = $this->_uploaded_image($field, $upload_error);
                    if ($upload_error !== '') {
                        $errors[$field] = array('name' => $this->_param_label($key), 'message' => $upload_error);
                        continue;
                    }
                } else {
                    $value = $this->_posted_value($posted, $field, $lang, $def);
                    if (!empty($def['secret']) && !empty($clear[$field])) {
                        $options['clear'] = TRUE;
                        $value = '';
                    }
                }
                if ($value === NULL) {
                    continue;
                }
                $res = $this->parametres_store->save($key, $value, $lang, $options);
                $name = $this->_param_label($key, $lang);
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

    /**
     * Historique des modifications d'un onglet, visible des profils qui peuvent
     * le modifier (EX-HIS-02).
     */
    public function historique($tab = NULL) {
        $visible = $this->parametres_store->visible_tabs();
        if (!$this->_check_tab($tab, $visible)) {
            return;
        }
        if (!$this->parametres_store->can_write_tab($tab)) {
            $this->_message(sprintf($this->lang->line('parametres_history_forbidden'),
                $this->lang->line($visible[$tab]['label'])));
            return;
        }
        $this->load->model('parametres_history_model');
        if (!$this->parametres_history_model->available()) {
            $this->_message($this->lang->line('parametres_history_unavailable'));
            return;
        }

        $token = $this->_token();
        $rows = array();
        foreach ($this->parametres_history_model->by_tab($tab) as $h) {
            $def = $this->parametres_store->definition($h['cle']);
            $rows[] = array(
                'id'        => $h['id'],
                'date'      => date_db2ht(substr($h['created_at'], 0, 10)) . ' ' . substr($h['created_at'], 11, 5),
                'auteur'    => htmlspecialchars((string) $h['created_by']),
                'parametre' => htmlspecialchars($this->_param_label($h['cle'], $h['lang'])),
                'action'    => $this->lang->line('parametres_history_action_' . $h['action']),
                'ancienne'  => $this->_history_value($h, 'ancienne_valeur', $def),
                'nouvelle'  => $this->_history_value($h, 'nouvelle_valeur', $def),
                'retour'    => $this->_restore_button($h, $def, $token),
            );
        }

        $data = array(
            'title'   => $this->lang->line('parametres_history_title'),
            'tab'     => $tab,
            'info'    => $visible[$tab],
            'rows'    => $rows,
            'result'  => $this->session->flashdata('parametres_restore'),
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view('parametres/bs_historique', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Revient à la valeur précédant une modification de l'historique (EX-HIS-03).
     */
    public function restore($id = 0) {
        $this->load->model('parametres_history_model');
        $row = $this->parametres_history_model->available()
            ? $this->parametres_history_model->get_by_id($id) : NULL;
        if (!$row) {
            $this->_message($this->lang->line('parametres_err_history_unknown'));
            return;
        }
        $tab = $row['onglet'];
        if ($this->_guard_post($tab, 'parametres/historique/' . $tab) === FALSE) {
            return;
        }

        $res = $this->parametres_store->restore($id);
        $name = $this->_param_label($row['cle'], $row['lang']);
        if ($res['status'] === 'saved') {
            $result = array('type' => 'success', 'text' => sprintf($this->lang->line('parametres_restored'), $name));
        } elseif ($res['status'] === 'unchanged') {
            $result = array('type' => 'info', 'text' => sprintf($this->lang->line('parametres_restore_unchanged'), $name));
        } else {
            $result = array('type' => 'danger',
                'text' => sprintf($this->lang->line('parametres_restore_failed'), $name, $this->_error_text($res)));
        }
        $this->session->set_flashdata('parametres_restore', $result);
        redirect('parametres/historique/' . $tab);
    }

    /**
     * Envoie un e-mail de test avec les paramètres de messagerie enregistrés
     * (EX-TST-01, EX-TST-02). Le résultat s'affiche dans l'onglet Messagerie.
     */
    public function test_email() {
        $tab = 'messagerie';
        if ($this->_guard_post($tab, 'parametres/index/' . $tab, TRUE) === FALSE) {
            return;
        }

        $to = trim((string) $this->input->post('to'));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $result = array('ok' => FALSE, 'to' => $to,
                'text' => sprintf($this->lang->line('parametres_test_email_bad_address'), $to), 'debug' => '');
        } else {
            $result = $this->_send_test_email($to);
        }
        $this->session->set_flashdata('parametres_test_email', $result);
        redirect('parametres/index/' . $tab);
    }

    // ------------------------------------------------------------------

    /**
     * Contrôles communs aux actions qui modifient quelque chose (POST) : méthode,
     * onglet accessible, droit de modification si demandé, jeton de la page.
     * En cas de refus, affiche un message explicite ou renvoie vers $back.
     *
     * @param string $tab
     * @param string $back        adresse de retour (rechargement, requête non POST)
     * @param bool   $need_write  exiger un paramètre modifiable dans l'onglet
     * @return array|false onglets visibles, ou FALSE si la demande est refusée
     */
    private function _guard_post($tab, $back, $need_write = FALSE) {
        $visible = $this->parametres_store->visible_tabs();
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect($back);
            return FALSE;
        }
        if (!$this->_check_tab($tab, $visible)) {
            return FALSE;
        }
        if ($need_write && !$this->parametres_store->can_write_tab($tab)) {
            $this->_message(sprintf($this->lang->line('parametres_forbidden_tab'),
                $this->lang->line($visible[$tab]['label'])));
            return FALSE;
        }
        if (!$this->_valid_token($this->input->post('parametres_token'))) {
            $this->_message($this->lang->line('parametres_err_token')
                . ' ' . anchor($back, $this->lang->line('parametres_reload')));
            return FALSE;
        }
        return $visible;
    }

    /**
     * Libellé d'un paramètre, suivi de la langue pour une valeur saisie par langue.
     * La clé brute est rendue pour un paramètre qui n'est plus déclaré.
     */
    private function _param_label($key, $lang = NULL) {
        $def = $this->parametres_store->definition($key);
        $label = $def ? $this->lang->line($def['label']) : $key;
        return $lang ? $label . ' (' . $this->lang->line('parametres_lang_' . $lang) . ')' : $label;
    }

    /**
     * Envoi effectif de l'e-mail de test. La bibliothèque e-mail lit
     * application/config/email.php : ce sont donc les valeurs enregistrées.
     */
    private function _send_test_email($to) {
        $this->load->library('email');
        $this->load->helper('email');
        list($from, $from_name) = default_email_sender();

        $this->email->clear(TRUE);
        $this->email->from($from, $from_name);
        $this->email->to($to);
        $this->email->subject($this->lang->line('parametres_test_email_subject'));
        $this->email->message(sprintf($this->lang->line('parametres_test_email_body'),
            htmlspecialchars($this->dx_auth->get_username()), date('d/m/Y H:i')));
        gvv_info("MAIL parametres test_email to=$to");

        if ($this->email->send()) {
            return array('ok' => TRUE, 'to' => $to,
                'text' => sprintf($this->lang->line('parametres_test_email_sent'), $to), 'debug' => '');
        }
        return array('ok' => FALSE, 'to' => $to,
            'text' => sprintf($this->lang->line('parametres_test_email_failed'),
                (string) $this->config->item('protocol'), $from),
            'debug' => $this->email->print_debugger());
    }

    /**
     * Valeur affichée dans l'historique : secrets masqués, valeur non définie signalée,
     * textes longs abrégés. Rendue échappée.
     */
    private function _history_value($h, $column, $def) {
        if (!empty($h['secret'])) {
            return '<em>' . $this->lang->line('parametres_history_secret') . '</em>';
        }
        $value = $h[$column];
        if ($value === NULL) {
            return '<em>' . $this->lang->line('parametres_history_default') . '</em>';
        }
        if ($def && $def['type'] === 'bool') {
            return $this->lang->line($value === '1' ? 'parametres_yes' : 'parametres_no');
        }
        $short = mb_strlen($value) > 80 ? mb_substr($value, 0, 80) . '…' : $value;
        return '<span title="' . htmlspecialchars(mb_substr($value, 0, 500)) . '">' . htmlspecialchars($short) . '</span>';
    }

    /**
     * Bouton de retour à la valeur précédente, en POST avec le jeton de la page.
     * Absent pour un secret ou un paramètre que l'utilisateur ne peut pas modifier.
     */
    private function _restore_button($h, $def, $token) {
        if (!$def || !empty($h['secret']) || !empty($def['secret'])
            || !$this->parametres_store->can_write($h['cle'])) {
            return '';
        }
        return '<form method="post" class="d-inline" action="' . site_url('parametres/restore/' . (int) $h['id']) . '">'
            . '<input type="hidden" name="parametres_token" value="' . htmlspecialchars($token) . '">'
            . '<button type="submit" class="btn btn-outline-secondary btn-sm restore-btn" title="'
            . htmlspecialchars($this->lang->line('parametres_restore_help')) . '">'
            . '<i class="fas fa-undo"></i> ' . $this->lang->line('parametres_restore') . '</button></form>';
    }

    /**
     * Fichier image déposé pour un paramètre de type image.
     *
     * @param string $field nom du champ
     * @param string $error motif du refus (sortie), chaîne vide sinon
     * @return string|null chemin enregistré, ou NULL si aucun fichier n'a été déposé
     */
    private function _uploaded_image($field, &$error) {
        $error = '';
        $input = 'img_' . $field;
        if (empty($_FILES[$input]) || $_FILES[$input]['error'] === UPLOAD_ERR_NO_FILE) {
            return NULL;
        }
        $this->load->library('upload', array(
            'upload_path'   => './uploads/',
            'allowed_types' => 'jpg|jpeg|gif|png',
            'max_size'      => '500',
            'encrypt_name'  => TRUE,
        ));
        if (!$this->upload->do_upload($input)) {
            $error = strip_tags($this->upload->display_errors('', ' '));
            return NULL;
        }
        $uploaded = $this->upload->data();
        return './uploads/' . $uploaded['file_name'];
    }

    /**
     * Jeton anti-falsification de requête (CSRF) propre à la page, conservé en
     * session : la protection CSRF globale de CodeIgniter n'est pas activée dans GVV.
     */
    private function _token() {
        $token = $this->session->userdata('parametres_token');
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->set_userdata('parametres_token', $token);
        }
        return $token;
    }

    private function _valid_token($posted) {
        $token = $this->session->userdata('parametres_token');
        return is_string($token) && strlen($token) === 64 && is_string($posted) && hash_equals($token, $posted);
    }

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
            'token'      => $this->_token(),
        );

        // Modules actifs auxquels il manque des paramètres (EX-ENR-06).
        $warnings = array();
        foreach (array_keys($params) as $key) {
            foreach ($this->parametres_store->missing_requirements($key) as $missing_tab => $keys) {
                $warnings[] = array(
                    'module' => $this->lang->line($params[$key]['def']['label']),
                    'tab'    => $missing_tab,
                    'label'  => $this->lang->line($this->parametres_store->tabs()[$missing_tab]['label']),
                );
            }
        }
        $data['warnings'] = $warnings;

        if ($tab === 'messagerie') {
            $data['test_email'] = $this->session->flashdata('parametres_test_email');
            $data['test_email_to'] = $this->_user_email();
        }

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
     * Adresse e-mail de l'utilisateur connecté (destinataire proposé pour le test).
     */
    private function _user_email() {
        $query = $this->db->select('email')->from('users')
            ->where('id', (int) $this->dx_auth->get_user_id())->get();
        return ($query && $query->num_rows() > 0) ? (string) $query->row()->email : '';
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
