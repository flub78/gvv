<?php
/*
 * GVV English translation
*/

# Terrains view

$lang['gvv_terrains_title'] = "Airfield";
$lang['gvv_terrains_title_list'] = "Airfields";

$lang['gvv_terrains_field_oaci'] = "OACI code";
$lang['gvv_terrains_field_nom'] = "Airfield name";
$lang['gvv_terrains_field_freq1'] = "Main frequency";
$lang['gvv_terrains_field_freq2'] = "Secondary frequency";
$lang['gvv_terrains_field_comment'] = "Description";

$lang['gvv_vue_terrains_short_field_oaci'] = "OACI";
$lang['gvv_vue_terrains_short_field_nom'] = "Name";
$lang['gvv_vue_terrains_short_field_freq1'] = $lang['gvv_terrains_field_freq1'];
$lang['gvv_vue_terrains_short_field_freq2'] = $lang['gvv_terrains_field_freq2'];
$lang['gvv_vue_terrains_short_field_comment'] = $lang['gvv_terrains_field_comment'];

# Quick airfield creation from the flight entry form
$lang['gvv_terrains_ajax_add_title'] = "Add an airfield missing from the list";
$lang['gvv_terrains_ajax_modal_title'] = "New airfield";
$lang['gvv_terrains_ajax_help'] = "The airfield will be added to the list and selected.";
$lang['gvv_terrains_ajax_submit'] = "Create airfield";
$lang['gvv_terrains_ajax_cancel'] = "Cancel";
$lang['gvv_terrains_ajax_error_oaci_required'] = "The OACI code is required.";
$lang['gvv_terrains_ajax_error_oaci_too_long'] = "The OACI code must not exceed 10 characters.";
$lang['gvv_terrains_ajax_error_oaci_exists'] = "An airfield with this OACI code already exists.";
$lang['gvv_terrains_ajax_error_nom_required'] = "The airfield name is required.";
$lang['gvv_terrains_ajax_error_freq_invalid'] = "The frequency must be a number (e.g. 122.500).";
$lang['gvv_terrains_ajax_error_save'] = "Airfield creation failed.";
$lang['gvv_terrains_ajax_error_network'] = "Network error, the airfield could not be created.";
