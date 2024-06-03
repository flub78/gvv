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
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @filesource mails.php
 * @package controllers
 * Controleur de gestion des mails.
 */
include ('./application/libraries/Gvv_Controller.php');
class Mails extends Gvv_Controller {
    protected $controller = 'mails';
    protected $model = 'mails_model';
    protected $modification_level = 'ca';
    protected $rules = array ();

    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();
        $this->load->library('MailMetadata');
        $this->load->model('membres_model');
        $this->load->model('comptes_model');
        $this->load->library('email');
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form_static_element()
     */
    function form_static_element($action) {
        parent::form_static_element($action);

        $this->load->model('membres_model');
        $selection = $this->config->item('listes_de_destinataires');

        if ($action == CREATION) {
            $this->data ['copie_a'] = $this->config->item('copie_a');
            $this->data ['destinataires'] = $this->membres_model->emails();
        }
    }

    /**
     * Validates the configuration changes
     */
    public function formValidation($action) {
        $button = $this->input->post('button');

        if ($button == "Envoyer") {
            parent::formValidation($action, true);
            $this->send();
            return;
        }
        parent::formValidation($action);
    }

    /*
     * Envoie de l'email
     *
     * $this->data =
     * 'id' => string '0' (length=1)
     * 'titre' => string 'Test 3' (length=6)
     * 'destinataires' => string 'frederic.peignot@free.fr; mathieu.caudrelier@free.fr' (length=52)
     * 'copie_a' => string '' (length=0)
     * 'selection' => string '5' (length=1)
     * 'individuel' => string '1' (length=1)
     * 'date_envoie' => string '0000-00-00 00:00:00' (length=19)
     * 'texte' => string 'Salut' (length=5)
     * 'debut_facturation' => string '' (length=0)
     * 'fin_facturation' => string '' (length=0)
     */
    private function send() {
        $timestamp = date("Y-m-d H:i:s");
        $this->data ['date_envoie'] = $timestamp;

        if ($this->data ['debut_facturation']) {
            $this->data ['debut_facturation'] = date_ht2db($this->data ['debut_facturation']);
        }
        if ($this->data ['fin_facturation']) {
            $this->data ['fin_facturation'] = date_ht2db($this->data ['fin_facturation']);
        }
        $this->gvv_model->update('id', $this->data);

        // var_dump($this->data);
        $destinataires = $this->data ['destinataires'];
        $cc = $this->data ['copie_a'];
        $from = $this->config->item('email_club');
        $subject = $this->data ['titre'];
        // $message = iconv('UTF-8', 'windows-1252', $this->data['texte']);
        $message = $this->data ['texte'];

        $errors = "";

        if ($this->data ['individuel']) {
            // envoie d'un mail à chaque destinataire.

            $dests = explode(", ", $this->data ['destinataires']);
            // pour chaque email destinataire
            foreach ( $dests as $dest ) {

                // Recherche du/des pilote(s) correspondant
                $pilote_info_list = $this->membres_model->pilote_with_email($dest);
                ;

                if (count($pilote_info_list)) {
                    // on a trouvé des fiches pilotes correspondante
                    foreach ( $pilote_info_list as $row ) {
                        // on personalise le message
                        $info = array (
                                '#\$PRENOM#' => $row ['mprenom'],
                                '#\$NOM#' => $row ['mnom'],
                                '#\$ADRESSE#' => $row ['madresse'],
                                '#\$CP#' => $row ['cp'],
                                '#\$VILLE#' => $row ['ville'],
                                '#\$SOLDE#' => $row ['solde']
                        );

                        /*
                         * foreach ($info as $key => $value) {
                         * $info[$key] = iconv('UTF-8', 'windows-1252', $value);
                         * }
                         */

                        $message_perso = $this->personalise_message($message, $info);
                        // et on l'envoie
                        $errors .= $this->email($dest, $cc, $from, $subject, $message_perso);
                    }
                } else {
                    // on a pas trouvé de fiche associées à l'adresse email
                    // On envoie le message non personnalisé
                    $errors .= $this->email($dest, $cc, $from, $subject, $message);
                }
            }
        } else {
            // envoie à la liste
            $errors = $this->email($destinataires, $cc, $from, $subject, $message);
        }

        if ($errors) {
            $data ['text'] = $errors;
        } else {
            $msg = "Le courriel \"$subject\" a été envoyé avec succès à $destinataires.";
            $data ['text'] = $msg;
        }

        $data ['title'] = 'Mail envoyé';
        $this->load->view('message', $data);
    }

    /**
     * Personalise un message en fonction des info pilote fournies dans $pilote_info
     *
     * $pilote_info est un hash avec des mots clé à remplacer
     *
     * $pilote_ingo = array (
     * '#\$SOLDE#' => '-280',
     * '#\$PRENOM#' => 'Fred',
     * '#\$NOM#' => 'Durand'
     * );
     *
     * @param unknown_type $message
     * @param unknown_type $pilote_info
     *            hash table
     */
    function personalise_message($message, $pilote_info) {
        $message_perso = $message;
        // gvv_debug("perso $message " . var_export($pilote_info));
        foreach ( $pilote_info as $key => $value ) {
            // echo "key=$key, value=$value" . br();
            $message_perso = preg_replace($key, $value, $message_perso);
        }
        return $message_perso;
    }

    /**
     * Retourne la liste d'email
     */
    function ajax_email_info() {
        $selection = $this->input->post('selection');

        gvv_debug("ajax_email_info selection=$selection");
        $queries = $this->config->item('listes_de_requetes');
        $query = $queries [$selection];

        $destinataires = $this->membres_model->emails($query);

        $json = '{';
        $json .= "\"destinataires\": \"$destinataires\"";
        $json .= "}";
        echo $json;
    }

    /**
     * Envoie d'un courriel
     */
    function email($to, $cc, $from, $subject, $message, $attach = "") {
        $this->email->clear();
        $config ['wordwrap'] = TRUE;
        $config ['mailtype'] = 'text';
        $config ['charset'] = 'utf-8';
        $this->email->initialize($config);

        $this->email->from($from, 'Gestion vol à voile');
        $this->email->to($to);
        $this->email->cc($cc);
        // $this->email->bcc('them@their-example.com');

        $this->email->subject($subject);
        $this->email->message($message);

        if ($attach)
            $this->email->attach($attach);

        if ($this->email->send()) {
            gvv_info("mail to=$to, cc=$cc, from=$from, subject=$subject, attach=$attach");
            return "";
        } else {
            $msg = "Erreur durant l'envoi de courriel à $to " . $this->email->print_debugger();

            gvv_error("mail to=$to, cc=$cc, from=$from, subject=$subject, attach=$attach $msg");
            return $msg;
        }
    }

    /**
     * Test unitaire pour personalise_message
     */
    function test_personalise_message() {
        $msg = '
Hello $PRENOM,

Tu es débiteur de $SOLDE.
';
        $expected = '
Hello Joe,

Tu es débiteur de -100.
';

        $info = array (
                '#\$PRENOM#' => 'Joe',
                '#\$SOLDE#' => - 100
        );
        $replaced = $this->personalise_message($msg, $info);
        $this->unit->run($replaced, $expected, 'personalise_message');
    }

    /**
     * Tests unitaires pour le controleur
     */
    function test_methodes() {
        // $this->unit->run('Foo', 'is_string', 'test mails');
        $this->test_personalise_message();
    }

    /**
     * Generate the ID of a test element
     *
     * @param number $index
     */
    public function test_element_id($index = 0) {
        return $index;
    }

    /**
     * Generate a test element
     *
     * @param number $index
     */
    public function test_element($index = 0) {
        // Crée des mails
        return 0;
    }

    /**
     * Change an element to check if the change is taken into account into DB
     *
     * @param unknown $elt_ref
     */
    public function test_change(&$elt) {
    }

    /**
     * Test unitaire
     */
    function test($format = "html") {
        parent::test($format);

        $this->test_methodes();
        // $this->test_model("mails");

        $this->tests_results($format);
    }
}