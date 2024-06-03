//    GVV Gestion vol à voile
//    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// Fonctions Javascript de controle de formulaire.

// alert("Bienvenue sur GVV");

function Autotab(id, longueur, texte) {
	if (texte.length > longueur - 1) {
		document.getElementById("" + id).focus();
	}
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
		if (isNaN(vdeb)) {
			alert("La valeur de début n'est pas un nombre");
			return;
		}
		if (isNaN(vfin)) {
			alert("La valeur de fin n'est pas un nombre");
			return;
		}
		var resultat = Math.round((parseFloat(vfin) - parseFloat(vdeb)) * 100) / 100;
		if (resultat > 0) {
			window.document.saisie.vaduree.value = resultat;
			document.getElementById("vaduree").innerHTML = resultat;
		} else {
			alert("La durée est nulle ou négative");
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
		if (isNaN(vdeb)) {
			alert("La valeur de début n'est pas un nombre");
			return;
		}
		if (isNaN(vfin)) {
			alert("La valeur de fin n'est pas un nombre");
			return;
		}
		var debe = Math.floor(parseFloat(vdeb));
		var debd = Math.round(((parseFloat(vdeb) - debe)) * 100);
		var fine = Math.floor(parseFloat(vfin));
		var find = Math.round((parseFloat(vfin) - fine) * 100);
		if (debe > 23 || debd > 59) {
			alert("La valeur de début est incorrecte");
			return;
		}
		if (fine > 23 || find > 59) {
			alert("La valeur de début est incorrecte");
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
			var result = "" + rese + "h" + resdaff;
			window.document.saisie.vpduree.value = result;
		} else {
			alert("La durée est nulle ou négative");
		}
	}
}

function new_selection() {

	var selector = document.getElementById('selector');
	var selected = selector.options[selector.selectedIndex].value;
	// alert("mlogin changed " + selected);

	var controllers = document.getElementsByName('controller_url');
	if (controllers.length < 1) {
		alert('controller_url not found');
	} else {
		var url = controllers[0].value + "/edit/" + selected;
		// alert('controller_url found: ' + url);
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
