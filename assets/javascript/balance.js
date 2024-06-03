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
 * Changement de date de balance
 */
function new_balance_date() {
	
	var balance_date = $("#balance_date").val();
	var controllers = document.getElementsByName('controller_url');
	if (controllers.length < 1) {
		alert('controller_url not found');
	} else {
		var url = controllers[0].value + "/new_balance_date/" + balance_date;
		window.location.href = url;
	}
}

/**
 * Bascule generale/détaillée
 */
function balance_general() {
	
	var selectedVal = "";
	var selected = $("input[type='radio'][name='general']:checked");
	if (selected.length > 0) {
	    selectedVal = selected.val();
	}

	var controllers = document.getElementsByName('controller_url');
	if (controllers.length < 1) {
		alert('controller_url not found');
	} else {
		var url = controllers[0].value;
		if (selectedVal == 1) {
			url += '/general';
		} else {
			url += '/detail';
		}
		window.location.href = url;
	}
}


