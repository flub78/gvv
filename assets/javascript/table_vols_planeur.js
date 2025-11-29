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
 * Fonctions Javascript initialisationdattable planche planeur
 * 
 */

$(document).ready(function () {

    $('.datatable_server').dataTable({
        "bServerSide": true,
        "sAjaxSource": "ajax_page",
        "bFilter": true,
        "bPaginate": true,
        "iDisplayLength": 25,
        "bStateSave": true,
        "bRetrieve": true,
        "bSort": true,
        "bInfo": true,
        "bJQueryUI": true,
        "bAutoWidth": true,
        "sPaginationType": "full_numbers",
        // "sDom": 'lfptip',
        "aoColumns": [
            { "bSortable": false },       // edit
            { "bSortable": false },        // delete
            { "sType": "date-uk" },      // date "asSorting": [ "desc", "asc" ]
            { "bSortable": true },       // debut
            { "bSortable": true },       // durée
            { "bSortable": true },        // immat
            { "bSortable": true },        // pilote
            { "bSortable": true },        // inst
            { "bSortable": false },       // L
            { "bSortable": true },       // Rem
            { "bSortable": false },       // Alt
            { "bSortable": false },       // obs
            { "bSortable": true },        // lieu
            { "bSortable": false },       // Kms
            { "bSortable": false },       // - 25
            { "bSortable": false },       // DC
            { "bSortable": false },       // Prv
            { "bSortable": false }       // cat
        ],
        "oLanguage": olanguage,
        "fnDrawCallback": highlightSearchCallback
    });

    $('.datatable_server_ro').dataTable({
        "bServerSide": true,
        "sAjaxSource": "ajax_page",
        "bFilter": false,
        "bPaginate": true,
        "iDisplayLength": 25,
        "bStateSave": true,
        "bSort": true,
        "bInfo": true,
        "bJQueryUI": true,
        "bAutoWidth": true,
        "sPaginationType": "full_numbers",
        "aoColumns": [
            { "asSorting": ["desc"] },      // date 
            { "bSortable": false },       // debut
            { "bSortable": false },       // durée
            { "bSortable": false },        // immat
            { "bSortable": false },        // pilote
            { "bSortable": false },        // inst
            { "bSortable": false },       // L
            { "bSortable": false },       // Rem
            { "bSortable": false },       // Alt
            { "bSortable": false },       // obs
            { "bSortable": false },        // lieu     
            { "bSortable": false },       // Kms
            { "bSortable": false },       // - 25
            { "bSortable": false },       // DC
            { "bSortable": false },       // Prv
            { "bSortable": false } //,       // cat
        ],
        "oLanguage": olanguage
    });

});
