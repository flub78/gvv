<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Agenda_google_liaisons — liste, pour l'administrateur du club, des membres
 * dont la synchronisation avec Google Agenda est suspendue (SYNC-ERR-05).
 * N'affiche que le membre, l'état, la cause et les dates : jamais le jeton,
 * ni l'agenda du membre.
 */
class Agenda_google_liaisons extends MY_Controller
{
    protected $controller = 'agenda_google_liaisons';

    function __construct()
    {
        parent::__construct();
        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }
        if (!$this->gvv_authorization->has_role($this->dx_auth->get_user_id(), 'club-admin', NULL)) {
            show_error('Accès réservé aux administrateurs du club.', 403);
        }
        $this->lang->load('agenda_google');
        $this->lang->load('tableaux_de_bord');
        $this->load->vars(array(
            'nav_back_url'   => 'parametres/index/modules',
            'nav_back_label' => $this->lang->line('agenda_google_retour_configuration'),
        ));
    }

    public function index()
    {
        $this->load->model('agenda_google_liaisons_model');
        $this->load->library('Agenda_google_sync');
        $this->load->library('gvvmetadata');
        $this->load->helper('validation');   // date_db2ht() pour les colonnes de dates
        $rows = array();
        foreach ($this->agenda_google_liaisons_model->suspended() as $row) {
            $rows[] = array(
                'id'                   => $row['id'],
                'membre'               => trim($row['mprenom'] . ' ' . $row['mnom']) . ' (' . $row['mlogin'] . ')',
                'etat'                 => $this->lang->line('agenda_google_etat_suspendue'),
                'cause_suspension'     => $row['cause_suspension'],
                'updated_at'           => $row['updated_at'],
                'derniere_reussite_at' => $row['derniere_reussite_at'],
            );
        }
        $this->gvvmetadata->store_table('vue_agenda_google_suspendues', $rows);
        load_last_view('agenda_google_liaisons/index', array(
            'controller'     => $this->controller,
            'count'          => count($rows),
            'module_enabled' => $this->agenda_google_sync->module_enabled(),
        ));
    }
}
