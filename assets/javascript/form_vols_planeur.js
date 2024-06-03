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
function show_instruction() {
	var dc = $("#vpdc:checked").val();
	if (dc == 1) {
		$("#instruction").show();
		$("#passager").hide();
	} else {
		$("#vpinst").val('');
		$("#instruction").hide();
		$("#passager").show();
	}
}

/**
 * N'affiche pas le payeur pour les VI et VE
 */
function show_payeur() {
	var gratuit = $("#vpve:checked").val() || $("#vpvi:checked").val();
	if (gratuit == 1) {
		$(".payeur").hide();
	} else {
		$(".payeur").show();
	}
}

/**
 * vpcategorie a changé, affiche ou pas les champs complémentaires
 */
function vpcategorie_changed() {
	var selected = $('input[name=vpcategorie]:checked').val();
	switch (selected) {
		case '0':
			// alert('standard');
			$(".payeur").show();
			$(".VI").hide();
			break;
		case '1':
			// alert('VI');
			$(".payeur").hide();
			$(".VI").show();
			break;
		case '2':
			// alert('VE');
			$(".payeur").hide();
			$(".VI").hide();
			break;
		case '3':
			// alert('Concours');
			$(".payeur").show();
			$(".VI").hide();
			break;
		default:
			alert('default');
			$(".payeur").show();
			$(".VI").show();
			break;
	}
}

/**
 * MAJ des caractéristiques dépendantes de la machine
 */
function update_machine() {
	// C'est assez facile d'obtenir l'immatriculation de la machine selectionné
	var machine = $("#vpmacid").val();
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

	// Le problème maintenant est de savoir s'il s'agit d'un biplace et si elle est
	// treuillable ou autonome
	$.ajax({
		url: url,
		type: 'POST',
		data: 'machine=' + machine,
		success: function (code_html, statut) {
			// alert("success: " + code_html);
			var planeur = JSON.parse(code_html);

			/*
			 alert(code_html + " --> " 
					 + "id:" + planeur.machine
					 + "biplace:" + planeur.biplace
					 + "treuil:" + planeur.treuil
					 + "autonome:" + planeur.autonome
					 );
			 */

			if (planeur.autonome) {
				$('.autonome').show();
			} else {
				$('.autonome').hide();
			}

			if (planeur.biplace > 1) {
				$('#DC').show();
				$('#DC').prop( "checked", false );
				$("#passager").show();
				// $('.VI').show();
			} else {
				$('#DC').hide();
				$("#instruction").hide();
				$("#passager").hide();
				// $('.VI').hide();
			}
		},

		error: function (resultat, statut, erreur) {
			alert("error");
		},

		complete: function (resultat, statut) {
			// alert("complete");
		}
	});
}

function targetpop(form) {
	newwin = window.open('', 'formpopup', 'width=400,height=400,resizeable,scrollbars');
	form.target = 'formpopup';
	newwin.focus();
}

function calculp2(ligne) {
	var vdeb = document.getElementById("vpcdeb" + ligne).value;
	var vfin = document.getElementById("vpcfin" + ligne).value;

	if (vdeb != "" && vfin != "") {
		if (isNaN(vdeb)) {
			alert("La valeur de début n\'est pas un nombre");
			return;
		}
		if (isNaN(vfin)) {
			alert("La valeur de fin n\'est pas un nombre");
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
			alert("La valeur de fin est incorrecte");
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
			document.getElementById("dur" + ligne).value = result;
		} else {
			alert("La durée est nulle ou négative");
		}
	}
}


function instonoff(ligne) {
	if (document.getElementById("dc" + ligne).checked)
		document.getElementById("inst" + ligne).style.display = "block";
	else
		document.getElementById("inst" + ligne).style.display = "none";
}

function masqueligne(ligne) {
	document.getElementById("tr" + ligne).style.color = "#AAAAAA";
	var forml = document.getElementById("saisie" + ligne)
	for (i = 0; i <= forml.length - 1; i++) {
		forml[i].disabled = true;
	}
	document.getElementById("but" + ligne).style.display = "none";

}


// initialisation du fuseau horaire 
function inittz() {
	var tz;
	try { tz = new Date().getTimezoneOffset(); }
	catch (e) { tz = 0; }
	tz = (tz / -60);			// the javascript gettimezone function return negative value in minutes then /-60 to have correct hours
	var tzlist = { "-1": 5, "0": 4, "1": 3, "2": 2, "3": 1, "4": 0 };
	document.getElementById("tz").selectedIndex = tzlist["" + tz];

}

if (document.getElementById('tz')) inittz();

// Le code JQuery n'est actif et testable qu'avec un accès internet
$(document).ready(function () {

	// Cache le champ instruction si ce n'est pas un vol DC
	$("#vpdc").change(show_instruction);
	show_instruction();

	$("#vpve").change(show_payeur);
	$("#vpvi").change(show_payeur);
	show_payeur();
	var lance = $('input[name="vpautonome"][checked="checked"]').val();
	// alert ("lance=" + lance);
	if (lance == 3) {
		$('.treuil').hide();
		$('.altitude').show();
	} else if (lance == 1) {
		$('.treuil').show();
		$('.altitude').hide();
	} else {
		$('.altitude').hide();
		$('.treuil').hide();
	}


	$('input[name="vpautonome"]').change(function () {
		if ($(this).val() == 3) {
			$('.treuil').hide();
			$('.altitude').show();
		} else if ($(this).val() == 1) {
			$('.treuil').show();
			$('.altitude').hide();
		} else {
			$('.altitude').hide();
			$('.treuil').hide();
		}
	});

	$("#vpmacid").change(update_machine);
	update_machine();
});

