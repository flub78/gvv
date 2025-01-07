<?php
// application/libraries/Parsedown.php
// First, download Parsedown.php from https://github.com/erusev/parsedown/blob/master/Parsedown.php
// and place it in your application/libraries folder
require_once APPPATH . 'libraries/Parsedown.php';

class MY_Parsedown {
    private $parser;
    
    public function __construct() {
        $this->parser = new Parsedown();
    }
    
    public function text($markdown) {
        return $this->parser->text($markdown);
    }
}

// application/helpers/markdown_helper.php
if (!function_exists('markdown')) {
    function markdown($text) {
        $CI =& get_instance();
        if (!isset($CI->my_parsedown)) {
            $CI->load->library('MY_Parsedown', null, 'my_parsedown');
        }
        return $CI->my_parsedown->text($text);
    }
}

// Example usage in your controller:
class Your_Controller extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->helper('markdown');
    }
    
    public function some_method() {
        $data['markdown_content'] = "# Hello World\n\nThis is **bold** text.";
        $this->load->view('your_view', $data);
    }
}

// Example usage in your view (your_view.php):
?>
<div class="markdown-content">
    <?php echo markdown($markdown_content); ?>
</div>
