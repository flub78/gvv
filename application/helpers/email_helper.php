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
            $email = trim($line);

            if (empty($email)) {
                continue;
            }

            $valid = validate_email($email);

            $result[] = array(
                'email' => $email,
                'normalized' => normalize_email($email),
                'valid' => $valid,
                'error' => $valid ? '' : 'Invalid email format',
                'line' => $line_number + 1
            );
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

        $email_col = isset($config['email_col']) ? (int)$config['email_col'] : 0;
        $name_col = isset($config['name_col']) ? (int)$config['name_col'] : -1;
        $firstname_col = isset($config['firstname_col']) ? (int)$config['firstname_col'] : -1;
        $has_header = isset($config['has_header']) ? (bool)$config['has_header'] : TRUE;
        $delimiter = isset($config['delimiter']) ? $config['delimiter'] : ',';

        $lines = str_getcsv($content, "\n");
        $result = array();
        $start_line = $has_header ? 1 : 0;

        for ($i = $start_line; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i], $delimiter);

            if (empty($row) || count($row) <= $email_col) {
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
                'error' => $valid ? '' : 'Invalid email format',
                'line' => $i + 1
            );
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
