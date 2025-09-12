/**
 * JavaScript for manual reconciliation page
 * Handles selection of entries and reconciliation process
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Rapprochement manuel JS: DOM loaded');
    const rapprocherBtn = document.getElementById('rapprocher-btn');
    console.log('Rapprocher button found:', rapprocherBtn);

    // Désactiver le bouton au démarrage
    if (rapprocherBtn) {
        rapprocherBtn.setAttribute('disabled', 'disabled');
    }

    // Fonction pour vérifier les sélections et activer/désactiver le bouton
    function updateRapprocherButton() {
        const checkedBoxes = document.querySelectorAll('input[type="checkbox"][name^="cb_"]:checked');
        const allCheckboxes = document.querySelectorAll('input[type="checkbox"][name^="cb_"]');
        console.log('Checkboxes found:', allCheckboxes.length, 'checked:', checkedBoxes.length);
        
        // Debug: log tous les noms des checkboxes trouvées
        allCheckboxes.forEach(function(checkbox) {
            console.log('Checkbox found:', checkbox.name, 'checked:', checkbox.checked);
        });
        
        if (rapprocherBtn) {
            let shouldEnable = false;
            
            if (checkedBoxes.length > 0) {
                // Calculate sum of selected amounts
                const operationAmount = window.OPERATION_AMOUNT || 0;
                let selectedSum = 0;
                
                checkedBoxes.forEach(function(checkbox) {
                    const row = checkbox.closest('tr');
                    if (row) {
                        const cells = row.querySelectorAll('td');
                        if (cells.length >= 3) {
                            const amountText = cells[2].textContent.trim();
                            const amount = parseFloat(amountText.replace(/[^\d.,-]/g, '').replace(',', '.'));
                            if (!isNaN(amount)) {
                                selectedSum += amount;
                            }
                        }
                    }
                });
                
                // Check if amounts match (with small tolerance)
                const tolerance = 0.01;
                const difference = Math.abs(selectedSum - Math.abs(operationAmount));
                shouldEnable = difference <= tolerance;
                
                console.log('Operation amount:', operationAmount);
                console.log('Selected sum:', selectedSum);
                console.log('Difference:', difference);
                console.log('Should enable button:', shouldEnable);
                
                // Update visual indicator
                updateAmountIndicator(Math.abs(operationAmount), selectedSum, difference);
            } else {
                // Hide indicator when nothing is selected
                hideAmountIndicator();
            }
            
            if (shouldEnable) {
                rapprocherBtn.removeAttribute('disabled');
                rapprocherBtn.classList.remove('btn-secondary');
                rapprocherBtn.classList.add('btn-primary');
            } else {
                rapprocherBtn.setAttribute('disabled', 'disabled');
                rapprocherBtn.classList.remove('btn-primary');
                rapprocherBtn.classList.add('btn-secondary');
            }
            console.log('Button disabled:', rapprocherBtn.hasAttribute('disabled'));
        }
    }

    // Function to update the amount indicator
    function updateAmountIndicator(operationAmount, selectedAmount, difference) {
        const indicator = document.getElementById('amount-indicator');
        const selectedAmountSpan = document.getElementById('selected-amount');
        const differenceAmountSpan = document.getElementById('difference-amount');
        
        if (indicator && selectedAmountSpan && differenceAmountSpan) {
            selectedAmountSpan.textContent = selectedAmount.toFixed(2).replace('.', ',') + ' €';
            differenceAmountSpan.textContent = difference.toFixed(2).replace('.', ',') + ' €';
            
            // Change indicator color based on difference
            indicator.classList.remove('alert-info', 'alert-success', 'alert-warning');
            if (difference <= 0.01) {
                indicator.classList.add('alert-success');
            } else if (difference <= 1.00) {
                indicator.classList.add('alert-warning');
            } else {
                indicator.classList.add('alert-info');
            }
            
            indicator.classList.remove('d-none');
        }
    }

    // Function to hide the amount indicator
    function hideAmountIndicator() {
        const indicator = document.getElementById('amount-indicator');
        if (indicator) {
            indicator.classList.add('d-none');
        }
    }

    // Gestion du clic sur les checkboxes
    document.addEventListener('change', function(e) {
        if (e.target.type === 'checkbox' && e.target.name.startsWith('cb_')) {
            updateRapprocherButton();
        }
    });

    // Filtrage des écritures
    document.querySelectorAll('input[name="ecriture-filter"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const filterValue = this.value;
            const operationAmount = window.OPERATION_AMOUNT || 0;
            
            document.querySelectorAll('tr').forEach(function(row) {
                // Chercher une checkbox dans cette ligne pour déterminer si c'est une ligne d'écriture
                const checkbox = row.querySelector('input[type="checkbox"][name^="cb_"]');
                if (!checkbox) return; // Skip si pas de checkbox
                
                let show = true;
                
                if (filterValue === 'non-rapprochees') {
                    const badge = row.querySelector('.bg-success');
                    show = !badge; // Montrer uniquement si pas de badge vert
                } else if (filterValue === 'montant') {
                    // Chercher le montant dans la ligne
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 3) { // Vérifier qu'il y a au moins 3 colonnes
                        const montantText = cells[2].textContent.trim(); // 3ème colonne pour le montant
                        const montant = parseFloat(montantText.replace(/[^\d.,-]/g, '').replace(',', '.'));
                        if (!isNaN(montant)) {
                            const tolerance = Math.max(0.01, Math.abs(operationAmount) * 0.01); // 1% de tolerance minimum 0.01
                            show = Math.abs(montant - Math.abs(operationAmount)) <= tolerance;
                        }
                    }
                }
                
                row.style.display = show ? '' : 'none';
            });
            
            // Mettre à jour le bouton après filtrage
            updateRapprocherButton();
        });
    });

    // État initial du bouton
    updateRapprocherButton();

    // Override form submission to bypass validation popup
    // This prevents the "Veuillez sélectionner une écriture" alert from showing
    // in manual mode since we have our own checkbox-based validation
    const form = document.querySelector('form[action*="rapprochez"]');
    if (form && rapprocherBtn) {
        // Add debugging
        console.log('Form found:', form);
        console.log('Form action:', form.action);
        console.log('Form method:', form.method);
        
        // Prevent any external validation by intercepting the submit
        form.addEventListener('submit', function(e) {
            console.log('Manual reconciliation form submitted');
            
            // Check if at least one checkbox is selected
            const checkedBoxes = document.querySelectorAll('input[type="checkbox"][name^="cb_"]:checked');
            console.log('Checked boxes:', checkedBoxes.length);
            
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins une écriture à rapprocher');
                return false;
            }
            
            // Check that the sum of selected entries matches the statement operation amount
            const operationAmount = window.OPERATION_AMOUNT || 0;
            let selectedSum = 0;
            
            checkedBoxes.forEach(function(checkbox) {
                // Find the row containing this checkbox
                const row = checkbox.closest('tr');
                if (row) {
                    // The amount should be in the 3rd column (index 2)
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 3) {
                        const amountText = cells[2].textContent.trim();
                        // Parse amount: remove currency symbols, spaces, and convert comma to dot
                        const amount = parseFloat(amountText.replace(/[^\d.,-]/g, '').replace(',', '.'));
                        if (!isNaN(amount)) {
                            selectedSum += amount;
                        }
                    }
                }
            });
            
            console.log('Operation amount:', operationAmount);
            console.log('Selected sum:', selectedSum);
            
            // Check if amounts match (with small tolerance for floating point precision)
            const tolerance = 0.01;
            const difference = Math.abs(selectedSum - Math.abs(operationAmount));
            
            if (difference > tolerance) {
                e.preventDefault();
                const message = `Le montant total des écritures sélectionnées (${selectedSum.toFixed(2)} €) ne correspond pas au montant de l'opération bancaire (${Math.abs(operationAmount).toFixed(2)} €).\n\nÉcart: ${difference.toFixed(2)} €`;
                alert(message);
                return false;
            }
            
            // Allow form to submit normally - our validation passed
            console.log('Form validation passed, submitting...');
            return true;
        }, true); // Use capture phase to run before any other validation
        
        // Also override any click handlers on the submit button
        rapprocherBtn.addEventListener('click', function(e) {
            console.log('Rapprocher button clicked');
            
            // Check if button is disabled
            if (rapprocherBtn.hasAttribute('disabled')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Button is disabled, preventing submission');
                return false;
            }
            
            console.log('Button click allowed');
        }, true); // Use capture phase
    }
});
