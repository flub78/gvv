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
 * @package javascript
 * 
 * Fonctions Javascript de controle du formulaire de saisie des vols avions
 * 
 */

/**
 * Affiche ou pas l'instructeur en fonction du champ DC
 */
function show_instruction () {
    var dc = $("#vadc:checked").val();
	if (dc == 1) {
		 $("#instruction").show();
	} else {
		$("#vainst").val('');
		$("#instruction").hide();			
	}
}

/**
 * N'affiche pas le payeur pour les VI et VE
 */
function show_payeur () {
    var gratuit = $("#vave:checked").val() || $("#vavi:checked").val();
	if (gratuit == 1) {
		 $(".payeur").hide();
	} else {
		 $(".payeur").show();			
	}
}

/**
 * MAJ des caractéristiques dépendantes de la machine
 */
function update_machine() {
	  // C'est assez facile d'obtenir l'immatriculation de la machine selectionné
	  var machine = $("#vamacid").val();
	  // alert ("machine=" + machine);

	  // Calcul l'URL en relatif par rapport à la page courante
	  var path = window.location.pathname;
	  var splitted = path.split('/');
	  var last = splitted.pop();
	  while (last != 'create' && last != 'edit' && (splitted.length > 0)) {
		  last = splitted.pop();
	  }
	  splitted.push('ajax_machine_info');  
	  var url = splitted.join('/');  
	  	  
	  // Le problème maintenant est de savoir s'il s'agit d'un biplace,
	  // la dernière valeur d'horamètre ainsi que l'unité de l'horamètre
	  // alert('url=' + url);
	  // Mise à jour immédiate du format horamètre à partir des données préchargées
	  update_hora_format(machine);

	  $.ajax({
	       url : url,
	       type : 'POST',
	       data : 'machine=' + machine,
	       success : function(code_html, statut){
		       var avion = JSON.parse(code_html);

	           if (avion.places > 1) {
		           $('.DC').show();
		           $('.VI').show();
	           } else {
		           $('.DC').hide();
		           $('.VI').hide();
		       }

	           if ($("#fin").val() == '') {
	        	   $("#debut").val(avion.hora);
	           }
	       },

	       error : function(resultat, statut, erreur){
	           alert("error");
	       },

	       complete : function(resultat, statut){
	           // alert("complete");
	       }
	  });	
}

function mode_to_unit(mode) {
	if (mode === 1) {
		return "min";
	}
	if (mode === 2) {
		return "tenth";
	}
	return "cent";
}

function hora_unit_label(unit) {
	if (unit == "tenth") {
		return "1/10h";
	}
	if (unit == "min") {
		return hm;
	}
	return h_100;
}

function update_hora_format(machine) {
	var selected_machine = machine || $("#vamacid").val();
	if (!selected_machine) {
		$("#hora_format").text('');
		return;
	}
	var mode = 0;
	if (typeof horametres_modes_data !== 'undefined' &&
	    horametres_modes_data.hasOwnProperty(selected_machine)) {
		mode = parseInt(horametres_modes_data[selected_machine], 10);
		if (isNaN(mode)) mode = 0;
	}
	var unit = mode_to_unit(mode);
	var label = horametre + " " + selected_machine + " " + hora_unit_label(unit);
	$("#hora_format").text(label);
}

//Le code JQuery n'est actif et testable qu'avec un accès internet
$(document).ready(function(){

	// Cache le champ instruction si ce n'est pas un vol DC
	$("#vadc").change(show_instruction);
	show_instruction();

	$("#vave").change(show_payeur);
	$("#vavi").change(show_payeur);
	show_payeur();
	
	$("#vamacid").change(update_machine);
	update_machine();	

});

