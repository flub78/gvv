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

echo heading("gvv_of_title_soldes", 3);

?>
	<p>Seuls les comptes au solde non null sont affichés.</p>
    <div class="actions mb-3">
        <button onclick="selectAll()">Sélectionnez tout</button>
        <button onclick="deselectAll()">Dé-sélectionnez tout</button>
        <button onclick="getSelectedRows()">Get Selected Rows</button>
    </div>
<?php

// Utilisé pour les comptes clients
echo table_from_array ($soldes, array(
    'fields' => array('', 'Compte OF', 'Nom', 'Profil',  'Compte GVV', 'Solde'),
    'align' => array('center', 'right', 'left', 'left', 'center', 'right'),
    'class' => 'datatable table'
));

echo form_open_multipart('openflyers/import_operations');
echo "Date d'import des soldes: " . '<input type="date" name="userfile" size="50" class="mt-4"/><br><br>';

echo form_input(array(
	'type' => 'submit',
	'name' => 'button',
	'value' => $this->lang->line("gvv_button_validate"),
	'class' => 'btn btn-primary mb-4'
));
echo form_close('</div>');

echo '</div>';

?>
    <script>
        // Store table data
        let tableData = [
            { id: '001', name: 'John Doe', category: 'electronics', status: 'Active', date: '2024-01-15' },
            { id: '002', name: 'Jane Smith', category: 'clothing', status: 'Pending', date: '2024-01-20' },
            { id: '003', name: 'Bob Johnson', category: 'books', status: 'Inactive', date: '2024-01-25' },
            { id: '004', name: 'Alice Brown', category: 'home', status: 'Active', date: '2024-02-01' },
            { id: '005', name: 'Charlie Wilson', category: 'electronics', status: 'Pending', date: '2024-02-05' }
        ];

        // Callback function called when select changes
        function updateRow(selectElement, rowIndex) {
            const newValue = selectElement.value;
            const oldValue = tableData[rowIndex].category;
            
            // Update the data
            tableData[rowIndex].category = newValue;
            
            // Log the change
            addLogEntry(`Row ${rowIndex + 1}: Category changed from "${oldValue}" to "${newValue}"`);
            
            // You can add more logic here, such as:
            // - Making an API call to update the backend
            // - Updating other parts of the UI
            // - Validating the change
            
            console.log(`Row ${rowIndex + 1} updated:`, tableData[rowIndex]);
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