<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

include_once ("Gvvmetadata.php");

/**
 *
 * Metadata for Mail
 *
 * @author idefix
 * @package librairies
 */
class EventsTypesMetadata extends GVVMetadata {
    /**
     *
     * Constructor
     */
    function __construct() {
        parent :: __construct();
        
        $CI = & get_instance();
        $CI->lang->load('events_types');
        
        /**
         * Events types
         */
        $this->keys['vue_events_types'] = 'id';
        $this->field['events_types']['id']['Subtype'] = 'key';
        
        $this->alias_table["vue_events_types"] = "events_types";
                       
        $this->field['events_types']['activite']['Subtype'] = 'enumerate';
        $this->field['events_types']['activite']['Enumerate'] =  $CI->lang->line("gvv_events_types_enum_activite");
        $this->field['events_types']['en_vol']['Subtype'] = 'boolean';
        $this->field['events_types']['multiple']['Subtype'] = 'boolean';
        $this->field['events_types']['expirable']['Subtype'] = 'boolean';
        $this->field['events_types']['annual']['Subtype'] = 'boolean';
        
        // $this->dump();
    }
}