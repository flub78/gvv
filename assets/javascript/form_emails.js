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
 * Fonctions Javascript de controle du formulaire de saisie des emails
 * 
 */

/**
 * Changement du selecteur d'email
 */
function email_change() {
	
	var selection = $("#selection").val();
	// alert('email_change = ' + selection);

	// Calcul l'URL en relatif par rapport à la page courante
	var path = window.location.pathname;
	var splitted = path.split('/');
	var last = splitted.pop();
	while (last != 'create' && last != 'edit' && (splitted.length > 0)) {
		last = splitted.pop();
	}
	splitted.push('ajax_email_info');  
	var url = splitted.join('/');  
	  	  
	// Fetch des informations correspondantes
	// alert('url=' + url);
	$.ajax({
	       url : url,
	       type : 'POST',
	       data : 'selection=' + selection,
	       success : function(code_html, statut){
		       // alert("success: " + code_html);
		       var reply = JSON.parse(code_html);

		       /*
	           alert(code_html + " --> " 
			           + "destinataires:" + reply.destinataires
			           );
			   */
	           $("#destinataires").val(reply.destinataires);
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

	// recharge le selecteur d'adresses
	$("#selection").change(email_change);

});

