<?php

include('./application/libraries/Gvv_Controller.php');
class Associations_ecriture extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'associations_ecriture';
    protected $model = 'associations_ecriture_model';
    protected $modification_level = 'ca';
    protected $rules = array();

    protected function form_static_element($action) {
        $this->data['action'] = $action;
        $this->data['fields'] = $this->fields;
        $this->data['controller'] = $this->controller;
        if ($action == "visualisation") {
            $this->data['readonly'] = "readonly";
        }

        $this->data['saisie_par'] = $this->dx_auth->get_username();

        $this->load->model('comptes_model');
        $compte_selector = $this->comptes_model->selector_with_null([], TRUE);
        $this->gvvmetadata->set_selector('compte_selector', $compte_selector);
    }

    function form2database($action = '') {
        $processed_data = parent::form2database($action);

        if (!$processed_data['id_compte_gvv']) {
            unset($processed_data['id_compte_gvv']);
        }
        return $processed_data;
    }

    function test($format = "html") {
        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Tests $this->controller");
        $this->tests_results($format);
    }
}