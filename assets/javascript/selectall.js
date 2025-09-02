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
 * Fonctions Javascript de selection multiple
 * 
 */

// Callback function called when account select changes
function associateAccount(selectElement, str) {

    console.log("APP_BASE_URL=" + window.APP_BASE_URL);

    const cptGVV = selectElement.value;

    console.log("associateAccount, cpt GVV=" + cptGVV + ", str=" + str);

    // Call server to associate account
    fetch(window.APP_BASE_URL + 'associations_releve/associate?string_releve=' + str + '&cptGVV=' + encodeURIComponent(cptGVV))
        .then(response => response.json())
        .then(data => console.log('Association response:', data))
        .catch(error => console.error('Error:', error));

    location.reload();
}

// Callback function called when select changes
// function updateRow(selectElement, id_of, nom_of) {
//     const cptGVV = selectElement.value;

//     console.log("updateRow, cpt GVV=" + cptGVV + ", id_of=" + id_of + ", nom=" + nom_of);

//     // Call server to associate account
//     fetch('/associations_of/associate?id_of=' + id_of + '&nom_of=' + encodeURIComponent(nom_of) + '&cptGVV=' + encodeURIComponent(cptGVV))
//         .then(response => response.json())
//         .then(data => console.log('Association response:', data))
//         .catch(error => console.error('Error:', error));
// }


// Select all rows
function selectAll() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    console.log('All rows selected');
}

// Deselect all rows
function deselectAll() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    console.log('All rows deselected');
}

// Get selected rows
function getSelectedRows() {
    const selectedRows = [];
    const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');

    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const rowIndex = Array.from(row.parentNode.children).indexOf(row);
        selectedRows.push({
            index: rowIndex,
            data: tableData[rowIndex]
        });
    });

    console.log('Selected rows:', selectedRows);
    console.log(`${selectedRows.length} rows selected`);

    if (selectedRows.length > 0) {
        alert(`Selected ${selectedRows.length} row(s). Check console for details.`);
    } else {
        alert('No rows selected');
    }
}

function selectUniques() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]:not(#smartMode)');
    checkboxes.forEach(checkbox => {
        if (checkbox.classList.contains('unique')) {
            checkbox.checked = true;
        } else {
            checkbox.checked = false;
        }
    });
    console.log('Unique rows selected');
}

function maxDaysChanged(input) {
    const maxDays = input.value;
    console.log('Max days changed to:', maxDays);
    // You can add logic here to filter the table based on maxDays
    fetch('./max_days_change?maxDays=' + maxDays)
        .then(response => response.json())
        .then(data => location.reload())
        .catch(error => console.error('Error:', error));
}

function smartModeChanged(checkbox) {
    const isChecked = checkbox.checked;
    console.log('Smart mode changed:', isChecked);
    // You can add logic here to toggle smart mode functionality
    if (isChecked) {
        // Enable smart mode
        console.log('Smart mode enabled');
    } else {
        // Disable smart mode
        console.log('Smart mode disabled');
    }

    fetch('./smart_mode_change?smartMode=' + isChecked)
        .then(response => response.json())
        .then(data => {

            console.log('Smart mode change response:', data);
            location.reload();
        })
        .catch(error => console.error('Error:', error));
}



