<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Override CI_Security to avoid deprecated /e modifier in entity_decode
 * Compatible with PHP 7.4+
 */
class MY_Security extends CI_Security
{
    /**
     * HTML Entities Decode (patched)
     *
     * @param string $str
     * @param string $charset
     * @return string
     */
    public function entity_decode($str, $charset = 'UTF-8')
    {
        if (stristr($str, '&') === FALSE) {
            return $str;
        }

        $str = html_entity_decode($str, ENT_COMPAT, $charset);

        $str = preg_replace_callback('~&#x(0*[0-9a-f]{2,5})~i', function ($matches) {
            return chr(hexdec($matches[1]));
        }, $str);

        $str = preg_replace_callback('~&#([0-9]{2,4})~', function ($matches) {
            return chr($matches[1]);
        }, $str);

        return $str;
    }
}

/* End of file MY_Security.php */
/* Location: ./application/core/MY_Security.php */
