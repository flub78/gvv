#!/bin/bash
# Run migration 052

source setenv.sh

php index.php migrate migrate

echo "Migration 052 completed. Checking section_id column..."
mysql -u gvv_user -plfoyfgbj gvv2 -e "SHOW CREATE TABLE user_roles_per_section\G"
