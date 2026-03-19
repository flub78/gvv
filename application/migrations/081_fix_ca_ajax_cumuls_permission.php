<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 081: Add /comptes/ajax_cumuls/ to CA role legacy permissions
 *
 * Migration 074 restricted the CA role's /comptes/ access to only
 * /comptes/dashboard/ and /comptes/tresorerie/, but forgot to include
 * /comptes/ajax_cumuls/ which is the AJAX endpoint called by the tresorerie
 * view to fetch chart data. Without it, the tresorerie page renders empty
 * for legacy CA users (DX Auth URI check blocks the AJAX call).
 */
class Migration_Fix_ca_ajax_cumuls_permission extends CI_Migration
{
    public function up()
    {
        $ca_role = $this->db->select('id')->from('roles')->where('name', 'ca')->get()->row();

        if (!$ca_role) {
            log_message('error', 'Migration 081: ca role not found');
            return;
        }

        $perm = $this->db->select('id, data')
            ->from('permissions')
            ->where('role_id', $ca_role->id)
            ->get()->row();

        if (!$perm) {
            log_message('error', 'Migration 081: permissions not found for ca role');
            return;
        }

        $data = @unserialize($perm->data);

        if ($data === FALSE || !isset($data['uri']) || !is_array($data['uri'])) {
            log_message('error', 'Migration 081: could not unserialize ca permissions');
            return;
        }

        if (in_array('/comptes/ajax_cumuls/', $data['uri'])) {
            log_message('info', 'Migration 081: /comptes/ajax_cumuls/ already present, nothing to do');
            return;
        }

        $data['uri'][] = '/comptes/ajax_cumuls/';
        $data['uri'] = array_values($data['uri']);

        $this->db->where('role_id', $ca_role->id)
            ->update('permissions', array('data' => serialize($data)));

        log_message('info', 'Migration 081: Added /comptes/ajax_cumuls/ to ca role permissions');
    }

    public function down()
    {
        $ca_role = $this->db->select('id')->from('roles')->where('name', 'ca')->get()->row();

        if (!$ca_role) {
            return;
        }

        $perm = $this->db->select('id, data')
            ->from('permissions')
            ->where('role_id', $ca_role->id)
            ->get()->row();

        if (!$perm) {
            return;
        }

        $data = @unserialize($perm->data);

        if ($data === FALSE || !isset($data['uri'])) {
            return;
        }

        $data['uri'] = array_values(array_filter($data['uri'], function ($uri) {
            return $uri !== '/comptes/ajax_cumuls/';
        }));

        $this->db->where('role_id', $ca_role->id)
            ->update('permissions', array('data' => serialize($data)));

        log_message('info', 'Migration 081: Removed /comptes/ajax_cumuls/ from ca role permissions');
    }
}
