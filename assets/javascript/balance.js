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
 * Fonctions Javascript de calcul sur la balance
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
		if (!/^\d{2}\/\d{2}\/\d{4}$/.test(balance_date)) {
			alert('Format de date invalide. Utilisez JJ/MM/AAAA.');
			return;
		}
		saveAccordionState();
		var url = controllers[0].value + "/new_balance_date/" + balance_date;
		window.location.href = url;
	}
}

function saveAccordionState() {
	var collapses = document.querySelectorAll('#balanceAccordion .accordion-collapse');
	if (collapses.length === 0) return;
	var states = {};
	collapses.forEach(function(el) {
		states[el.id] = el.classList.contains('show');
	});
	sessionStorage.setItem('balance_accordion_state', JSON.stringify(states));
}

function restoreAccordionState() {
	var saved = sessionStorage.getItem('balance_accordion_state');
	if (!saved) return;
	sessionStorage.removeItem('balance_accordion_state');
	var states = JSON.parse(saved);
	var collapses = document.querySelectorAll('#balanceAccordion .accordion-collapse');
	collapses.forEach(function(el) {
		if (!(el.id in states)) return;
		var button = document.querySelector('[data-bs-target="#' + el.id + '"]');
		if (states[el.id]) {
			el.classList.add('show');
			if (button) { button.classList.remove('collapsed'); button.setAttribute('aria-expanded', 'true'); }
		} else {
			el.classList.remove('show');
			if (button) { button.classList.add('collapsed'); button.setAttribute('aria-expanded', 'false'); }
		}
	});
}

restoreAccordionState();

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


