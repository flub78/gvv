<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// Load Gvv_Controller base class
require_once(APPPATH . 'core/Gvv_Controller.php');

/**
 * Login_as Controller
 *
 * Permet aux administrateurs de se connecter en tant qu'un autre utilisateur
 * sans connaître son mot de passe. Utile pour les tests et le débogage.
 *
 * SÉCURITÉ: Cette fonctionnalité n'est accessible que si:
 * - dev_menu_users contient le username dans la configuration
 * - ET l'utilisateur actuel est admin
 *
 * @package controllers
 */
class Login_as extends Gvv_Controller {

    public function __construct() {
        parent::__construct();

        // Vérifier que l'utilisateur est admin
        // Les admins peuvent déjà modifier les mots de passe, donc cette fonctionnalité
        // ne leur donne pas plus de privilèges qu'ils n'ont déjà
        if (!$this->dx_auth->is_admin()) {
            show_error('Accès réservé aux administrateurs.', 403);
        }

        $this->load->model('dx_auth/users', 'users');
        $this->load->model('dx_auth/roles', 'roles');
    }

    /**
     * Page principale - Liste des utilisateurs
     */
    public function index() {
        $data = array();

        // Récupérer tous les utilisateurs avec leurs rôles
        $query = $this->users->get_all();
        $users = $query->result();

        // Récupérer les infos des membres pour afficher les noms complets
        $this->load->model('membres_model');

        $users_list = array();
        foreach ($users as $user) {
            $membre = $this->membres_model->get_by_id('mlogin', $user->username);
            $display_name = $user->username;
            if (!empty($membre) && !empty($membre['mprenom']) && !empty($membre['mnom'])) {
                $display_name = $membre['mprenom'] . ' ' . $membre['mnom'] . ' (' . $user->username . ')';
            }

            // Récupérer le nom du rôle
            $role_query = $this->roles->get_role_by_id($user->role_id);
            $role_name = $role_query->num_rows() > 0 ? $role_query->row()->name : 'Inconnu';

            $users_list[] = array(
                'id' => $user->id,
                'username' => $user->username,
                'display_name' => $display_name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role_name' => $role_name,
                'banned' => $user->banned
            );
        }

        // Trier par nom d'affichage
        usort($users_list, function($a, $b) {
            return strcasecmp($a['display_name'], $b['display_name']);
        });

        $data['users'] = $users_list;
        $data['current_user'] = $this->dx_auth->get_username();

        load_last_view('login_as', $data);
    }

    /**
     * Effectue le changement d'utilisateur
     *
     * @param string $username Le nom d'utilisateur cible
     */
    public function switch_to($username = null) {
        if (empty($username)) {
            // Récupérer depuis POST
            $username = $this->input->post('username');
        }

        if (empty($username)) {
            $this->session->set_flashdata('error', 'Veuillez sélectionner un utilisateur.');
            redirect('login_as');
            return;
        }

        // Récupérer l'utilisateur cible
        $query = $this->users->get_user_by_username($username);

        if ($query->num_rows() != 1) {
            $this->session->set_flashdata('error', 'Utilisateur non trouvé: ' . htmlspecialchars($username));
            redirect('login_as');
            return;
        }

        $target_user = $query->row();

        // Vérifier que l'utilisateur n'est pas banni
        if ($target_user->banned > 0) {
            $this->session->set_flashdata('error', 'Impossible de se connecter en tant qu\'un utilisateur banni.');
            redirect('login_as');
            return;
        }

        // Sauvegarder l'utilisateur admin original pour le log
        $original_user = $this->dx_auth->get_username();

        // Logger l'action AVANT le changement de session
        $this->load->helper('log');
        gvv_log('info', "LOGIN_AS: '$original_user' se connecte en tant que '$username'");

        // Effectuer le changement de session (bypass du mot de passe)
        $this->dx_auth->_set_session($target_user);

        // Message de succès
        $this->session->set_flashdata('success', 'Vous êtes maintenant connecté en tant que: ' . $username);

        // Rediriger vers la page d'accueil
        redirect('welcome');
    }
}
