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

// Dépendances sur les fichiers de langue (*_lang.js) :
//   horametre  - libellé "Horamètre" localisé
//   hm         - libellé "heures.minutes" localisé
//   h_100      - libellé "heures.centième" localisé
var currentHoraMode = 0;
var currentMachineXhr = null;

/**
 * Construit le widget de saisie d'horamètre
 * @param {string} containerId - ID du div conteneur
 * @param {string} hiddenId    - ID de l'input hidden (ex: "debut", "fin")
 * @param {number} mode        - 0=centième, 1=minutes, 2=dixième
 */
function buildHoraWidget(containerId, hiddenId, mode) {
    var container = document.getElementById(containerId);
    if (!container) return;
    var hiddenInput = document.getElementById(hiddenId);
    if (!hiddenInput) return;

    var fullValue = hiddenInput.value || '0';
    var dotPos = fullValue.indexOf('.');
    var intPart = dotPos >= 0 ? parseInt(fullValue.substring(0, dotPos)) : parseInt(fullValue);
    var decStr  = dotPos >= 0 ? fullValue.substring(dotPos + 1) : '0';
    if (isNaN(intPart)) intPart = 0;

    var maxDec, decWidth;
    if (mode == 1) {       // minutes
        maxDec = 59; decWidth = 2;
    } else if (mode == 2) { // dixième
        maxDec = 9;  decWidth = 1;
    } else {               // centième (défaut)
        maxDec = 99; decWidth = 2;
    }

    // Normalise decStr en justification gauche sur decWidth chiffres :
    // "50" avec decWidth=1 → "5" (5 dixièmes)
    // "5"  avec decWidth=2 → "50" (50 centièmes = 0.5h)
    // "05" avec decWidth=2 → "05" (5 centièmes)
    var decPart = parseInt((decStr + '00').substring(0, decWidth));
    if (isNaN(decPart)) decPart = 0;
    if (decPart > maxDec) {
        console.warn('buildHoraWidget: decPart=' + decPart + ' > maxDec=' + maxDec + ' pour ' + hiddenId + '="' + fullValue + '" (mode=' + mode + ') → réinitialisé à 0');
        decPart = 0;
    }

    var intInputId = hiddenId + '_int';
    var decInputId = hiddenId + '_dec';

    var decHtml = '<select id="' + decInputId + '" class="form-select" style="width:' + (maxDec >= 10 ? '75' : '65') + 'px">';
    for (var i = 0; i <= maxDec; i++) {
        var optLabel = String(i).padStart(decWidth, '0');
        decHtml += '<option value="' + i + '"' + (i === decPart ? ' selected' : '') + '>' + optLabel + '</option>';
    }
    decHtml += '</select>';

    container.innerHTML =
        '<div class="d-flex align-items-center gap-1">' +
        '<button type="button" class="btn btn-outline-secondary" id="' + hiddenId + '_minus">−</button>' +
        '<input type="number" id="' + intInputId + '" class="form-control" style="width:80px" min="0" value="' + intPart + '">' +
        '<button type="button" class="btn btn-outline-secondary" id="' + hiddenId + '_plus">+</button>' +
        decHtml +
        '</div>';

    function updateHidden() {
        var intVal = parseInt(document.getElementById(intInputId).value);
        var decVal = parseInt(document.getElementById(decInputId).value);
        if (isNaN(intVal) || intVal < 0) intVal = 0;
        if (isNaN(decVal) || decVal < 0) decVal = 0;
        if (decVal > maxDec) decVal = maxDec;
        hiddenInput.value = intVal + '.' + String(decVal).padStart(decWidth, '0');
        $(hiddenInput).trigger('change');
    }

    document.getElementById(intInputId).addEventListener('change', updateHidden);
    document.getElementById(intInputId).addEventListener('input',  updateHidden);
    document.getElementById(decInputId).addEventListener('change', updateHidden);
    document.getElementById(decInputId).addEventListener('input',  updateHidden);

    document.getElementById(hiddenId + '_minus').addEventListener('click', function() {
        var el = document.getElementById(intInputId);
        var v = parseInt(el.value) || 0;
        if (v > 0) { el.value = v - 1; updateHidden(); }
    });
    document.getElementById(hiddenId + '_plus').addEventListener('click', function() {
        var el = document.getElementById(intInputId);
        el.value = (parseInt(el.value) || 0) + 1;
        updateHidden();
    });

    updateHidden();
}

function buildHoraWidgets(mode) {
    currentHoraMode = mode;
    buildHoraWidget('debut_widget', 'debut', mode);
    buildHoraWidget('fin_widget',   'fin',   mode);
    var duree = parseFloat($('[name="vaduree"]').val());
    if (!isNaN(duree) && duree > 0) {
        $("#duree_display").text(formatDuree(duree, mode));
    }
}

/**
 * Formate une durée en heures décimales selon le mode horamètre
 * @param {number} decimal_hours - durée en heures décimales
 * @param {number} mode          - 0=centième, 1=minutes, 2=dixième
 */
function formatDuree(decimal_hours, mode) {
    if (isNaN(decimal_hours) || decimal_hours <= 0) return '';
    var h = Math.floor(decimal_hours);
    var min = Math.round((decimal_hours - h) * 60);
    if (min >= 60) { h++; min -= 60; }
    return h + 'h' + String(min).padStart(2, '0');
}

/**
 * Convertit une valeur horamètre en heures décimales selon le mode
 */
function horaToDecimal(value, mode) {
    var v = parseFloat(value);
    if (isNaN(v)) return 0;
    if (mode == 1) { // minutes : partie décimale = minutes (00-59)
        var h   = Math.floor(v);
        var min = Math.round((v - h) * 100);
        return h + min / 60;
    }
    return v; // centième et dixième : valeur directement en heures décimales
}

/**
 * Met à jour le champ durée à partir des horamètres début et fin
 */
function updateDuree() {
    var debut = parseFloat($("#debut").val());
    var fin   = parseFloat($("#fin").val());
    if (isNaN(debut) || isNaN(fin)) return;
    var debutH = horaToDecimal(debut, currentHoraMode);
    var finH   = horaToDecimal(fin,   currentHoraMode);
    var duree  = Math.round((finH - debutH) * 1000) / 1000;
    if (duree > 0) {
        $('[name="vaduree"]').val(duree);
        $("#duree_display").text(formatDuree(duree, currentHoraMode));
        $("#time_error").text('');
    } else if (fin > 0 && debut > 0) {
        $('[name="vaduree"]').val('');
        $("#duree_display").text('');
        $("#time_error").text('Durée nulle ou négative');
    }
}

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

	  if (currentMachineXhr) {
	      currentMachineXhr.abort();
	      currentMachineXhr = null;
	  }
	  currentMachineXhr = $.ajax({
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

	       },

	       error : function(resultat, statut, erreur){
	           alert("error");
	       },

	       complete : function(resultat, statut){
	           currentMachineXhr = null;
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
	buildHoraWidgets(mode);

	if (typeof is_new_vol !== 'undefined' && is_new_vol) {
		var lastHora = (typeof horametres_last_data !== 'undefined' &&
		                horametres_last_data.hasOwnProperty(selected_machine))
		               ? horametres_last_data[selected_machine] : 0;
		$("#debut").val(lastHora);
		$("#fin").val(lastHora);
		buildHoraWidget('debut_widget', 'debut', mode);
		buildHoraWidget('fin_widget',   'fin',   mode);
	}
}

//Le code JQuery n'est actif et testable qu'avec un accès internet
$(document).ready(function(){

	// Cache le champ instruction si ce n'est pas un vol DC
	$("#vadc").change(show_instruction);
	show_instruction();

	$("#vave").change(show_payeur);
	$("#vavi").change(show_payeur);
	show_payeur();
	
	buildHoraWidgets(typeof initial_horametre_mode !== 'undefined' ? initial_horametre_mode : 0);

	$("#debut, #fin").on('change', updateDuree);

	$("#vamacid").change(update_machine);
	update_machine();

});

