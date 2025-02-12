<?php
class Auth extends CI_Controller {
    // Used for registering and changing password form validation
    var $min_username = 4;
    var $max_username = 20;
    var $min_password = 4;
    var $max_password = 20;

    function __construct() {
        parent::__construct();

        $this->load->library('Form_validation');
        $this->load->library('DX_Auth');

        $this->load->helper('url');
        $this->load->helper('form');

        $this->lang->load('auth');
        $this->load->model('sections_model');
    }

    function index() {
        $this->login();
    }

    /* Callback function */
    function username_check($username) {
        $result = $this->dx_auth->is_username_available($username);
        if (! $result) {
            $this->form_validation->set_message('username_check', $this->lang->line("auth_existing_user"));
        }

        return $result;
    }

    function email_check($email) {
        $result = $this->dx_auth->is_email_available($email);
        if (! $result) {
            $this->form_validation->set_message('email_check', $this->lang->line("auth_existing_email"));
        }

        return $result;
    }

    function captcha_check($code) {
        $result = TRUE;

        if ($this->dx_auth->is_captcha_expired()) {
            // Will replace this error msg with $lang
            $this->form_validation->set_message('captcha_check', $this->lang->line("auth_expired_code"));
            $result = FALSE;
        } elseif (! $this->dx_auth->is_captcha_match($code)) {
            $this->form_validation->set_message('captcha_check', $this->lang->line("auth_incorrect_captcha"));
            $result = FALSE;
        }

        return $result;
    }

    function recaptcha_check() {
        $result = $this->dx_auth->is_recaptcha_match();
        if (! $result) {
            $this->form_validation->set_message('recaptcha_check', $this->lang->line("auth_incorrect_captcha"));
        }

        return $result;
    }

    /* End of Callback function */

    function _login() {
        $data['url_club'] = $this->config->item('url_club');
        $data['sections_selector'] = $this->sections_model->selector_with_all();

        if (! $this->dx_auth->is_logged_in()) {
            $val = $this->form_validation;

            // Set form validation rules
            $val->set_rules('username', 'lang:auth_username', 'trim|required|xss_clean');
            $val->set_rules('password', 'lang:auth_password', 'trim|required|xss_clean');
            $val->set_rules('remember', 'lang:auth_remember_me', 'integer');

            // Set captcha rules if login attempts exceed max attempts in config
            if ($this->dx_auth->is_max_login_attempts_exceeded()) {
                $val->set_rules('captcha', 'lang:auth_confirmation_code', 'trim|required|xss_clean|callback_captcha_check');
            }

            if (
                $val->run() and
                $this->dx_auth->login($val->set_value('username'), $val->set_value('password'), $val->set_value('remember'))
            ) {
                // Login success
                // Redirect to homepage
                gvv_info("Login: " . $val->set_value('username'));

                // set some session defaults

                // By default only display active items
                $session = [
                    'filter_active' => 1,
                    'filter_25' => 0,
                    'filter_membre_actif' => 2,
                    'filter_machine_actif' => 2
                ];
                if ($this->input->post('legacy_gui')) {
                    $session['legacy_gui'] = true;
                } else {
                    $this->session->unset_userdata('legacy_gui');
                }
                if ($this->input->post('section')) {
                    $section = $this->input->post('section');
                    $session['section'] = $section;
                }
                $this->session->set_userdata($session);

                redirect('', 'location');
            } else {
                // Check if the user is failed logged in because user is banned user or not
                if ($this->dx_auth->is_banned()) {
                    // Redirect to banned uri
                    $this->dx_auth->deny_access('banned');
                } else {
                    // Default is we don't show captcha until max login attempts eceeded
                    $data['show_captcha'] = FALSE;

                    // Show captcha if login attempts exceed max attempts in config
                    if ($this->dx_auth->is_max_login_attempts_exceeded()) {
                        // Create catpcha
                        $this->dx_auth->captcha();

                        // Set view data to show captcha on view file
                        $data['show_captcha'] = TRUE;
                    }

                    $this->load->config('program');
                    $data['locked'] = $this->config->item('locked');

                    // Load login page view
                    load_last_view($this->dx_auth->login_view, $data);
                }
            }
        } else {
            $data['auth_message'] = $this->lang->line("auth_already_connected");
            load_last_view($this->dx_auth->logged_in_view, $data);
        }
    }

    function login() {
        $this->session->unset_userdata('mobile');
        $this->_login();
    }

    function logout() {
        gvv_info("Logout: " . $this->dx_auth->get_username());

        // Il faut aller chercher les info de sessions avant de quitter la session

        $this->dx_auth->logout();
        redirect("auth/login");
    }

    function register() {
        if (! $this->dx_auth->is_logged_in() and $this->dx_auth->allow_registration) {
            $val = $this->form_validation;

            // Set form validation rules
            $val->set_rules('username', 'lang:auth_user_name', 'trim|required|xss_clean|min_length[' . $this->min_username . ']|max_length[' . $this->max_username . ']|callback_username_check|alpha_dash');
            $val->set_rules('password', 'lang:auth_password', 'trim|required|xss_clean|min_length[' . $this->min_password . ']|max_length[' . $this->max_password . ']|matches[confirm_password]');
            $val->set_rules('confirm_password', "lang:auth_confirm_password", 'trim|required|xss_clean');
            $val->set_rules('email', 'lang:auth_mail', 'trim|required|xss_clean|valid_email|callback_email_check');

            if ($this->dx_auth->captcha_registration) {
                $val->set_rules('captcha', 'lang:auth_confirmation_code', 'trim|xss_clean|required|callback_captcha_check');
            }

            // Run form validation and register user if it's pass the validation
            if ($val->run() and $this->dx_auth->register($val->set_value('username'), $val->set_value('password'), $val->set_value('email'))) {
                // Set success message accordingly
                if ($this->dx_auth->email_activation) {
                    $data['auth_message'] = $this->lang->line("auth_success_registration_email");
                } else {
                    $data['auth_message'] = $this->lang->line("auth_success_registration") . ' ' . anchor(site_url($this->dx_auth->login_uri), 'Login');
                }

                // Load registration success page
                load_last_view($this->dx_auth->register_success_view, $data);
            } else {
                // Is registration using captcha
                if ($this->dx_auth->captcha_registration) {
                    $this->dx_auth->captcha();
                }

                // Load registration page
                load_last_view($this->dx_auth->register_view);
            }
        } elseif (! $this->dx_auth->allow_registration) {
            $data['auth_message'] = $this->lang->line("auth_registration_disabled");
            load_last_view($this->dx_auth->register_disabled_view, $data);
        } else {
            $data['auth_message'] = $this->lang->line("auth_disconnect_before");
            load_last_view($this->dx_auth->logged_in_view, $data);
        }
    }

    function register_recaptcha() {
        if (! $this->dx_auth->is_logged_in() and $this->dx_auth->allow_registration) {
            $val = $this->form_validation;

            // Set form validation rules
            $val->set_rules('username', 'lang:auth_sername', 'trim|required|xss_clean|min_length[' . $this->min_username . ']|max_length[' . $this->max_username . ']|callback_username_check|alpha_dash');
            $val->set_rules('password', 'lang:auth_assword', 'trim|required|xss_clean|min_length[' . $this->min_password . ']|max_length[' . $this->max_password . ']|matches[confirm_password]');
            $val->set_rules('confirm_password', 'lang:auth_confirm_password', 'trim|required|xss_clean');
            $val->set_rules('email', 'lang:auth_email', 'trim|required|xss_clean|valid_email|callback_email_check');

            // Is registration using captcha
            if ($this->dx_auth->captcha_registration) {
                // Set recaptcha rules.
                // IMPORTANT: Do not change 'recaptcha_response_field' because it's used by reCAPTCHA API,
                // This is because the limitation of reCAPTCHA, not DX Auth library
                $val->set_rules('recaptcha_response_field', 'lang:auth_confirmation_code', 'trim|xss_clean|required|callback_recaptcha_check');
            }

            // Run form validation and register user if it's pass the validation
            if ($val->run() and $this->dx_auth->register($val->set_value('username'), $val->set_value('password'), $val->set_value('email'))) {
                // Set success message accordingly
                if ($this->dx_auth->email_activation) {
                    $data['auth_message'] = $this->lang->line("auth_success_registration_email");
                } else {
                    $data['auth_message'] = $this->lang->line("auth_success_registration") . ' ' . anchor(site_url($this->dx_auth->login_uri), 'Login');
                }

                // Load registration success page
                load_last_view($this->dx_auth->register_success_view, $data);
            } else {
                // Load registration page
                load_last_view('auth/register_recaptcha_form');
            }
        } elseif (! $this->dx_auth->allow_registration) {
            $data['auth_message'] = $this->lang->line("auth_registration_disabled");
            load_last_view($this->dx_auth->register_disabled_view, $data);
        } else {
            $data['auth_message'] = $this->lang->line("auth_disconnect_before");
            load_last_view($this->dx_auth->logged_in_view, $data);
        }
    }

    function activate() {
        // Get username and key
        $username = $this->uri->segment(3);
        $key = $this->uri->segment(4);

        // Activate user
        if ($this->dx_auth->activate($username, $key)) {
            $data['auth_message'] = $this->lang->line("auth_account_enabled") . ' ' . anchor(site_url($this->dx_auth->login_uri), 'Login');
            load_last_view($this->dx_auth->activate_success_view, $data);
        } else {
            $data['auth_message'] = $this->lang->line("auth_incorrect_activation");
            load_last_view($this->dx_auth->activate_failed_view, $data);
        }
    }
    function forgot_password() {
        $val = $this->form_validation;

        // Set form validation rules
        $val->set_rules('login', 'lang:auth_user_or_email', 'trim|required|xss_clean');

        // Validate rules and call forgot password function
        if ($val->run() and $this->dx_auth->forgot_password($val->set_value('login'))) {
            $data['auth_message'] = $this->lang->line("auth_forgot_pw_msg");
            load_last_view($this->dx_auth->forgot_password_success_view, $data);
        } else {
            load_last_view($this->dx_auth->forgot_password_view);
        }
    }
    function reset_password() {
        // Get username and key
        $username = $this->uri->segment(3);
        $key = $this->uri->segment(4);

        // Reset password
        if ($this->dx_auth->reset_password($username, $key)) {
            $data['auth_message'] = $this->lang->line("auth_reinit_password") . anchor(site_url($this->dx_auth->login_uri), 'Login');
            load_last_view($this->dx_auth->reset_password_success_view, $data);
        } else {
            $data['auth_message'] = $this->lang->line("auth_reinit_password_failed");
            load_last_view($this->dx_auth->reset_password_failed_view, $data);
        }
    }

    function change_password($duplicate = "") {
        // Check if user logged in or not
        if ($this->dx_auth->is_logged_in()) {
            $val = $this->form_validation;

            // Set form validation
            $val->set_rules('old_password', 'lang:auth_previous_password', 'trim|required|xss_clean|min_length[' . $this->min_password . ']|max_length[' . $this->max_password . ']');
            $val->set_rules('new_password', 'lang:auth_new_password', 'trim|required|xss_clean|min_length[' . $this->min_password . ']|max_length[' . $this->max_password . ']|matches[confirm_new_password]');
            $val->set_rules('confirm_new_password', 'lang:auth_confirm_password', 'trim|required|xss_clean');

            // Validate rules and change password
            if ($val->run() and $this->dx_auth->change_password($val->set_value('old_password'), $val->set_value('new_password'))) {
                $data['auth_message'] = $this->lang->line("auth_password_changed");
                load_last_view($this->dx_auth->change_password_success_view, $data);
            } else {
                $data = array(
                    'duplicate' => $duplicate
                );
                load_last_view($this->dx_auth->change_password_view, $data);
            }
        } else {
            // Redirect to login page
            $this->dx_auth->deny_access('login');
        }
    }

    function cancel_account() {
        // Check if user logged in or not
        if ($this->dx_auth->is_logged_in()) {
            $val = $this->form_validation;

            // Set form validation rules
            $val->set_rules('password', 'lang:auth_password', "trim|required|xss_clean");

            // Validate rules and change password
            if ($val->run() and $this->dx_auth->cancel_account($val->set_value('password'))) {
                // Redirect to homepage
                redirect('', 'location');
            } else {
                load_last_view($this->dx_auth->cancel_account_view);
            }
        } else {
            // Redirect to login page
            $this->dx_auth->deny_access('login');
        }
    }

    // Example how to get permissions you set permission in /backend/custom_permissions/
    function custom_permissions() {
        if ($this->dx_auth->is_logged_in()) {
            $txt = "";
            $txt .= $this->lang->line("auth_my_role") . ': ' . $this->dx_auth->get_role_name() . '<br/>';
            $txt .= $this->lang->line("auth_my_permissions") . ': <br/>';

            if ($this->dx_auth->get_permission_value('edit') != NULL and $this->dx_auth->get_permission_value('edit')) {
                $txt .= $this->lang->line("auth_edit_authorized");
            } else {
                $txt .= $this->lang->line("auth_edit_forbiden");
            }

            $txt .= '<br/>';

            if ($this->dx_auth->get_permission_value('delete') != NULL and $this->dx_auth->get_permission_value('delete')) {
                $txt .= $this->lang->line("auth_delete_authorized");
            } else {
                $txt .= $this->lang->line("auth_delete_forbiden");
            }

            $data = array();
            $data['title'] = $this->lang->line("auth_my_permissions");
            $data['text'] = $txt;
            load_last_view('message', $data);
        }
    }

    // URL deny
    function deny() {
        load_last_view('welcome/deny');
    }
}
