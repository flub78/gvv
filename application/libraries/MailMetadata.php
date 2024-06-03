<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

include_once ("Gvvmetadata.php");

/**
 *
 * Metadata for Mail
 *
 * @author idefix
 * @package librairies
 */
class MailMetadata extends GVVMetadata {
    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        /**
         * Courriels
         */
        $this->keys ['vue_mails'] = 'id';
        $this->alias_table ["vue_mails"] = "mails";

        $this->field ['mails'] ['id'] ['Name'] = 'Numéro';
        $this->field ['mails'] ['date_envoie'] ['Name'] = 'Date';
        $this->field ['mails'] ['titre'] ['Name'] = 'Sujet';
        $this->field ['mails'] ['selection'] ['Name'] = 'Selection';

        $this->field ['vue_mails'] ['selection'] ['Subtype'] = 'enumerate';
        $this->field ['vue_mails'] ['selection'] ['Enumerate'] = $this->CI->config->item('listes_de_destinataires');

        $this->field ['mails'] ['individuel'] ['Subtype'] = 'boolean';
        $this->field ['mails'] ['destinataires'] ['Attrs'] = array (
                'cols' => 96,
                'rows' => 4
        );

        $this->field ['mails'] ['texte'] ['Attrs'] = array (
                'cols' => 96,
                'rows' => 16
        );
        $this->field ['mails'] ['titre'] ['Title'] = "Sujet du courriel";
        $this->field ['mails'] ['destinataires'] ['Title'] = "Destinataires séparés par des ,";
        $this->field ['mails'] ['copie_a'] ['Title'] = "Personnes en copie séparés par des ,";
        $this->field ['mails'] ['individuel'] ['Title'] = "Envois individuels ou à la liste";
        $this->field ['mails'] ['texte'] ['Title'] = "Pour les envois individuels, les variables \$SOLDE, \$NOM, \$PRENOM, \$ADRESSE, \$CP, \$VILLE sont remplacées.
        Ex: si vous tapez Bonjour \$PRENOM, dans le texte, le destinataire recevra Bonjour Jules, (s'il s'appelle Jules)";

        $this->field ['mails'] ['selection'] ['Subtype'] = 'enumerate';
        $this->field ['mails'] ['selection'] ['Enumerate'] = $this->CI->config->item('listes_de_destinataires');

        // $this->dump();
    }
}