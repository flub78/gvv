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
 * Formulaire de passage d'écritures
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('compta');
$this->lang->load('attachments');

echo '<div id="body" class="body container-fluid">';

echo checkalert($this->session, isset($popup) ? $popup : "");
echo heading($title_key, 3);

?>

<div class="d-flex flex-row flex-wrap">
    <div>
        <?php
        if (isset($message)) {
            echo p($message) . br();
        }

        echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

        // hidden contrller url for java script access
        echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
        echo form_hidden('saisie_par', $saisie_par, '');
        echo form_hidden('annee_exercise', $annee_exercise, '');


        // On affiche tous les champs dans un tableau. C'est plus simple de remplir d'abbord le tableau
        // et de l'afficher ensuite, surtout pour modifier l'affichage

        echo form_hidden('id', $id);
        echo form_hidden('date_creation', $date_creation);
        echo form_hidden('title', $this->lang->line($title_key));
        echo form_hidden('categorie', 0);

        echo validation_errors();
        echo ($this->gvvmetadata->form('ecritures', array(
            'date_op' => $date_op,
            //	'annee_exercise' => $annee_exercise,
            'compte1' => $compte1,
            'compte2' => $compte2,
            'montant' => $montant,
            'description' => $description,
            'num_cheque' => $num_cheque,
            //     'categorie' => $categorie,
            'gel' => $gel
        )));

        echo validation_button($action);
        echo form_close();
        ?>
    </div>
    <div class="ms-4">
        <?php
        echo heading("gvv_attachments_title", 3);

        $attrs = array(
            'controller' => "attachments",
            'actions' => array('edit', 'delete'),
            'fields' => array('description', 'file'),
            'mode' => "rw",
            'class' => "fixed_datatable table table-striped",
            'param' => "?table=ecritures&id=" . $id
        );

        echo $this->gvvmetadata->table("vue_attachments", $attrs, "");
        ?>
    </div>

    <script type="text/javascript" src="<?php echo js_url('form_ecriture'); ?>"></script>