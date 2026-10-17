<?php

use PHPUnit\Framework\TestCase;

/**
 * Login_Attempts::check_attempts() ne compte que les échecs de connexion
 * récents (DX_login_attempts_window) : une adresse IP bloquée par le CAPTCHA
 * est libérée automatiquement une fois la fenêtre écoulée.
 *
 * @see application/models/dx_auth/login_attempts.php
 */
class LoginAttemptsWindowTest extends TestCase
{
    /** Adresse réservée à la documentation (RFC 5737), jamais utilisée réellement */
    const TEST_IP = '192.0.2.198';

    /** @var CI_Controller */
    private $CI;

    /** @var Login_Attempts */
    private $model;

    private $saved_window;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();

        $this->CI->config->load('dx_auth');
        $this->saved_window = $this->CI->config->item('DX_login_attempts_window');
        $this->assertEquals(1800, $this->saved_window, 'Fenêtre configurée dans dx_auth.php');

        require_once APPPATH . 'models/dx_auth/login_attempts.php';
        $this->model = new Login_Attempts();

        $this->CI->db->delete('login_attempts', array('ip_address' => self::TEST_IP));
    }

    protected function tearDown(): void
    {
        $this->CI->db->delete('login_attempts', array('ip_address' => self::TEST_IP));
        $this->CI->config->set_item('DX_login_attempts_window', $this->saved_window);
    }

    private function addAttempt($seconds_ago)
    {
        $this->CI->db->query(
            "INSERT INTO login_attempts (ip_address, time) VALUES (?, DATE_SUB(NOW(), INTERVAL ? SECOND))",
            array(self::TEST_IP, (int) $seconds_ago)
        );
    }

    public function test_seuls_les_echecs_recents_sont_comptes(): void
    {
        $this->addAttempt(60);
        $this->addAttempt(600);
        $this->addAttempt(3600);   // hors fenêtre
        $this->addAttempt(86400);  // hors fenêtre

        $this->assertEquals(2, $this->model->check_attempts(self::TEST_IP)->num_rows());
    }

    public function test_fenetre_nulle_compte_tous_les_echecs(): void
    {
        $this->CI->config->set_item('DX_login_attempts_window', 0);
        $this->addAttempt(60);
        $this->addAttempt(86400);

        $this->assertEquals(2, $this->model->check_attempts(self::TEST_IP)->num_rows());
    }

    public function test_fenetre_par_defaut_si_non_configuree(): void
    {
        // Installations dont dx_auth.php ne définit pas encore le réglage
        $this->CI->config->set_item('DX_login_attempts_window', NULL);
        $this->addAttempt(60);
        $this->addAttempt(86400);

        $this->assertEquals(1, $this->model->check_attempts(self::TEST_IP)->num_rows());
    }

    public function test_clear_attempts_vide_le_compteur(): void
    {
        $this->addAttempt(60);
        $this->model->increase_attempt(self::TEST_IP);
        $this->model->clear_attempts(self::TEST_IP);

        $this->assertEquals(0, $this->model->check_attempts(self::TEST_IP)->num_rows());
    }
}
