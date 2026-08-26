<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Interprète forms.required_params (ENUM combinant pilote/instructeur/machine,
 * ex. 'pilot+instructor+machine') sans dupliquer la liste des combinaisons
 * dans le contrôleur forms_admin et dans ses vues bs_generate/bs_submissions.
 */

function forms_requires_pilot($required_params) {
    return in_array($required_params, array('pilot', 'pilot+instructor', 'pilot+machine', 'pilot+instructor+machine'), true);
}

function forms_requires_instructor($required_params) {
    return in_array($required_params, array('instructor', 'pilot+instructor', 'instructor+machine', 'pilot+instructor+machine'), true);
}

function forms_requires_machine($required_params) {
    return in_array($required_params, array('machine', 'pilot+machine', 'instructor+machine', 'pilot+instructor+machine'), true);
}
