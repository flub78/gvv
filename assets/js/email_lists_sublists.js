// Email Lists - Sublists Management
(function() {
    'use strict';

    // Get base URL from configuration set by PHP
    const baseUrl = window.GVV_CONFIG && window.GVV_CONFIG.baseUrl 
        ? window.GVV_CONFIG.baseUrl 
        : window.location.origin + '/email_lists';

    // Add sublist via AJAX
    function addSublist(parentListId, childListId) {
        fetch(`${baseUrl}/add_sublist_ajax`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `parent_list_id=${parentListId}&child_list_id=${childListId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Reload to refresh lists
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de l\'ajout de la sous-liste');
        });
    }

    // Remove sublist via AJAX
    function removeSublist(parentListId, childListId) {
        if (!confirm('Retirer cette sous-liste ?')) return;

        fetch(`${baseUrl}/remove_sublist_ajax`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `parent_list_id=${parentListId}&child_list_id=${childListId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Reload to refresh lists
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors du retrait de la sous-liste');
        });
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        const listId = document.getElementById('current_list_id')?.value;
        if (!listId) return;

        // Add sublist buttons
        document.querySelectorAll('.add-sublist-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const childId = this.dataset.listId;
                addSublist(listId, childId);
            });
        });

        // Remove sublist buttons
        document.querySelectorAll('.remove-sublist-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const childId = this.dataset.sublistId;
                removeSublist(listId, childId);
            });
        });
    });
})();
