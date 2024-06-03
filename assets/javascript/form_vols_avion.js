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
	  $.ajax({
	       url : url,
	       type : 'POST',
	       data : 'machine=' + machine,
	       success : function(code_html, statut){
		       // alert("success: " + code_html);
		       var avion = JSON.parse(code_html);
	
		       /*	
	           alert(code_html + " --> " 
			           + "id:" + avion.machine
			           + "places:" + avion.places
			           + "unit:" + avion.unit
			           + "hora:" + avion.hora
			           );
	  	     
		       */
	           
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
	           
	           var format_hora;
	           if (avion.unit == "cent") {
	        	   format_hora = horametre + " " + avion.machine + " " + h_100;
	           } else {
	        	   format_hora = horametre + " " + avion.machine + " " + hm;	        	   
	           }
	           var txt = $("#hora_format").text();
	           // alert(format_hora + " avion.unit = " + avion.unit + " -> " + txt);
	           $("#hora_format").text(format_hora);
	       },

	       error : function(resultat, statut, erreur){
	           alert("error");
	       },

	       complete : function(resultat, statut){
	           // alert("complete");
	       }
	  });	
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

