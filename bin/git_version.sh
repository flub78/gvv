#!/bin/bash
COMMIT=$(git rev-parse --short HEAD)
DATE=$(git log -1 --format=%cd --date=format:"%d/%m/%Y %H:%M:%S")
COMMIT_MESSAGE=$(git log -1 --format=%s)

echo "<?php
defined('BASEPATH') OR exit('No direct script access allowed');
\$config['commit'] = '$COMMIT';
\$config['commit_date'] = '$DATE';
\$config['commit_message'] = \"$COMMIT_MESSAGE\";
" > application/config/version.php