<?php
/**
 * GVV - Gestion des rôles (interface CA)
 *
 * Interface simplifiée de gestion des rôles de section,
 * accessible aux membres du Conseil d'Administration.
 * Ne présente que les rôles de niveau section, en excluant
 * les rôles réservés à l'administration (ca, bureau, tresorier).
 */
class Gestion_roles extends CI_Controller {

    private $excluded_roles = array('ca', 'bureau', 'tresorier');

    function __construct() {
        parent::__construct();

        date_default_timezone_set("Europe/Paris");
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->helper('form');
        $this->load->helper('views');
        $this->load->library('DX_Auth');
        $this->lang->load('gvv');
        $this->session->set_userdata('requested_url', current_url());

        if (getenv('TEST') != '1') {
            $this->dx_auth->check_login();
        }

        // Load early for the global CA check below
        $this->load->library('Gvv_Authorization');

        // For new-auth users, check CA globally (any section) so this matches
        // how the menu link is shown via has_role('ca') from Gvv_Controller
        // (which also uses a global NULL-section check).
        // For legacy users, fall back to the session-scoped has_role() call.
        $use_new_auth = $this->session->userdata('use_new_auth');
        if ($use_new_auth) {
            $user_id = $this->dx_auth->get_user_id();
            // club-admin is a super-role that includes all CA privileges
            if (!$this->gvv_authorization->has_role($user_id, 'ca', NULL)
                && !$this->gvv_authorization->has_role($user_id, 'club-admin', NULL)) {
                $this->dx_auth->deny_access();
            }
        } elseif (!has_role('ca')) {
            $this->dx_auth->deny_access();
        }

        $this->load->model('authorization_model');

        // Bouton retour → tableau de bord Administration du club
        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/admin_club',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_admin_club'),
        ]);
    }

    function index($user_id = NULL) {
        // Build user selector list
        $rows = $this->db
            ->select('u.id, u.username, m.mnom, m.mprenom')
            ->from('users u')
            ->join('membres m', 'u.username = m.mlogin', 'left')
            ->order_by('m.mnom, m.mprenom, u.username')
            ->get()->result_array();

        $user_selector = array('' => '');
        foreach ($rows as $row) {
            $label = trim($row['mnom'] . ' ' . $row['mprenom']);
            if (!$label) $label = $row['username'];
            $user_selector[$row['id']] = $label . ' (' . $row['username'] . ')';
        }

        // Section roles (filtered)
        $excluded = $this->excluded_roles;
        $all_roles = $this->authorization_model->get_all_roles('section');
        $data['all_roles'] = array_values(array_filter($all_roles, function($r) use ($excluded) {
            return !in_array($r['nom'], $excluded);
        }));

        $data['sections']        = $this->authorization_model->get_all_sections();
        $data['user_selector']   = $user_selector;
        $data['selected_user_id'] = (int)$user_id ?: NULL;
        $data['selected_user']   = NULL;
        $data['user_roles']      = array();

        if ($user_id) {
            $user_q = $this->db
                ->select('u.id, u.username, u.email, m.mnom, m.mprenom, s.nom as section_name')
                ->from('users u')
                ->join('membres m', 'u.username = m.mlogin', 'left')
                ->join('sections s', 'm.club = s.id', 'left')
                ->where('u.id', (int)$user_id)
                ->get()->row_array();

            $data['selected_user'] = $user_q;

            if ($user_q) {
                $all_user_roles = $this->authorization_model->get_user_roles((int)$user_id, NULL, FALSE);
                $data['user_roles'] = array_values(array_filter($all_user_roles, function($r) use ($excluded) {
                    return $r['scope'] === 'section' && !in_array($r['role_name'], $excluded);
                }));
            }
        }

        load_last_view('gestion_roles/bs_index', $data);
    }

    function edit_user_roles() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $user_id    = (int)$this->input->post('user_id');
        $role_id    = (int)$this->input->post('types_roles_id');
        $section_id = $this->input->post('section_id');
        $action     = $this->input->post('action');

        if (!$user_id || !$role_id || !in_array($action, array('grant', 'revoke'))) {
            echo json_encode(array('success' => FALSE, 'message' => 'Paramètres manquants ou invalides'));
            return;
        }

        $role = $this->authorization_model->get_role($role_id);
        if (!$role || $role['scope'] !== 'section' || in_array($role['nom'], $this->excluded_roles)) {
            echo json_encode(array('success' => FALSE, 'message' => 'Rôle non autorisé'));
            return;
        }

        $section_id      = (int)$section_id;
        $current_user_id = $this->dx_auth->get_user_id();
        $current_username = $this->dx_auth->get_username();

        // Résolution du nom de l'utilisateur cible pour les logs
        $target_q = $this->db->select('username')->from('users')->where('id', $user_id)->get()->row();
        $target_username = $target_q ? $target_q->username : "id:$user_id";

        try {
            if ($section_id === -1) {
                $sections = $this->db->select('id, nom')->from('sections')
                    ->where('id !=', 0)->where('id !=', 89)->get()->result_array();

                foreach ($sections as $section) {
                    if ($action === 'grant') {
                        $this->gvv_authorization->grant_role($user_id, $role_id, $section['id'], $current_user_id, NULL);
                        if ($role['nom'] === 'user') {
                            $this->_ensure_compte_client($target_username, $section['id'], $section['nom']);
                        }
                        log_message('info', "gestion_roles: $current_username a attribué le rôle '{$role['nom']}' à $target_username (section {$section['nom']})");
                    } else {
                        $this->gvv_authorization->revoke_role($user_id, $role_id, $section['id'], $current_user_id);
                        log_message('info', "gestion_roles: $current_username a retiré le rôle '{$role['nom']}' de $target_username (section {$section['nom']})");
                    }
                }
                $result = TRUE;
            } else {
                $section_q = $this->db->select('nom')->from('sections')->where('id', $section_id)->get()->row();
                $section_nom = $section_q ? $section_q->nom : "id:$section_id";

                if ($action === 'grant') {
                    $result = $this->gvv_authorization->grant_role($user_id, $role_id, $section_id, $current_user_id, NULL);
                    if ($result === 'EXISTS') $result = TRUE;
                    if ($role['nom'] === 'user') {
                        $this->_ensure_compte_client($target_username, $section_id, $section_nom);
                    }
                    log_message('info', "gestion_roles: $current_username a attribué le rôle '{$role['nom']}' à $target_username (section $section_nom)");
                } else {
                    $result = $this->gvv_authorization->revoke_role($user_id, $role_id, $section_id, $current_user_id);
                    log_message('info', "gestion_roles: $current_username a retiré le rôle '{$role['nom']}' de $target_username (section $section_nom)");
                }
            }
        } catch (Exception $e) {
            log_message('error', "gestion_roles: exception lors de la modification de rôle — {$e->getMessage()}");
            echo json_encode(array('success' => FALSE, 'message' => $e->getMessage()));
            return;
        }

        $excluded = $this->excluded_roles;
        $all_roles = $this->authorization_model->get_user_roles($user_id, NULL, FALSE);
        $roles = array_values(array_filter($all_roles, function($r) use ($excluded) {
            return $r['scope'] === 'section' && !in_array($r['role_name'], $excluded);
        }));

        echo json_encode(array('success' => (bool)$result, 'roles' => $roles));
    }

    private function _ensure_compte_client($target_username, $section_id, $section_nom) {
        $membre = $this->db->select('mnom, mprenom')
            ->from('membres')
            ->where('mlogin', $target_username)
            ->get()->row_array();
        if (!$membre) {
            log_message('error', "gestion_roles/_ensure_compte_client: membre $target_username introuvable");
            return;
        }
        $this->load->model('comptes_model');
        $nom_complet = trim($membre['mnom'] . ' ' . $membre['mprenom']);
        if (!$this->comptes_model->get_by_pilote_codec($target_username, 411, $section_id)) {
            $cpt = array(
                'nom'        => $nom_complet,
                'pilote'     => $target_username,
                'desc'       => "Compte client 411 $section_nom $nom_complet",
                'codec'      => 411,
                'actif'      => 1,
                'debit'      => 0.0,
                'credit'     => 0.0,
                'club'       => $section_id,
                'saisie_par' => $this->dx_auth->get_username()
            );
            $this->comptes_model->create($cpt);
            log_message('info', "gestion_roles: compte 411 créé pour $target_username dans la section $section_nom");
        }
    }
}
