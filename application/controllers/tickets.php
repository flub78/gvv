<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http: *www.gnu.org/licenses/>.
 *
 * @filesource tickets.php
 * @package controllers
 */
include ('./application/libraries/Gvv_Controller.php');

/**
 * Controleur de gestion des tickets de treuillé et de remorqués.
 *
 * A priori la création de tickets (ajout de crédits) devrait se faire
 * à travers les achats et les lignes de débit devrait être généré par les vols.
 * Donc le controleur ne devrait pas être utilisé directement sauf pendant
 * la mise au point.
 *
 * Cas d'utilisation:
 *
 * 1) Achat de tickets
 * Un pilote achète un lot de ticket. L'achat génère une ligne de crédit dans la table ticket.
 *
 * 2) Vol
 * Si le solde de ticket est positif lors de l'achat, l'achat est tranformé en
 * consomation de ticket.
 *
 * C'est facile quand tout les événements sont traités séquentiellement. C'est plus délicat
 * quand on modifie le passé. Par exemple on peut supprimer le vol correspondant à la consomation
 * du dernier ticket d'une série. Dans ce cas, le ticket est recrédité et l'on a un vol (le suivant)
 * qui ne décompte pas de ticket alors qu'il en aurait décompté un si le vol d'avant n'avait
 * pas été crée puis supprimé.
 *
 * Je ne pense pas qu'il soit utile de garantir que l'historique des transactions n'a pas d'impact
 * sur le résultat final. Cela entrainnerait des réactions en chaines sur tout les vols suivant
 * une modification. Il sera toujour possible de modifier manuellement les vols pour prendre en compte
 * les modifications à posteriori. Donc c'est au moment de la prise en compte du vol qu'on vérifiera
 * s'il y reste des tickets ou pas.
 *
 * 3) Modification de vols.
 * Lorsqu'on modifie un vol, on supprime d'abord ses conséquences, achat, écriture comptable
 * avant de les regénérer avec les nouveau paramètres du vol. C'est plus simple que
 * de déterminer quelles sont les conséquences exacte de la modification. Eventuellement
 * cela entraine des modifications uniquement pour une modification de commentaire, mais
 * c'est plus simple donc plus sur à gérer.
 *
 * On ne gére que des incréments positifs ou négatifs sur les tickets, donc l'aanulation
 * d'un achat ou d'un vol entraine seulement l'annulation de la ligne de ticket associée.
 */
class Tickets extends Gvv_Controller {
    protected $controller = 'tickets';
    protected $model = 'tickets_model';
    protected $modification_level = 'ca';

    // régles de validation
    protected $rules = array ();

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->load->model('membres_model');
        $this->load->model('types_ticket_model');
    }

    /**
     * Génération des éléments à passer au formulaire en cas de création,
     * modification ou réaffichage après erreur.
     *
     * @param $action @see
     *            constants.php
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $this->gvvmetadata->set_selector('pilote_selector', $this->membres_model->selector());
        $this->gvvmetadata->set_selector('ticket_selector', $this->types_ticket_model->selector());

        if ($this->data ['date'] == "") {
            $this->data ['date'] = date("d/m/Y");
        } else {
            $this->data ['date'] = date_db2ht($this->data ['date']);
        }
        $this->data ['saisie_par'] = $this->dx_auth->get_username();
    }

    /**
     * Hook called after element creation
     *
     * @param $data tableau
     *            de l'enregistrement à modifier
     */
    function post_create($data = array ()) {
        parent::post_create();
        // echo "post_create GVV tickets<br>";
    }

    /**
     * Affiche le solde de tickets de tous les utilisateurs
     */
    function solde($mode = "") {
        $selection = array ();
        $select = $this->gvv_model->select_totaux($selection, "pilote, type");

        if ('csv' == $mode) {
            $attrs = array (
                    'fields' => array (
                            'pilote',
                            'nom',
                            'solde'
                    ),
                    'mode' => "csv"
            );
            return $this->gvvmetadata->csv("vue_solde_tickets", $attrs);
        } else if ('pdf' == $mode) {
            $this->load->library('Pdf');
            $pdf = new Pdf();

            $pdf->AddPage('P');
            $pdf->title("Soldes tickets par pilote", 1);

            $attrs = array (
                    'fields' => array (
                            'pilote',
                            'nom',
                            'solde'
                    ),
                    'width' => array (
                            50,
                            20,
                            20
                    ),
                    'mode' => "pdf"
            );
            $this->gvvmetadata->pdf("vue_solde_tickets", $pdf, $attrs);
            $pdf->Output();
            return;
        }

        $data ['selection'] = $select;
        load_last_view('tickets/soldes_pilote', $data);
    }

    /**
     * Active/désactive le filtrage des tickets
     *
     * @param $action @see
     *            constants.php
     */
    public function filterValidation($action) {
        $button = $this->input->post('button');

        if ($button == "Filtrer") {
            // Enable filtering
            $session ['filter_date'] = $this->input->post('filter_date');
            $session ['date_end'] = $this->input->post('date_end');
            $session ['filter_pilote'] = $this->input->post('filter_pilote');
            $session ['filter_active'] = 1;
            $this->session->set_userdata($session);
        } else {
            // Disable filtering
            foreach ( array (
                    'filter_date',
                    'date_end',
                    'filter_pilote',
                    'filter_machine',
                    'filter_active'
            ) as $field ) {
                $this->session->unset_userdata($field);
            }
        }
        redirect($this->controller . '/page');
    }

    /**
     * Selectionne les éléments à afficher sur une page
     *
     * @param $premier premier
     *            ticket de la page
     * @param
     *            $pilote
     * @param $per_page nombre
     *            de ticket à afficher par pages
     */
    function select_page($premier = 0, $pilote = '', $per_page = PER_PAGE) {
        if ($premier == 0 && $ajax = $this->config->item('ajax')) {
            $per_page = 1000000000;
        }

        $this->data ['action'] = VISUALISATION;
        $this->data ['filter_active'] = $this->session->userdata('filter_active');

        $this->data ['filter_date'] = '';
        $this->data ['date_end'] = '';
        $this->data ['filter_pilote'] = '';
        $this->data ['filter_machine'] = '';
        $this->data ['planchiste'] = $this->dx_auth->is_role('planchiste', true, true);
        $selection = array ();

        $pilote_selector = array (
                '' => ''
        );
        $pilote_selector = $this->membres_model->selector_with_null(array (
                'actif' => 1
        ));
        $this->data ['pilote_selector'] = $pilote_selector;

        $order = "desc";

        if ($this->session->userdata('filter_active') or ($pilote != '')) {
            $order = "asc";

            $selection = "";
            $filter_pilote = $this->session->userdata('filter_pilote');
            if ($filter_pilote) {
                $this->data ['filter_pilote'] = $filter_pilote;
                $selection = "(tickets.pilote = \"$filter_pilote\"  )";
                $this->data ['solde_pilote'] = $this->gvv_model->solde($filter_pilote);
            } else if ($pilote != '') {
                $selection = "(tickets.pilote = \"$pilote\"  )";
                $this->data ['solde_pilote'] = $this->gvv_model->solde($pilote);
            }

            $filter_date = $this->session->userdata('filter_date');
            $date_end = $this->session->userdata('date_end');
            if ($filter_date) {
                if ($selection != '')
                    $selection .= " and ";
                $this->data ['filter_date'] = $filter_date;
                if ($date_end) {
                    $selection .= "tickets.date >= \"" . date_ht2db($filter_date) . "\" ";
                } else {
                    $selection .= "tickets.date = \"" . date_ht2db($filter_date) . "\" ";
                }
            }

            if ($date_end) {
                if ($selection != '')
                    $selection .= " and ";
                $this->data ['date_end'] = $date_end;
                $selection .= "tickets.date <= \"" . date_ht2db($date_end) . "\" ";
            }

            if ($selection == "")
                $selection = array ();
        }

        $this->data ['select_result'] = $this->gvv_model->select_page($per_page, $premier, $selection, $order);
        $this->data ['kid'] = $this->kid;
        $this->data ['controller'] = $this->controller;

        $this->data ['count'] = $this->gvv_model->count($selection);
        $this->data ['premier'] = $premier;
    }

    /**
     * Affiche une page d'éléments
     *
     * @param
     *            $premier
     * @param
     *            $pilote
     */
    function page($premier = 0, $pilote = '', $lien = FALSE) {
        $this->push_return_url("Tickets");

        if (! $this->dx_auth->is_role('ca', true, true)) {
            // Si le pilote n'est pas autorisé, restriction à ses tickets
            $pilote = $this->dx_auth->get_username();
            $lien = TRUE;
        }

        if ($pilote != '') {
            $info_pilote = $this->membres_model->get_by_id('mlogin', $pilote);
            if ($info_pilote ['compte'] && $lien) {
                $this->load->model('comptes_model');
                $account = $this->comptes_model->get_by_id('id', $info_pilote ['compte']);
                $pilote = $account ['pilote'];
            }
            $this->data ['nom'] = $this->membres_model->image($pilote);
        } else {
            $this->data ['nom'] = '';
        }
        $this->data ['has_modification_rights'] = (! isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));

        $this->select_page($premier, $pilote);
        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * Affiche une page d'éléments
     *
     * @param
     *            $premier
     * @param
     *            $pilote
     */
    function export($mode = "csv", $pilote = '') {
        if (! $this->dx_auth->is_role('ca', true, true)) {
            $pilote = $this->dx_auth->get_username();
        }
        $this->select_page(0, $pilote, 10000);
        if ('csv' == $mode) {
            $attrs = array (
                    'fields' => array (
                            'date',
                            'pilote',
                            'quantite',
                            'nom',
                            'description',
                            'vol'
                    )
            );
            $this->gvvmetadata->csv("vue_tickets", $attrs);
        } else {
            $this->load->library('Pdf');
            $pdf = new Pdf();

            $pdf->AddPage('P');
            $pdf->title("Gestion des tickets", 1);

            $attrs = array (
                    'fields' => array (
                            'date',
                            'pilote',
                            'quantite',
                            'nom',
                            'description',
                            'vol'
                    ),
                    'width' => array (
                            20,
                            45,
                            15,
                            30,
                            50,
                            30
                    ),
                    'mode' => "pdf"
            );
            $this->gvvmetadata->pdf("vue_tickets", $pdf, $attrs);
            $pdf->Output();
        }
    }

    /**
     * Visualise les tickets pour un pilote
     *
     * @param unknown_type $pilote
     */
    function view($pilote) {
        $this->page(0, $pilote);
    }

    /*
     * Fonction de migration des données. Usage unique.
     *
     */
    function cleanup() {
        echo "cleanup" . br();
        $achats = $this->gvv_model->select_raw();
        $pattern = '/(.*)(, reste=(\d+))(.*)/';
        foreach ( $achats as $key => $row ) {
            $description = $row ['description'];
            if (preg_match($pattern, $description, $matches)) {
                $avant = $matches [1];
                $reste = $matches [2];
                $apres = $matches [4];
                echo $avant . '---|' . $reste . '|---' . $apres . br();
                $replace = $matches [1] . $matches [4];
                $row ['description'] = $replace;
                // var_dump($row);
                // echo "replace=$replace" . br();

                $this->db->where('id', $row ['id']);
                $this->db->update('tickets', $row);
            }
        }
    }

}