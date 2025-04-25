<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Openflyers_scraper extends CI_Controller {

    private $cookie_file;
    private $base_url = 'https://openflyers.com/abbeville/'; // Base URL from your Selenium script

    public function __construct() {
        parent::__construct();
        $this->load->helper('url');

        // Create a temporary cookie file to store session cookies
        $this->cookie_file = tempnam(sys_get_temp_dir(), 'cookie_');

        // You might want to add access control here if needed
        // E.g., check if user is logged in to your CI application
    }

    public function index() {
        echo '<h1>OpenFlyers Data Extractor</h1>';
        echo '<p><a href="' . site_url('openflyers_scraper/extract_data') . '">Extract OpenFlyers Data</a></p>';
    }

    public function extract_data() {
        // Step 1: Login to OpenFlyers
        if ($this->login()) {
            // Step 2: Navigate to the management section and extract data
            // $data = $this->navigate_and_extract();

            // Step 3: Logout
            $this->logout();

            // Step 4: Display or process the extracted data
            $this->display_results($data);
        } else {
            echo '<h2>Login Failed</h2>';
            echo '<p>Could not authenticate with OpenFlyers. Please check credentials.</p>';
        }
    }

    private function login() {
        // Initialize cURL session
        $ch = curl_init();

        // First, load the login page
        curl_setopt($ch, CURLOPT_URL, $this->base_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);
            curl_close($ch);
            return false;
        }

        // From your Selenium script, we can see the login process involves:
        // 1. Setting login and password fields
        // 2. Clicking "validation" button
        // 3. Clicking "Valider" link

        // Prepare login data based on the form fields in your Selenium script
        $login_data = array(
            'login' => 'fpeignot', // Replace with actual credentials or config values
            'rawPassword' => 'belobelo' // Replace with actual credentials or config values
        );

        // Submit login form
        curl_setopt($ch, CURLOPT_URL, $this->base_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($login_data));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'cURL Error during login: ' . curl_error($ch);
            curl_close($ch);
            return false;
        }

        // Check if we need to confirm with "Valider" link
        // This is based on your Selenium script which clicks a "Valider" link after submitting credentials
        if (strpos($response, 'Valider') !== false) {
            // Extract the validation URL - this might need adjustment based on actual HTML structure
            preg_match('/<a href="([^"]*)"[^>]*>Valider<\/a>/', $response, $matches);

            if (isset($matches[1])) {
                $validation_url = $this->resolve_url($matches[1]);

                curl_setopt($ch, CURLOPT_URL, $validation_url);
                curl_setopt($ch, CURLOPT_POST, false);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    echo 'cURL Error during validation: ' . curl_error($ch);
                    curl_close($ch);
                    return false;
                }
            }
        }

        // Verify successful login (check for elements that appear only after login)
        // Based on your Selenium script, we look for management section links
        $login_successful = (strpos($response, 'thumb-management') !== false ||
            strpos($response, 'disconnect') !== false);

        curl_close($ch);

        return $login_successful;
    }

    private function navigate_and_extract() {
        $data = array();

        // Step 1: Navigate to Management section
        $management_url = $this->get_management_url();
        if (!$management_url) {
            return $data; // Empty if navigation failed
        }

        // Step 2: Navigate to specific page (col:nth-child(5) > item:nth-child(2))
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $management_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'cURL Error navigating to management: ' . curl_error($ch);
            curl_close($ch);
            return $data;
        }

        // Extract the URL for the specific report we want
        // This corresponds to ".col:nth-child(5) > .item:nth-child(2) .txt" in your Selenium script
        $target_url = $this->extract_target_url($response);

        if (!$target_url) {
            echo 'Could not find target URL in management page';
            curl_close($ch);
            return $data;
        }

        // Now navigate to the target URL
        curl_setopt($ch, CURLOPT_URL, $target_url);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'cURL Error navigating to target page: ' . curl_error($ch);
            curl_close($ch);
            return $data;
        }

        // Additional navigation based on Selenium script
        // The script clicks "validation" and then "359" link

        // Click "validation" (submit a form or follow a link)
        if (strpos($response, 'id="validation"') !== false) {
            // Extract form action
            preg_match('/<form[^>]*action="([^"]*)"[^>]*>/', $response, $form_matches);

            if (isset($form_matches[1])) {
                $validation_url = $this->resolve_url($form_matches[1]);

                curl_setopt($ch, CURLOPT_URL, $validation_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, ''); // Empty POST data if no form fields needed

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    echo 'cURL Error during validation: ' . curl_error($ch);
                    curl_close($ch);
                    return $data;
                }
            }
        }

        // Click "359" link
        if (strpos($response, '>359<') !== false) {
            preg_match('/<a href="([^"]*)"[^>]*>359<\/a>/', $response, $link_matches);

            if (isset($link_matches[1])) {
                $link_url = $this->resolve_url($link_matches[1]);

                curl_setopt($ch, CURLOPT_URL, $link_url);
                curl_setopt($ch, CURLOPT_POST, false);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    echo 'cURL Error following link: ' . curl_error($ch);
                    curl_close($ch);
                    return $data;
                }
            }
        }

        // Now we should be on the data page
        // Extract the relevant data using HTML parsing
        $data = $this->parse_data($response);

        curl_close($ch);
        return $data;
    }

    private function get_management_url() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);
            curl_close($ch);
            return false;
        }

        // Extract the management URL from the page
        // Based on your Selenium script: ".thumb-management > .link"
        preg_match('/<div[^>]*class="[^"]*thumb-management[^"]*"[^>]*>.*?<a[^>]*href="([^"]*)"[^>]*class="[^"]*link[^"]*"/s', $response, $matches);

        curl_close($ch);

        if (isset($matches[1])) {
            return $this->resolve_url($matches[1]);
        }

        return false;
    }

    private function extract_target_url($html) {
        // Extract URL for ".col:nth-child(5) > .item:nth-child(2) .txt"
        preg_match('/<div[^>]*class="[^"]*col[^"]*"[^>]*>(?:.*?<div[^>]*class="[^"]*item[^"]*"[^>]*>){1}.*?<div[^>]*class="[^"]*col[^"]*"[^>]*>(?:.*?<div[^>]*class="[^"]*item[^"]*"[^>]*>){1}.*?<div[^>]*class="[^"]*col[^"]*"[^>]*>(?:.*?<div[^>]*class="[^"]*item[^"]*"[^>]*>){1}.*?<div[^>]*class="[^"]*col[^"]*"[^>]*>(?:.*?<div[^>]*class="[^"]*item[^"]*"[^>]*>){1}.*?<div[^>]*class="[^"]*col[^"]*"[^>]*>(?:.*?<div[^>]*class="[^"]*item[^"]*"[^>]*>){1}.*?<a[^>]*href="([^"]*)"[^>]*class="[^"]*txt[^"]*"/s', $html, $matches);

        // This is a complex regex that might need tuning based on actual HTML structure
        // Alternatively, consider using a proper HTML parser

        if (isset($matches[1])) {
            return $this->resolve_url($matches[1]);
        }

        return false;
    }

    private function parse_data($html) {
        // Load HTML parser library (Simple HTML DOM or DOMDocument)
        $this->load->library('simple_html_dom');
        $dom = str_get_html($html);

        $data = array();

        if (!$dom) {
            return $data;
        }

        // Extract data based on the structure of the target page
        // This is a placeholder - you'll need to adjust based on actual HTML structure

        // Example: Extract table data if available
        foreach ($dom->find('table tr') as $row) {
            $row_data = array();

            foreach ($row->find('td') as $cell) {
                $row_data[] = trim($cell->plaintext);
            }

            if (!empty($row_data)) {
                $data[] = $row_data;
            }
        }

        // If specific data needs to be extracted
        // Extract based on specific CSS selectors or HTML structure

        // Clean up
        $dom->clear();
        unset($dom);

        return $data;
    }

    private function logout() {
        $ch = curl_init();

        // Initialize cURL session for the page with the disconnect button
        curl_setopt($ch, CURLOPT_URL, $this->base_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'cURL Error navigating to logout page: ' . curl_error($ch);
            curl_close($ch);
            return false;
        }

        // Extract the logout URL (disconnect button)
        preg_match('/<a[^>]*class="[^"]*disconnect[^"]*"[^>]*href="([^"]*)"/', $response, $matches);

        if (isset($matches[1])) {
            $logout_url = $this->resolve_url($matches[1]);

            curl_setopt($ch, CURLOPT_URL, $logout_url);
            curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'cURL Error during logout: ' . curl_error($ch);
                curl_close($ch);
                return false;
            }
        }

        curl_close($ch);

        // Delete the cookie file when done
        if (file_exists($this->cookie_file)) {
            unlink($this->cookie_file);
        }

        return true;
    }

    private function resolve_url($url) {
        // Convert relative URLs to absolute URLs
        if (strpos($url, 'http') !== 0) {
            // Handle different types of relative URLs
            if (strpos($url, '/') === 0) {
                // URL starts with /
                $parsed_base = parse_url($this->base_url);
                $url = $parsed_base['scheme'] . '://' . $parsed_base['host'] . $url;
            } else {
                // URL is relative to current directory
                $url = rtrim($this->base_url, '/') . '/' . $url;
            }
        }

        return $url;
    }

    private function display_results($data) {
        echo '<h2>Extracted OpenFlyers Data</h2>';

        if (empty($data)) {
            echo '<p>No data was extracted or the scraping process didn\'t find the expected data structure.</p>';
            return;
        }

        echo '<table border="1" cellpadding="5">';
        foreach ($data as $index => $row) {
            if ($index === 0) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<th>' . htmlspecialchars($cell) . '</th>';
                }
                echo '</tr>';
            } else {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . htmlspecialchars($cell) . '</td>';
                }
                echo '</tr>';
            }
        }
        echo '</table>';

        // Option to save data to CSV or database
        echo '<p><a href="' . site_url('openflyers_scraper/save_data') . '">Save this data</a></p>';
    }

    public function save_data() {
        // Implementation for saving data
        // This could save to database, CSV file, etc.
        // You'd store the data in session or elsewhere first
        echo '<h2>Data Saving Functionality</h2>';
        echo '<p>This would save the extracted data to your database or file system.</p>';
    }
}
