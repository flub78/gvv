#!/bin/bash
#
# Fix all test URLs to include /index.php/ for remote server compatibility
#

echo "Fixing URL paths in test files..."

# Find all test files and update page.goto() calls
find tests/ \( -name "*.js" -o -name "*.ts" \) -type f | while read file; do
    # Skip node_modules
    if [[ "$file" == *"node_modules"* ]]; then
        continue
    fi

    # Count how many fixes we'll make
    count=$(grep -c "page\.goto('/" "$file" 2>/dev/null || echo "0")

    if [ "$count" -gt 0 ]; then
        echo "Updating $file ($count occurrences)..."

        # Replace page.goto('/ with page.goto('/index.php/ for all paths except:
        # - Already has /index.php/
        # - Root path /
        # - Empty path

        # Temporarily replace to avoid double-adding
        sed -i "s|page\.goto('/\([^']\)|page.goto('/TEMP_INDEX_PHP/\1|g" "$file"

        # Fix root path back to just /
        sed -i "s|page\.goto('/TEMP_INDEX_PHP/')|page.goto('/')|g" "$file"

        # Fix paths that already had index.php
        sed -i "s|page\.goto('/TEMP_INDEX_PHP/index\.php/|page.goto('/index.php/|g" "$file"

        # Convert TEMP back to index.php
        sed -i "s|TEMP_INDEX_PHP|index.php|g" "$file"
    fi
done

echo ""
echo "Done! All test URLs now include /index.php/ prefix."
echo "This ensures compatibility with servers that don't have mod_rewrite configured."
