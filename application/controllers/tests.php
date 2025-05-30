<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
set_include_path(getcwd() . "/..:" . get_include_path());
include(APPPATH . '/third_party/phpqrcode/qrlib.php');

class Tests extends CI_Controller {
    function __construct() {
        date_default_timezone_set('Europe/Paris');
        parent::__construct();
        // Check if user is logged in or not
        $this->load->library('DX_Auth');
        // if (!getenv('TEST') && !$this->dx_auth->is_logged_in()) {
        // redirect("auth/login");
        // }
        $this->load->library('unit_test');

        $this->lang->load('gvv');
    }

    function index() {
        load_last_view('tests');
    }

    /**
     * entry for experimentation
     */
    public function hello() {
        echo ("Hello world;");

        $url = 'https://gvv.planeur-abbeville.fr/index.php/auth/login';
        $fields = array(
            'username' => 'fpeignot',
            'password' => 'xxxxx'
        );

        $fields_string = "";
        //url-ify the data for the POST
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

        //execute post
        $result = curl_exec($ch);
        var_dump($result);

        //close connection
        curl_close($ch);
        echo ("Bye!");
    }

    /**
     * entry for experimentation
     */
    public function fetch() {
        /**
         * Ca marche copie le le fichier HTML reçu dans
        C:\Users\frede\Dropbox\xampp\htdocs\gvv2\example_homepage.txt
         */

        echo ("Hello world;");
        $ch = curl_init("http://www.example.com/");
        $fp = fopen("example_homepage.txt", "w");

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_exec($ch);
        if (curl_error($ch)) {
            fwrite($fp, curl_error($ch));
        }
        curl_close($ch);
        fclose($fp);
        $ch = curl_init("http://www.example.com/");
        $fp = fopen("example_homepage.txt", "w");

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_exec($ch);
        if (curl_error($ch)) {
            fwrite($fp, curl_error($ch));
        }
        curl_close($ch);
        fclose($fp);
        echo ("Bye!");
    }
    /**
     * entry for experimentation
     */
    public function fetch2() {
        /**
         * Ca marche copie le le fichier HTML reçu dans
         C:\Users\frede\Dropbox\xampp\htdocs\gvv2\example_homepage.txt
         */

        echo ("Fetching ACS;");
        $fields = array(
            'username' => 'fpeignot',
            'password' => 'didamu'
        );

        $fields_string = "";
        //url-ify the data for the POST
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        $ch = curl_init("https://gvv.planeur-abbeville.fr/index.php/auth/login");
        $fp = fopen("acs_homepage.txt", "w");

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        echo ($fields_string);

        curl_exec($ch);
        if (curl_error($ch)) {
            fwrite($fp, curl_error($ch));
        }
        curl_close($ch);
        fclose($fp);

        echo ("Bye!");
    }

    /**
     * Helpers
     */

    /**
     * assets_helper unit test
     */
    public function test_assets_helper() {
        $this->unit->header("Helper assets");
        $res = $this->load->helper('assets');

        $theme = theme();
        $this->unit->run($theme, base_url() . "themes/binary-news", "default theme");

        $css = css_url("menu");
        $this->unit->run($css, $theme . "/css/menu.css", "find a css file into a theme");

        $javascript_url = js_url("menu");
        $this->unit->run($javascript_url, base_url() . "assets/javascript/menu.js", "Javascript URL");

        $img_url = img_url("menu");
        $asset_url = asset_url("menu");
        $controller_url = controller_url("menu");
        $image = image_dir();
    }

    /**
     * Form elements helper unit test
     */
    public function test_form_elements_helper($what = "") {
        $this->unit->header("Helper Form elements");
        $res = $this->load->helper('form_elements');

        $mniveaux = 0;
        $checkbox_array = checkbox_array('mniveau', 1, $mniveaux);

        $input = input_field("name", "value", "");

        $mlogin = 'moi';
        $pilote_selector = array();
        dropdown_field('mlogin', $mlogin, $pilote_selector, "id='selector' onchange='new_selection();'");

        $table = array(
            array(
                6,
                12,
                17
            ),
            array(
                7,
                11,
                23
            )
        );
        $this->unit->run(count($table), 2, "Initial table size");
        add_first_row($table, array(
            'Jan',
            'Fev',
            'Mar'
        ));
        $this->unit->run(count($table), 3, "table size after add_first_row");

        add_first_col($table, array(
            'Année',
            '2013',
            '2014'
        ));

        $this->unit->run($table[0][0], "Année", '$table[0][0]');
        $this->unit->run($table[1][0], "2013", '$table[1][0]');
        $this->unit->run($table[1][1], 6, '$table[1][1]');
        $this->unit->run($table[2][2], 11, '$table[2][2]');
    }

    /**
     * csv_helper unit test
     */
    public function test_csv_helper() {
        $this->unit->header("Helper csv");
        $res = $this->load->helper('csv');

        $table = array(
            array(
                "Nom",
                "Age"
            ),
            array(
                "Jean",
                18
            ),
            array(
                "Mathusalem",
                99
            )
        );
        $this->unit->run(csv_file("test.csv", $table, false) != "", "CSV not empty");
    }

    /**
     * All helpers unit tests
     */
    public function test_helpers() {
        $this->test_assets_helper();
        $this->test_form_elements_helper();
        $this->test_csv_helper();

        $this->unit->XML_result("results/test_helpers.xml", "Test Helpers");
        echo $this->unit->report();
    }

    /**
     * Libraries
     * ***********************************************************************************
     */

    /**
     * Widget library unit test
     */
    public function test_widget_library() {
        $this->unit->header("Widget library unit tests");

        $res = $this->load->library('Widget');

        $w = new Widget(array(
            'color' => 'orange'
        ));
        $this->unit->run($w != NULL, TRUE, "Widget library loaded");
        $this->unit->run($w->get('color'), 'orange', "Widget default attribut");
        $w->set('color', 'red');
        $this->unit->run($w->get('color'), 'red', "Widget attribut value after modification");
        $w->display();
        $this->unit->run($w->image(), "", "Widget default image is empty");
    }

    /**
     * Test les services additionales de la librairie Log
     * (elle est toujours chargée)
     */
    public function test_log_library() {
        $user = get_current_user();
        $this->unit->run($user != "", true, "user=$user");
        // On some machine uid may be 0
        $uid = getmyuid();
        $this->unit->run($uid >= 0, true, "uid=$uid");
        // As well as the group ID
        $gid = getmygid();
        $this->unit->run($gid >= 0, true, "gid=$gid");
        $pid = getmypid();
        $this->unit->run($pid != 0, true, "pid=$pid");
        $fileowner = fileowner(".");
        $this->unit->run($fileowner >= 0, true, "fileowner=$fileowner");

        $log_dir = "application/logs";
        $this->unit->run(is_dir($log_dir), true, "$log_dir est un répertoire");
        $this->unit->run(is_writable($log_dir), true, "$log_dir est inscriptible");

        $size = $this->log->log_file_size();
        $this->unit->run(gvv_info('info', "Message d'info"), "", "gvv_info");
        $size_after_info = $this->log->log_file_size();
        $this->unit->run($size_after_info > $size, true, "gvv_info $size_after_info > $size");

        $this->unit->run(gvv_debug('debug', "Message de debug"), "", "gvv_debug");
        $size_after_debug = $this->log->log_file_size();
        $this->unit->run($size_after_debug > $size_after_info, true, "gvv_debug $size_after_debug > $size_after_info");

        $count = $this->log->count_lines("gvv_error", "ERROR");
        $this->unit->run(gvv_error('error', "Message d'erreur"), "", "gvv_error");
        $new_count = $this->log->count_lines("gvv_error", "ERROR");
        $this->unit->run($new_count == $count + 1, true, "count=$count + 1 == new_count=$new_count");

        $logfile = $this->log->log_file();
        $this->unit->run($logfile, true, "log file=$logfile");
    }

    /**
     * Button library unit test
     */
    public function test_button_library() {
        $this->unit->header("Button library unit tests");

        $res = $this->load->library('Button');

        $w = new Button(array(
            'color' => 'orange'
        ));
        $this->unit->run($w != NULL, TRUE, "Button library loaded");
        $this->unit->run($w->get('color'), 'orange', "Button default attribut");
        $w->set('color', 'red');
        $this->unit->run($w->get('color'), 'red', "Button attribut value after modification");
        $w->display();

        $res = $this->load->library('ButtonDelete');
        $w = new ButtonDelete();

        $res = $this->load->library('ButtonEdit');
        $w = new ButtonEdit();
    }

    /**
     * Unit test for GVVMetadata library
     */
    public function test_metadata_library() {
        $this->unit->header("Metadata library unit tests");

        // gvvmetadata is loaded by common models
        $gmd = new GVVMetadata();

        $tables = $this->gvvmetadata->tables_list();
        $this->unit->run(count($tables) > 0, true, "GVVMetadata several tables are declared");

        $cnt = 0;
        foreach ($tables as $table) {
            $cnt++;

            if ($cnt > 5)
                continue;
            $key = $this->gvvmetadata->table_key($table);
            $this->unit->run($key != "", true, "GVVMetadata table=$table as a key");

            $img_elt = $this->gvvmetadata->table_image_elt($table);

            $auto_key = $this->gvvmetadata->autogen_key($table);

            $fields = $this->gvvmetadata->fields_list($table);
            $this->unit->run(count($fields) > 0, true, "GVVMetadata table=$table as several fields");

            $cnt2 = 0;
            foreach ($fields as $field) {
                $cnt2++;
                if ($cnt2 > 5)
                    continue;

                $field_attrs = $this->gvvmetadata->field_attr($table, $field);

                $field_name = $this->gvvmetadata->field_name($table, $field);
                $this->unit->run($field_name != "", true, "GVVMetadata $table->$field field_name=$field_name");

                $type = $this->gvvmetadata->field_type($table, $field);
                $subtype = $this->gvvmetadata->field_subtype($table, $field);
                $default = $this->gvvmetadata->field_default($table, $field);

                $this->unit->run($type != "", true, "GVVMetadata $table->$field type=$type");
                // $this->unit->run($subtype != "", true, "GVVMetadata $table->$field subtype=$subtype");
            }
        }
    }

    /**
     * Unit test for PersistentCoverage library
     */
    public function test_coverage_library() {
        $this->unit->header("PersistentCoverage library unit tests");

        $this->load->library('PersistentCoverage', '', "cov");
        $this->cov->enable();
        $this->unit->run($this->cov->active(), true, "Coverage coverage enabled");
        $this->cov->disable();
        $this->unit->run($this->cov->active(), false, "Coverage coverage disabled");
        $this->cov->enable();
        $this->unit->run($this->cov->active(), true, "Coverage coverage re-enabled");

        $this->cov->start();
        $this->cov->stop();
        $this->cov->coverage_result();
        $this->unit->run(true, true, "Coverage generated");
    }

    /**
     * All Libraries unit tests
     */
    public function test_libraries() {
        $this->test_log_library();
        $this->test_widget_library();
        $this->test_button_library();
        $this->test_metadata_library();
        // $this->test_coverage_library();

        $this->unit->XML_result("results/test_libraries.xml", "Test Libraries");
        echo $this->unit->report();
    }

    /*
     * Convert a language file into a hash
     */
    private function to_hash($filename) {
        include($filename);
        return $lang;
    }

    /*
     * Check that the support for a language is complete
     */
    private function check_entries($ref_file, $lang_file, $identical) {
        $missing_keys = 0;

        $ref_hash = $this->to_hash($ref_file);
        $lang_hash = $this->to_hash($lang_file);

        foreach ($ref_hash as $key => $value) {
            if (! array_key_exists($key, $lang_hash)) {
                echo nbs(8) . "key $key not found in $lang_file" . br();
                $missing_keys++;
            } else {
                if (is_array($ref_hash[$key]) != is_array($lang_hash[$key])) {
                    echo nbs(8) . "incoherent array type for $key" . br();
                } elseif (is_array($ref_hash[$key]) && (count($ref_hash[$key]) != count($lang_hash[$key]))) {
                    echo nbs(8) . "different number of array elements for $key" . br();
                } elseif ($identical && ($ref_hash[$key] == $lang_hash[$key])) {
                    echo nbs(12) . "value for $key = $value, identical, may be not translated yet" . br();
                }
            }
        }
        return $missing_keys;
    }

    /*
     * Check that the support for a language is complete
     */
    public function check_lang($lang = "english", $identical = 0, $type = "application") {
        $lang_ref = "french";

        echo "Reference language=$lang_ref" . br();
        echo "Checked language=$lang" . br();

        $missing_files = 0;
        $missing_keys = 0;
        $not_translated = 0;

        $pwd = getcwd();

        $ref_files = array();
        $lang_files = array();

        $ref_dir = $pwd . "/$type/language/" . $lang_ref;
        if (! is_dir($ref_dir)) {
            echo "Reference language $ref_dir not found" . br();
            exit();
        }

        $lang_dir = $pwd . "/$type/language/" . $lang;
        if (! is_dir($lang_dir)) {
            echo "Language directory $lang_dir not found" . br();
            exit();
        }

        echo br();

        // Check that all files are found in the checked language
        if ($dh = opendir($ref_dir)) {
            while (($file = readdir($dh)) !== false) {
                if (preg_match('/.*\.php$/', $file, $matches)) {
                    // echo "fichier : $file : type : " . filetype($ref_dir . $file) . br();
                    $ref_files[] = $file;

                    $lang_file = $lang_dir . '/' . $file;

                    if (! file_exists($lang_file)) {
                        echo "$lang_file not found" . br();
                        $missing_files++;
                    }
                }
            }
            closedir($dh);
        }

        echo br();
        // Check that all entries are found in the checked language
        if ($dh = opendir($ref_dir)) {
            while (($file = readdir($dh)) !== false) {
                if (preg_match('/.*\.php$/', $file, $matches)) {
                    $ref_file = $ref_dir . '/' . $file;
                    $lang_file = $lang_dir . '/' . $file;

                    if (file_exists($lang_file)) {
                        echo "Checking $file" . br();
                        $missing_keys += $this->check_entries($ref_file, $lang_file, $identical);
                    }
                }
            }
            closedir($dh);
        }

        echo br();
        echo "Missing files = $missing_files, missing entries = $missing_keys" . br();
    }
    function upload() {
        $data = array();
        $data['msg'] = "upload form";
        $data['controller'] = "tests";
        $data['action'] = "action";
        $data['error'] = "";
        load_last_view('tests/upload_form', $data);
    }

    /*
     * function to connect to the upload button
     * Probably supposed to make the upload and return some progress status in Ajax
     */
    function uploading() {
        $userfile = $this->input->post('name');
        echo "uploading $userfile ...." . br();
    }

    function do_upload() {
        $config['upload_path'] = './uploads/';
        $config['allowed_types'] = 'gif|jpg|png|avi';
        $config['allowed_types'] = '*';
        $config['max_size'] = '2000000'; // 2 Gb
        // $config['max_width'] = '1024';
        // $config['max_height'] = '768';
        $this->load->library('upload', $config);

        if (! $this->upload->do_upload('userfile')) {
            $error = array(
                'error' => $this->upload->display_errors()
            );

            load_last_view('tests/upload_form', $error);
        } else {
            $data = array(
                'upload_data' => $this->upload->data()
            );

            load_last_view('tests/upload_success', $data);
        }
    }

    function exception() {
        throw new ErrorException("Test exception");
    }

    function error() {
        show_error("GVV error");
    }

    function qr() {
        //echo "QR code";
        QRcode::png('https://example.com');
        //QRcode::png('https://example.com', 'qrcode.png');
    }
}

/* End of file tests.php */
/* Location: ./application/controllers/tests.php */