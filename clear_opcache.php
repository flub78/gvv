<?php
// Clear opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "Opcache cleared successfully\n";
} else {
    echo "Opcache not enabled\n";
}
