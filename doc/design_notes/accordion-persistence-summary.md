# Implementing Persistent State for Bootstrap Accordions

This guide covers how to implement state persistence for Bootstrap accordions in a PHP/CodeIgniter 2.x environment, ensuring accordion items maintain their open/closed states across page reloads.

## Implementation Overview

The solution uses the browser's localStorage to save and restore accordion states without requiring database changes or server-side storage.

## HTML/PHP Structure

Your accordion structure should follow this pattern in your PHP view:

```php
<div class="accordion" id="mainAccordion">
    <?php foreach ($items as $key => $item): ?>
        <div class="accordion-item" id="accordion_<?php echo $key; ?>">
            <h2 class="accordion-header">
                <button class="accordion-button <?php echo ($key !== 0) ? 'collapsed' : ''; ?>" 
                        type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#collapse_<?php echo $key; ?>">
                    <?php echo $item['title']; ?>
                </button>
            </h2>
            <div id="collapse_<?php echo $key; ?>" 
                 class="accordion-collapse collapse <?php echo ($key === 0) ? 'show' : ''; ?>" 
                 data-bs-parent="#mainAccordion">
                <div class="accordion-body">
                    <?php echo $item['content']; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

## JavaScript Implementation (Vanilla JS)

```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Load saved states when page loads
    document.querySelectorAll('.accordion-item').forEach(item => {
        const accordionId = item.id;
        const savedState = localStorage.getItem('accordion_' + accordionId);
        
        if (savedState === 'open') {
            const collapseElement = item.querySelector('.accordion-collapse');
            const buttonElement = item.querySelector('.accordion-button');
            
            if (collapseElement && buttonElement) {
                collapseElement.classList.add('show');
                buttonElement.classList.remove('collapsed');
            }
        }
    });

    // Save state when accordion items are clicked
    document.querySelectorAll('.accordion-button').forEach(button => {
        button.addEventListener('click', function() {
            const accordionItem = this.closest('.accordion-item');
            const accordionId = accordionItem.id;
            
            // Check if accordion is being opened or closed
            const isCollapsed = this.classList.contains('collapsed');
            
            // Store the opposite state since Bootstrap hasn't applied the class yet
            localStorage.setItem('accordion_' + accordionId, isCollapsed ? 'open' : 'closed');
        });
    });
});
```

## Utility Functions

To clear stored accordion states if needed:

```javascript
function clearAccordionStates() {
    Object.keys(localStorage).forEach(key => {
        if (key.startsWith('accordion_')) {
            localStorage.removeItem(key);
        }
    });
}
```

## Key Features

- Uses vanilla JavaScript (no jQuery required)
- Works with Bootstrap's accordion component
- Persists states using localStorage
- Compatible with CodeIgniter 2.x and PHP 7.4
- No database modifications needed
- Supports multiple accordions on the same page

## Implementation Notes

1. Ensure each accordion item has a unique ID
2. The solution works with Bootstrap's existing collapse functionality
3. States persist until browser data is cleared or clearAccordionStates() is called
4. No server-side implementation required

## Browser Support

This implementation uses modern JavaScript features and APIs that are supported in:
- Chrome 40+
- Firefox 38+
- Safari 10+
- Edge 12+
- Opera 27+

## Performance Considerations

- localStorage operations are synchronous but very fast
- The code uses efficient DOM querying methods
- Event listeners are properly scoped to specific elements
- Memory usage is minimal as only string values are stored
