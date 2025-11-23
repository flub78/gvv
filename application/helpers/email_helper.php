<?php
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * Email Helper
 *
 * Helper functions for email validation, normalization, deduplication,
 * and export generation for the email lists management system.
 *
 * @package helpers
 * @see doc/design_notes/gestion_emails_design.md
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Validate email address according to RFC 5322
 *
 * @param string $email Email address to validate
 * @return bool TRUE if valid, FALSE otherwise
 */
if (!function_exists('validate_email')) {
    function validate_email($email) {
        if (empty($email)) {
            return FALSE;
        }

        // Handle "Name <email@example.com>" format
        if (preg_match('/<(.+)>/', $email, $matches)) {
            $email = trim($matches[1]);
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== FALSE;
    }
}

/**
 * Normalize email address (lowercase + trim)
 *
 * @param string $email Email address to normalize
 * @return string Normalized email address
 */
if (!function_exists('normalize_email')) {
    function normalize_email($email) {
        if (empty($email)) {
            return '';
        }
        return strtolower(trim($email));
    }
}

/**
 * Deduplicate array of emails (case-insensitive)
 *
 * Accepts array of strings or array of associative arrays with 'email' key.
 * Returns deduplicated array in the same format as input.
 *
 * @param array $emails Array of email strings or arrays with 'email' key
 * @return array Deduplicated array
 */
if (!function_exists('deduplicate_emails')) {
    function deduplicate_emails($emails) {
        if (empty($emails)) {
            return array();
        }

        $seen = array();
        $result = array();
        $is_assoc = is_array($emails[0]);

        foreach ($emails as $item) {
            $email = $is_assoc ? (isset($item['email']) ? $item['email'] : '') : $item;

            if (empty($email)) {
                continue;
            }

            $normalized = normalize_email($email);

            if (!isset($seen[$normalized])) {
                $seen[$normalized] = TRUE;
                $result[] = $item;
            }
        }

        return $result;
    }
}

/**
 * Chunk emails into parts of specified size
 *
 * @param array $emails Array of emails
 * @param int $size Number of emails per chunk (default: 20)
 * @return array Array of chunks
 */
if (!function_exists('chunk_emails')) {
    function chunk_emails($emails, $size = 20) {
        if (empty($emails) || $size < 1) {
            return array();
        }
        return array_chunk($emails, $size);
    }
}

/**
 * Generate TXT export content
 *
 * @param array $emails Array of email strings or arrays with 'email' key
 * @param string $separator Separator between emails (default: ',')
 * @return string Email list as text
 */
if (!function_exists('generate_txt_export')) {
    function generate_txt_export($emails, $separator = ',') {
        if (empty($emails)) {
            return '';
        }

        $email_strings = array();
        $is_assoc = is_array($emails[0]);

        foreach ($emails as $item) {
            $email = $is_assoc ? (isset($item['email']) ? $item['email'] : '') : $item;
            if (!empty($email)) {
                $email_strings[] = $email;
            }
        }

        return implode($separator . ' ', $email_strings);
    }
}

/**
 * Generate Markdown export content
 *
 * @param array $list_data List metadata (name, description, created_at, etc.)
 * @param array $emails Array of email strings
 * @return string Markdown formatted content
 */
if (!function_exists('generate_markdown_export')) {
    function generate_markdown_export($list_data, $emails) {
        $md = "# " . (isset($list_data['name']) ? $list_data['name'] : 'Email List') . "\n\n";

        if (!empty($list_data['description'])) {
            $md .= $list_data['description'] . "\n\n";
        }

        $md .= "**Total:** " . count($emails) . " destinataire(s)\n\n";

        if (!empty($list_data['created_at'])) {
            $md .= "**Créé le:** " . $list_data['created_at'] . "\n\n";
        }

        if (!empty($list_data['updated_at'])) {
            $md .= "**Mis à jour le:** " . $list_data['updated_at'] . "\n\n";
        }

        $md .= "## Destinataires\n\n";

        if (empty($emails)) {
            $md .= "*Aucun destinataire*\n";
        } else {
            foreach ($emails as $email) {
                $md .= "- " . $email . "\n";
            }
        }

        return $md;
    }
}

/**
 * Generate mailto URL
 *
 * @param array $emails Array of email addresses
 * @param array $params Optional parameters (to/cc/bcc, subject, body, reply_to)
 * @return string|false mailto: URL or FALSE if too long
 */
if (!function_exists('generate_mailto')) {
    function generate_mailto($emails, $params = array()) {
        if (empty($emails)) {
            return 'mailto:';
        }

        $email_list = generate_txt_export($emails, ',');

        $field = isset($params['field']) ? $params['field'] : 'to';
        $subject = isset($params['subject']) ? $params['subject'] : '';
        $body = isset($params['body']) ? $params['body'] : '';
        $reply_to = isset($params['reply_to']) ? $params['reply_to'] : '';

        $url = 'mailto:';
        $query_params = array();

        if ($field === 'to') {
            $url .= $email_list;
        } elseif ($field === 'cc') {
            $query_params[] = 'cc=' . urlencode($email_list);
        } elseif ($field === 'bcc') {
            $query_params[] = 'bcc=' . urlencode($email_list);
        }

        if (!empty($subject)) {
            $query_params[] = 'subject=' . urlencode($subject);
        }

        if (!empty($body)) {
            $query_params[] = 'body=' . urlencode($body);
        }

        if (!empty($reply_to)) {
            $query_params[] = 'reply-to=' . urlencode($reply_to);
        }

        if (!empty($query_params)) {
            $url .= '?' . implode('&', $query_params);
        }

        // Check URL length (most browsers limit to ~2000 characters)
        if (strlen($url) > 2000) {
            return FALSE;
        }

        return $url;
    }
}

/**
 * Parse email string - Universal parser for all email input formats
 * Handles:
 * - Simple emails: test@example.com
 * - Named emails: John Doe <john@example.com>
 * - CSV format: email,name or email;name
 * - Multiple emails per line (comma or semicolon separated)
 * - Multiple lines
 *
 * @param string $content Text content with emails
 * @param array $options Options:
 *   - 'allow_csv' => bool - Try to detect and parse as CSV (default: true)
 *   - 'delimiter' => string - CSV delimiter if known (default: auto-detect)
 * @return array Array of parsed emails with metadata (email, name, valid, error, line)
 */
if (!function_exists('parse_email_string')) {
    function parse_email_string($content, $options = array()) {
        if (empty($content)) {
            return array();
        }

        $allow_csv = isset($options['allow_csv']) ? $options['allow_csv'] : true;
        $delimiter = isset($options['delimiter']) ? $options['delimiter'] : null;

        // Detect if content looks like CSV
        // CSV detection: Multiple lines with pattern "email,name" or "email;name"
        // NOT: Single line with comma-separated emails like "a@x.com, b@y.com, c@z.com"
        $is_csv = false;
        if ($allow_csv) {
            $lines = explode("\n", $content);
            $non_empty_lines = array();
            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $non_empty_lines[] = trim($line);
                }
            }
            $total_lines = count($non_empty_lines);

            // If single line, check if it's a list of comma-separated emails (not CSV)
            if ($total_lines === 1) {
                $line = $non_empty_lines[0];
                $delimiter = (strpos($line, ';') !== false) ? ';' : ',';
                $parts = explode($delimiter, $line);

                // If all parts look like emails, it's NOT CSV, it's a plain email list
                $email_count = 0;
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (filter_var($part, FILTER_VALIDATE_EMAIL) !== FALSE ||
                        preg_match('/<(.+)>/', $part, $matches) && filter_var($matches[1], FILTER_VALIDATE_EMAIL)) {
                        $email_count++;
                    }
                }

                // If more than 50% of parts are valid emails, treat as email list, not CSV
                if ($email_count > count($parts) * 0.5) {
                    $is_csv = false;
                } else {
                    $is_csv = true; // Looks like CSV: email,name format
                }
            } else {
                // Multiple lines - use original detection logic
                $first_lines = array_slice($non_empty_lines, 0, 5);
                $csv_count = 0;

                foreach ($first_lines as $line) {
                    $line = trim($line);
                    // Check if line has delimiter AND is not "Name <email>" format
                    if ((strpos($line, ',') !== false || strpos($line, ';') !== false) &&
                        !preg_match('/^[^<>]+<[^>]+>$/', $line)) {
                        $csv_count++;
                    }
                }

                // If more than 60% of lines look like CSV, treat as CSV
                if (count($first_lines) > 0 && ($csv_count / count($first_lines)) > 0.6) {
                    $is_csv = true;
                }
            }
        }

        // If CSV detected, use CSV parser
        if ($is_csv) {
            // Auto-detect delimiter
            if ($delimiter === null) {
                $first_line = trim(explode("\n", $content)[0]);
                $delimiter = (substr_count($first_line, ';') > substr_count($first_line, ',')) ? ';' : ',';
            }

            $config = array(
                'delimiter' => $delimiter,
                'has_header' => false,
                'email_col' => -1  // Scan all columns for emails
            );

            return parse_csv_emails($content, $config);
        }

        // Otherwise use text parser
        return parse_text_emails($content);
    }
}

/**
 * Parse text file content for emails (one per line)
 *
 * @param string $content Text content
 * @return array Array of valid emails with metadata (email, valid, error)
 */
if (!function_exists('parse_text_emails')) {
    function parse_text_emails($content) {
        if (empty($content)) {
            return array();
        }

        $lines = explode("\n", $content);
        $result = array();

        foreach ($lines as $line_number => $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Check if line contains multiple emails separated by comma or semicolon
            // But NOT if it's in "Name <email>" format
            $emails_in_line = array($line);
            if (!preg_match('/^[^<>]+<[^>]+>$/', $line)) {
                // Split by comma or semicolon if not in Name<email> format
                if (strpos($line, ',') !== false) {
                    $emails_in_line = explode(',', $line);
                } elseif (strpos($line, ';') !== false) {
                    $emails_in_line = explode(';', $line);
                }
            }

            foreach ($emails_in_line as $email_part) {
                $email_part = trim($email_part);
                if (empty($email_part)) {
                    continue;
                }

                $name = '';
                $email = $email_part;

                // Handle "Name <email@example.com>" format
                if (preg_match('/^(.+?)\s*<(.+?)>$/', $email_part, $matches)) {
                    $name = trim($matches[1]);
                    $email = trim($matches[2]);
                }

                $valid = validate_email($email);

                $result[] = array(
                    'email' => $email,
                    'name' => $name,
                    'normalized' => normalize_email($email),
                    'valid' => $valid,
                    'error' => $valid ? '' : 'Invalid email format: "' . $email_part . '"',
                    'line' => $line_number + 1
                );
            }
        }

        return $result;
    }
}

/**
 * Parse CSV content for emails
 *
 * @param string $content CSV content
 * @param array $config Configuration (email_col, name_col, firstname_col, has_header, delimiter)
 * @return array Array of parsed emails with metadata
 */
if (!function_exists('parse_csv_emails')) {
    function parse_csv_emails($content, $config = array()) {
        if (empty($content)) {
            return array();
        }

        $email_col = isset($config['email_col']) ? (int)$config['email_col'] : -1; // -1 means scan all columns
        $name_col = isset($config['name_col']) ? (int)$config['name_col'] : -1;
        $firstname_col = isset($config['firstname_col']) ? (int)$config['firstname_col'] : -1;
        $has_header = isset($config['has_header']) ? (bool)$config['has_header'] : TRUE;
        $delimiter = isset($config['delimiter']) ? $config['delimiter'] : ',';

        $lines = str_getcsv($content, "\n");
        $result = array();
        $start_line = $has_header ? 1 : 0;

        for ($i = $start_line; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i], $delimiter);

            if (empty($row)) {
                continue;
            }

            // If email_col is specified, use only that column
            if ($email_col >= 0) {
                if (count($row) <= $email_col) {
                    continue;
                }

                $email = trim($row[$email_col]);

                if (empty($email)) {
                    continue;
                }

                $name = '';
                $firstname = '';

                if ($name_col >= 0 && isset($row[$name_col])) {
                    $name = trim($row[$name_col]);
                }

                if ($firstname_col >= 0 && isset($row[$firstname_col])) {
                    $firstname = trim($row[$firstname_col]);
                }

                $display_name = trim($firstname . ' ' . $name);

                $valid = validate_email($email);

                $result[] = array(
                    'email' => $email,
                    'normalized' => normalize_email($email),
                    'name' => $name,
                    'firstname' => $firstname,
                    'display_name' => $display_name,
                    'valid' => $valid,
                    'error' => $valid ? '' : 'Invalid email format: "' . $email . '" (line ' . ($i + 1) . ')',
                    'line' => $i + 1
                );
            } else {
                // Scan all columns for email addresses
                foreach ($row as $col_index => $cell) {
                    $cell = trim($cell);
                    
                    if (empty($cell)) {
                        continue;
                    }

                    // Check if this cell contains a valid email
                    if (validate_email($cell)) {
                        // Try to get name from adjacent columns if available
                        $name = '';
                        $firstname = '';
                        $display_name = '';

                        // Try to use firstname (col 0) and name (col 1) if available
                        if ($col_index > 1) {
                            if (isset($row[0])) {
                                $firstname = trim($row[0]);
                            }
                            if (isset($row[1])) {
                                $name = trim($row[1]);
                            }
                            $display_name = trim($firstname . ' ' . $name);
                        }

                        $result[] = array(
                            'email' => $cell,
                            'normalized' => normalize_email($cell),
                            'name' => $name,
                            'firstname' => $firstname,
                            'display_name' => $display_name,
                            'valid' => true,
                            'error' => '',
                            'line' => $i + 1
                        );
                    }
                }
            }
        }

        return $result;
    }
}

/**
 * Detect duplicate emails in a list
 *
 * @param array $new_emails New emails to check
 * @param array $existing_emails Existing emails to compare against
 * @return array Array of duplicates with details
 */
if (!function_exists('detect_duplicates')) {
    function detect_duplicates($new_emails, $existing_emails) {
        $duplicates = array();
        $existing_normalized = array();

        // Build normalized map of existing emails
        foreach ($existing_emails as $existing) {
            $email = is_array($existing) ? (isset($existing['email']) ? $existing['email'] : '') : $existing;
            if (!empty($email)) {
                $existing_normalized[normalize_email($email)] = $email;
            }
        }

        // Check for duplicates in new emails
        foreach ($new_emails as $new) {
            $email = is_array($new) ? (isset($new['email']) ? $new['email'] : '') : $new;
            if (empty($email)) {
                continue;
            }

            $normalized = normalize_email($email);

            if (isset($existing_normalized[$normalized])) {
                $duplicates[] = array(
                    'new_email' => $email,
                    'existing_email' => $existing_normalized[$normalized],
                    'normalized' => $normalized
                );
            }
        }

        return $duplicates;
    }
}
