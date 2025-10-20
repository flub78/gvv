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
 * Vue planche (table) pour le plan comptable
 * 
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

echo '<div id="body" class="body container-fluid">';

echo heading("Plan comptable", 3);
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

$attrs = array(
	'controller' => $controller,
    'actions' => array ('edit', 'delete'),
    'fields' => array('pcode', 'pdesc'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'width' => array(50, 400),
    'class' => "datatable_style datatable_server table table-striped");

    // Create button above the table
echo '<div class="mb-3">'
    . '<a href="' . site_url('plan_comptable/create') . '" class="btn btn-sm btn-success">'
    . '<i class="fas fa-plus" aria-hidden="true"></i> '
    . $this->lang->line('gvv_button_create')
    . '</a>'
    . '</div>';

echo $this->gvvmetadata->empty_table("planc", $attrs);

$bar = array(
    array('label' => "Excel", 'url' => "$controller/export/csv", 'role' => 'ca'),
    array('label' => "Pdf", 'url' => "$controller/export/pdf", 'role' => 'ca'),
);
echo button_bar4($bar);

echo '</div>';

?>
<script language="JavaScript">
<!--
	 
$(document).ready(function(){
    // notre code ici
    $('.datatable_server').dataTable({
    	"bServerSide": true,
    	"sAjaxSource": "ajax_page",
        "bFilter": true,
        "bPaginate": true,
        "iDisplayLength": 25,
        "bStateSave": false,
        "bJQueryUI": true,
        "bSort": true,
        "bInfo": true,
        "bAutoWidth": true,
        "sPaginationType": "full_numbers",
        "oLanguage": {
                "sProcessing":     "Traitement en cours...",
                "sLengthMenu":     "Afficher _MENU_ &eacute;l&eacute;ments",
                "sZeroRecords":    "Aucun &eacute;l&eacute;ment &agrave; afficher",
                "sInfo":           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
                "sInfoEmpty":      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
                "sInfoFiltered":   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
                "sInfoPostFix":    "",
                "sSearch":         "Rechercher&nbsp;:",
                "sLoadingRecords": "Téléchargement...",
                "sUrl":            "",
                "oPaginate": {
                    "sFirst":    "Premier",
                    "sPrevious": "Pr&eacute;c&eacute;dent",
                    "sNext":     "Suivant",
                    "sLast":     "Dernier"
                }                       
        }
    });
    
});


	//-->
	</script>

