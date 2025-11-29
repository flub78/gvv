/**
 * Bank Statement Search Functionality
 *
 * Filters bank operations tables based on user search input.
 * Searches across all text content including:
 * - Dates
 * - Operation nature/type
 * - Amounts (debit/credit)
 * - Comments
 * - Associated accounting entries descriptions
 * - Reference numbers
 */

/**
 * Remove all highlights from text
 */
function removeHighlights(element) {
    const highlights = element.querySelectorAll('.highlight');
    highlights.forEach(highlight => {
        const parent = highlight.parentNode;
        parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
        parent.normalize(); // Merge adjacent text nodes
    });
}

/**
 * Highlight search term in text
 */
function highlightText(element, searchTerm) {
    // Process all text nodes
    const walker = document.createTreeWalker(
        element,
        NodeFilter.SHOW_TEXT,
        null,
        false
    );

    const nodesToReplace = [];
    let node;

    while (node = walker.nextNode()) {
        const parent = node.parentElement;

        // Skip if parent is a script, style, input, button, link, or already highlighted
        if (!parent ||
            parent.tagName === 'SCRIPT' ||
            parent.tagName === 'STYLE' ||
            parent.tagName === 'INPUT' ||
            parent.tagName === 'BUTTON' ||
            parent.tagName === 'A' ||
            parent.classList.contains('highlight')) {
            continue;
        }

        const text = node.nodeValue;
        const lowerText = text.toLowerCase();
        const lowerSearchTerm = searchTerm.toLowerCase();

        if (lowerText.includes(lowerSearchTerm)) {
            nodesToReplace.push(node);
        }
    }

    // Replace nodes with highlighted version
    nodesToReplace.forEach(node => {
        const text = node.nodeValue;
        const regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        const parts = text.split(regex);

        const fragment = document.createDocumentFragment();
        parts.forEach(part => {
            if (part.toLowerCase() === searchTerm.toLowerCase()) {
                const span = document.createElement('span');
                span.className = 'highlight';
                span.textContent = part;
                fragment.appendChild(span);
            } else if (part) {
                fragment.appendChild(document.createTextNode(part));
            }
        });

        node.parentNode.replaceChild(fragment, node);
    });
}

/**
 * Filter bank operations based on search input
 * Shows/hides operation tables based on whether they contain the search term
 */
function filterBankOperations() {
    const searchInput = document.getElementById('searchReleveBanque');
    if (!searchInput) {
        console.error('Search input not found');
        return;
    }

    const searchTerm = searchInput.value.trim();

    // Get all operation tables
    const operationTables = document.querySelectorAll('table.operations');

    if (!operationTables || operationTables.length === 0) {
        console.warn('No operation tables found');
        return;
    }

    let visibleCount = 0;
    let hiddenCount = 0;

    // Filter each operation table
    operationTables.forEach(table => {
        // First, remove any existing highlights
        removeHighlights(table);

        if (searchTerm === '') {
            // Show all if search is empty
            table.style.display = '';
            visibleCount++;
        } else {
            // Get all text content from the table
            const tableText = table.textContent || table.innerText;
            const normalizedText = tableText.toLowerCase();

            // Check if search term is found in the table content
            if (normalizedText.includes(searchTerm.toLowerCase())) {
                table.style.display = '';
                visibleCount++;

                // Highlight the search term in the table
                highlightText(table, searchTerm);
            } else {
                table.style.display = 'none';
                hiddenCount++;
            }
        }
    });

    // Update search statistics (optional - can be displayed in UI)
    console.log(`Search: "${searchTerm}" - Visible: ${visibleCount}, Hidden: ${hiddenCount}`);

    // Update any UI indicators if needed
    updateSearchStats(visibleCount, hiddenCount, searchTerm);
}

/**
 * Clear the search input and show all operations
 */
function clearBankSearch() {
    const searchInput = document.getElementById('searchReleveBanque');
    if (searchInput) {
        searchInput.value = '';
        filterBankOperations(); // Trigger filter to show all
        searchInput.focus(); // Return focus to search box
    }
}

/**
 * Update search statistics display (optional)
 * Can be used to show user how many operations match their search
 */
function updateSearchStats(visible, hidden, searchTerm) {
    // Check if we have a stats display element
    const statsElement = document.getElementById('searchStats');
    if (statsElement) {
        if (searchTerm) {
            statsElement.textContent = `${visible} opération(s) trouvée(s)`;
            statsElement.style.display = 'block';
        } else {
            statsElement.style.display = 'none';
        }
    }
}

/**
 * Initialize search functionality when page loads
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchReleveBanque');

    if (searchInput) {
        // Add event listener for Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterBankOperations();
            }
        });

        // Auto-focus on search box when user presses Ctrl+F or Cmd+F
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                // Only if we're on the bank statement tab
                const bankTab = document.getElementById('openflyers');
                if (bankTab && bankTab.classList.contains('show')) {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });
    }
});
