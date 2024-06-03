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
 * Fonctions Javascript de controle de formulaire.
 * 
 */

/**
 * 
 */
function Autotab(id, longueur, texte) {
	if (texte.length > longueur - 1) {
		document.getElementById("" + id).focus();
	}
}

/*
 * calcul du montant de l'essence en fonction de la quantité et du prix par litre
 */
 
function calculpompe() {
	if (document.saisie.pqte.value != "" && document.saisie.ppu.value != "") {
		var vqte = window.document.saisie.pqte.value;
		var select_list_field = window.document.saisie.ppu ;
		var select_list_selected_index = select_list_field.selectedIndex;

		var putext = select_list_field.options[select_list_selected_index].text;
		var puval= putext.substr( putext.lastIndexOf(" ") );

	
		
		if (isNaN(vqte)) {
			alert("La valeur en quantité n'est pas un nombre");
			return;
			}
		if (isNaN(puval)) {
			alert("La valeur du tarif n'est pas un nombre");
			return;
			}
		var resultat = Math.round(Math.abs(parseFloat(vqte)) * parseFloat(puval)*100)/100 ;
		if (resultat > 0) {
			window.document.saisie.pprix.value = resultat;
			} else {
			alert("Le prix est nul ou négatif");
			}
		
		}
}

/**
 * transforme une valeur heure.minute en heure.centieme
 * 532.3 -> 532.5
 * @returns
 */
function to_hundredth (hm) {
	var hours = hm | 0;
	var minutes = Math.round((hm - hours) * 100);
	if (minutes > 59) {
		alert("minutes = " + minutes + " > 59");
		return -1;
	}
	var centiemes = minutes / 60;
	var result = Math.round((hours + centiemes) * 100) / 100;
	return result;
}

/**
 * transforme une valeur heure.centieme en heures . "h" . minutes
 * 532.5 -> 532h30
 * @returns
 */
function to_hours_mins(hc) {
	var hours = hc | 0;
	var centiemes = (hc - hours) * 100;
	var minutes = Math.round((centiemes * 60) / 100);
	var result = hours + "h" + minutes;
	// alert("to_hours_mins(" + hc + ")=" + result);
	return result;
}

/*
 * Calcul de la durée en 1/100 eme horamètre <FORM name="saisie" <INPUT
 * name="deb" type="text" size="5" maxlength="8" value="" onChange="calcul()">
 * <INPUT name="fin" type="text" size="5" maxlength="8" value=""
 * onChange="calcul()"> <div ID="dure">
 */
function calcul() {
	if (document.saisie.vacdeb.value != ""
			&& document.saisie.vacfin.value != "") {
		var vdeb = window.document.saisie.vacdeb.value;
		var vfin = window.document.saisie.vacfin.value;
		var time_error = document.getElementById("time_error");

		time_error.innerText = "";

		if (isNaN(vdeb)) {
			time_error.innerText = "La valeur de début n'est pas un nombre";
			return;
		}
		if (isNaN(vfin)) {
			time_error.innerText = "La valeur de fin n'est pas un nombre";
			return;
		}
		
		// Recherche si une machine a son horamètre en minutes
		// var str = "";
		var selected_machine = $('#vamacid').val();
		var hem;
		$('[name="machines[]"] ').each(function () {
		    var id = $(this).val();
		    if (id == selected_machine) {
		    	var value = '[name="' + 'horametres_en_min[' + id + ']' + '"]';
		    	hem = $(value).val();
		    	// str += "" + id + " => " + value + " = " + hem + "\n";
		    }
		});

		// calcul du resultat en 1/100
		var deb, fin;
		if (hem == 1) {
			deb = to_hundredth(parseFloat(vdeb));
			if (deb < 0) return;
			fin = to_hundredth(parseFloat(vfin));
			if (fin < 0) return;
		} else {
			deb = parseFloat(vdeb);
			fin = parseFloat(vfin);
		}
		var resultat = Math.round((fin - deb) * 100) / 100;

		if (resultat > 0) {
			if (hem == 1) {
				resultat = to_hours_mins(resultat);
			} 
			window.document.saisie.vaduree.value = resultat;
			document.getElementById("vaduree").innerHTML = resultat;
		} else {
			time_error.innerText = "La durée est nulle ou négative";
		}
	}
}

/*
 * Calcul de la durée pour les vols de planeur <FORM name="saisie" <INPUT
 * name="deb" type="text" size="5" maxlength="8" value="" onChange="calcul()">
 * <INPUT name="fin" type="text" size="5" maxlength="8" value=""
 * onChange="calcul()"> <div ID="dure">
 */
function calculp() {
	if (document.saisie.vpcdeb.value != "" && document.saisie.vpcfin.value != "") {
		var vdeb = window.document.saisie.vpcdeb.value;
		var vfin = window.document.saisie.vpcfin.value;
		var time_error = document.getElementById("time_error");

		time_error.innerText = "";

		/* normalise */
		vdeb = vdeb.replace(':', '.');
		vdeb = vdeb.replace(',', '.');
		vdeb = vdeb.replace('h', '.');

		vfin = vfin.replace(':', '.');
		vfin = vfin.replace(',', '.');
		vfin = vfin.replace('h', '.');

		if (isNaN(vdeb)) {
			time_error.innerText = "La valeur de début " + vdeb + " n'est pas un nombre";
			return;
		}
		if (isNaN(vfin)) {
			time_error.innerText = "La valeur de fin n'est pas un nombre";
			return;
		}
		var debe = Math.floor(parseFloat(vdeb));
		var debd = Math.round(((parseFloat(vdeb) - debe)) * 100);
		var fine = Math.floor(parseFloat(vfin));
		var find = Math.round((parseFloat(vfin) - fine) * 100);
		if (debe > 23 || debd > 59) {
			time_error.innerText = "La valeur de début est incorrecte";
			return;
		}
		if (fine > 23 || find > 59) {
			time_error.innerText = "La valeur de début est incorrecte";
			return;
		}

		var diff = ((fine * 60) + find) - ((debe * 60) + debd);

		if (diff > 0) {
			var rese = Math.floor(diff / 60);
			var resd = Math.round(diff - (rese * 60));
			if (resd < 10) {
				var resdaff = "0" + resd;
			} else {
				var resdaff = resd;
			}
			var result = "" + rese + ":" + resdaff;
			window.document.saisie.vpduree.value = result;
		} else {
			window.document.saisie.vpduree.value = '';
			time_error.innerText = "La durée est nulle ou négative";
		}
	} else {
		window.document.saisie.vpduree.value = '';
	}
}

/**
 * Changement d'élément géré par GVV controller
 */
function new_selection(f) {
	if (f == undefined)
		f = "edit";
	var selector = document.getElementById('selector');
	var selected = selector.options[selector.selectedIndex].value;

	var controllers = document.getElementsByName('controller_url');
	if (controllers.length < 1) {
		alert('controller_url not found');
	} else {
		var url = controllers[0].value + "/" + f + "/" + selected;
		window.location.href = url;
	}
}

/**
 * Changement d'année
 */
function new_year() {
	var selector = document.getElementById('year_selector');
	var selected = selector.options[selector.selectedIndex].value;

	var controllers = document.getElementsByName('controller_url');
	if (controllers.length < 1) {
		alert('controller_url not found');
	} else {
		var url = controllers[0].value + "/new_year/" + selected;
		window.location.href = url;
	}
}

/**
 * Changement de type de licence
 */
function new_licence() {
	var selector = document.getElementById('licence_selector');
	var selected = selector.options[selector.selectedIndex].value;

	var controllers = document.getElementsByName('controller_url');
	if (controllers.length < 1) {
		alert('controller_url not found');
	} else {
		var url = controllers[0].value + "/switch_to/" + selected;
		window.location.href = url;
	}
}

/**
 * Changement de personne pour l'alarm
 */
function new_alarm() {
	var controller_url = $('[name="controller_url"]').val();
	var selected = $('#selector').val()
	var url = controller_url + '/index/' + selected;
	window.location.href = url;
}

/**
 * Changement de taille de page
 */
function per_page() {
	var selected = $('#per_page').val();
	
	var previous_url = document.URL;
	var controllers = document.getElementsByName('controller_url');
	if (controllers.length < 1) {
		alert('controller_url not found');
	} else {
		var url = controllers[0].value + "/set_per_page/" + selected;
		// alert(url);
		window.location.href = url;
	}
}

/**
 * Selection d'un nouveau compte sur la page journal
 */
function compte_selection() {

	var selector = document.getElementById('selector');
	var selected = selector.options[selector.selectedIndex].value;

	var controllers = document.getElementsByName('controller_url');
	if (controllers.length < 1) {
		alert('controller_url not found');
	} else {
		var url = controllers[0].value + "/journal_compte/" + selected;
		// alert('controller_url found: ' + url);
		window.location.href = url;
	}
}

/**
 * Selection d'un nouveau compte sur la page journal
 */
function query_selection() {

	var selector = document.getElementById('query_selector');
	var selected = selector.options[selector.selectedIndex].value;

	var controllers = document.getElementsByName('controller_url');
	if (controllers.length < 1) {
		alert('controller_url not found');
	} else {
		var url = controllers[0].value + "/query/" + selected;
		// alert('controller_url found: ' + url);
		window.location.href = url;
	}
}

/**
 * On a changé la valeur de la checkbox d'une ligne d'écriture
 */
function line_checked(id, state, compte, premier) {

	// alert('line_checked(' + id +  ', ' + state + ')');

	var controllers = document.getElementsByName('controller_url');
	if (controllers.length < 1) {
		alert('controller_url not found');
	} else {
		var url = controllers[0].value + "/switch_line/" + id + "/" + state + "/" 
			+ compte + "/" + premier;
		window.location.href = url;
	}
}

function testbiplace() {
	var biplace = window.document.saisie.planeur.value;
	if (biplace.charAt(0) == "B")
		document.getElementById('inst').style.display = 'block';
	else
		document.getElementById('inst').style.display = 'none';
}

/**
* Affiche/cache un élément en fonction de sa visibilité actuelle
*/
function show_hide_block(id){
	if (id.style.display != "none")
		id.style.display = "none";
	else
		id.style.display = "block";
}

/**
* Affiche le selector avion ou planeur
*/
function get_plane_selector(dropdown_id){
	if (dropdown_id.value == "aucun"){
		document.getElementById('dropdown_planeurs').style.display = "none";
		document.getElementById('dropdown_avions').style.display = "none";
	}
	else if (dropdown_id.value == "planeur"){
		document.getElementById('dropdown_avions').style.display = "none";
		document.getElementById('dropdown_planeurs').style.display = "block";
	}
	else if (dropdown_id.value == "avion"){
		document.getElementById('dropdown_planeurs').style.display = "none";
		document.getElementById('dropdown_avions').style.display = "block";
	}
}


/**
 * Navigation depuis un élément de table
 */
function goto(pilote) {
    var selector = document.getElementById('goto');
    var selected = selector.options[selector.selectedIndex].value;

    alert ('goto ' + selected + ', pilote=' + pilote + ', selected =' + selector.selectedIndex);
    return;
    
    var previous_url = document.URL;
    var controllers = document.getElementsByName('controller_url');
    if (controllers.length < 1) {
        alert('controller_url not found');
    } else {
        var url = controllers[0].value + "/set_per_page/" + selected;
        // alert(url);
        window.location.href = url;
    }
}

