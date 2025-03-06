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
 * Fonctions Javascript initialisation de la table des vols
 * 
 */

$(document).ready(function () {

    $('.datedtable').dataTable({
        "bFilter": true,
        "bPaginate": true,
        "iDisplayLength": 25,
        "bSort": true,
        "bStateSave": true,
        "bInfo": true,
        "bJQueryUI": true,
        "bAutoWidth": true,
        "sPaginationType": "full_numbers",
        "aoColumns": [
            { "sType": "date-uk" },      // date "asSorting": [ "desc", "asc" ]
            { "bSortable": true },        // pilote
            { "bSortable": true },        // inst
            { "bSortable": true },        // immat
            { "bSortable": true },        // Section
            { "bSortable": true },       // debut
            { "bSortable": false },       // fin
            { "bSortable": false },       // durée
            { "bSortable": false },       // att
            { "bSortable": false },       // obs
            { "bSortable": true },        // lieu     
            { "bSortable": false },       // - 25
            { "bSortable": false },       // DC
            { "bSortable": false },       // cat
            { "bSortable": false },       // Prv
            { "bSortable": false },       // Ess     
            { "bSortable": false },       // change
            { "bSortable": false }        // delete
        ],
        "oLanguage": olanguage
    });

    $('.datedtable_ro').dataTable({
        "bFilter": true,
        "bPaginate": true,
        "iDisplayLength": 25,
        "bSort": true,
        "bInfo": true,
        "bJQueryUI": true,
        "bStateSave": true,
        "bAutoWidth": true,
        "sPaginationType": "full_numbers",
        "aoColumns": [
            { "sType": "date-uk", "asSorting": ["desc", "asc"] },      // date
            { "bSortable": true },        // pilote
            { "bSortable": true },        // inst
            { "bSortable": true },        // immat
            { "bSortable": true },        // Section
            { "bSortable": false },       // debut
            { "bSortable": false },       // fin
            { "bSortable": false },       // durée
            { "bSortable": false },       // att
            { "bSortable": false },       // obs
            { "bSortable": true },        // lieu     
            { "bSortable": false },       // - 25
            { "bSortable": false },       // DC
            { "bSortable": false },       // cat
            { "bSortable": false },       // Prv
            { "bSortable": false },       // Ess     
        ],
        "oLanguage": olanguage
    });
});
