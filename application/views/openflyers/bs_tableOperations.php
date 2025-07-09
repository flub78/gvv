<?php

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
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Vue table pour les terrains
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('openflyers');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_of_title_operations", 3);

?>

    <h2><?=$titre?></h2>
	<p><?=$date_edition?></p>
    <div class="actions mb-3">
        <button class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
        <button class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
    </div>
<?php

echo form_open_multipart('openflyers/creates_soldes');

// Utilisé pour les comptes clients
// echo table_from_array ($comptes_html, array(
//     'fields' => array('Compte OF', 'Nom',  'Compte GVV', '', '', '', ''),
//     'align' => array('right', 'left',  'right', '', '', '', ''),
//     'class' => 'datatable3 table'
// ));

echo $comptes_html;

echo form_input(array(
	'type' => 'submit',
	'name' => 'button',
	'value' => $this->lang->line("gvv_of_init_soldes"),
	'class' => 'btn btn-primary mb-4'
));
echo form_close('</div>');

echo '</div>';

?>
    <script>


        // Callback function called when select changes
        function updateRow(selectElement, id_of, nom_of) {
            const cptGVV = selectElement.value;

			console.log("updateRow, cpt GVV=" + cptGVV + ", id_of=" + id_of + ", nom=" + nom_of);
			
			// Call server to associate account
			fetch('<?= base_url() ?>associations_of/associate?id_of=' + id_of + '&nom_of=' + encodeURIComponent(nom_of) + '&cptGVV=' + encodeURIComponent(cptGVV))
			    .then(response => response.json())
			    .then(data => console.log('Association response:', data))
			    .catch(error => console.error('Error:', error));
        }

        // Toggle row selection
        function toggleRowSelection(checkbox) {
            const row = checkbox.closest('tr');
            if (checkbox.checked) {
                row.classList.add('selected-row');
            } else {
                row.classList.remove('selected-row');
            }
        }

        // Select all rows
        function selectAll() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
                toggleRowSelection(checkbox);
            });
            addLogEntry('All rows selected');
        }

        // Deselect all rows
        function deselectAll() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
                toggleRowSelection(checkbox);
            });
            addLogEntry('All rows deselected');
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
            addLogEntry(`${selectedRows.length} rows selected`);
            
            if (selectedRows.length > 0) {
                alert(`Selected ${selectedRows.length} row(s). Check console for details.`);
            } else {
                alert('No rows selected');
            }
        }

        // Add log entry
        function addLogEntry(message) {
            const logContainer = document.getElementById('logContainer');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry';
            logEntry.textContent = `${timestamp}: ${message}`;
            logContainer.appendChild(logEntry);
            
            // Keep only last 10 log entries
            if (logContainer.children.length > 10) {
                logContainer.removeChild(logContainer.firstChild);
            }
        }
    </script>
<?php