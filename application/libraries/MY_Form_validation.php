<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 * With this extension it is possible to specigy callback in the controller
 * (and inherit them)
 * @author unknown
 *
 */
class MY_Form_validation extends CI_Form_validation
{
    function run($module = '', $group = '') {
        (is_object($module)) AND $this->CI =& $module;
        return parent::run($group);
    }
}
?>