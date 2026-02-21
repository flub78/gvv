<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 074: Fix CA role legacy permissions for /comptes/ access
 *
 * The 'ca' role had a broad '/comptes/' URI permission granting access to all
 * /comptes/* paths (including /comptes/page and /comptes/balance). CA members
 * should only have access to the dashboard and tresorerie views, not the full
 * chart of accounts or balance sheet (those are bureau/tresorier only).
 *
 * This migration replaces '/comptes/' with '/comptes/dashboard/' and
 * '/comptes/tresorerie/' in the legacy DX_Auth permissions for the 'ca' role.
 */
class Migration_Fix_ca_comptes_permissions extends CI_Migration
{
    public function up()
    {
        // Get the ca role id from the legacy roles table
        $ca_role = $this->db->select('id')->from('roles')->where('name', 'ca')->get()->row();

        if (!$ca_role) {
            log_message('error', 'Migration 074: ca role not found in roles table');
            return;
        }

        $role_id = $ca_role->id;

        // Get current permissions
        $perm = $this->db->select('id, data')
            ->from('permissions')
            ->where('role_id', $role_id)
            ->get()
            ->row();

        if (!$perm) {
            log_message('error', 'Migration 074: permissions not found for ca role_id=' . $role_id);
            return;
        }

        $data = @unserialize($perm->data);

        if ($data === FALSE || !isset($data['uri']) || !is_array($data['uri'])) {
            log_message('error', 'Migration 074: could not unserialize permissions for ca role');
            return;
        }

        // Replace '/comptes/' with specific allowed paths
        $new_uris = array();
        $replaced = false;

        foreach ($data['uri'] as $uri) {
            if ($uri === '/comptes/') {
                if (!$replaced) {
                    $new_uris[] = '/comptes/dashboard/';
                    $new_uris[] = '/comptes/tresorerie/';
                    $replaced = true;
                }
                // Skip the old broad '/comptes/' entry
            } else {
                $new_uris[] = $uri;
            }
        }

        if (!$replaced) {
            log_message('warning', 'Migration 074: /comptes/ not found in ca permissions, nothing to change');
            return;
        }

        // Re-index array numerically
        $data['uri'] = array_values($new_uris);

        $new_serialized = serialize($data);

        $this->db->where('role_id', $role_id)
            ->update('permissions', array('data' => $new_serialized));

        log_message('info', 'Migration 074: Replaced /comptes/ with /comptes/dashboard/ and /comptes/tresorerie/ for ca role');
    }

    public function down()
    {
        // Get the ca role id
        $ca_role = $this->db->select('id')->from('roles')->where('name', 'ca')->get()->row();

        if (!$ca_role) {
            return;
        }

        $role_id = $ca_role->id;

        $perm = $this->db->select('id, data')
            ->from('permissions')
            ->where('role_id', $role_id)
            ->get()
            ->row();

        if (!$perm) {
            return;
        }

        $data = @unserialize($perm->data);

        if ($data === FALSE || !isset($data['uri'])) {
            return;
        }

        // Remove the specific paths and restore the broad '/comptes/'
        $new_uris = array();
        $dashboard_found = false;

        foreach ($data['uri'] as $uri) {
            if ($uri === '/comptes/dashboard/') {
                if (!$dashboard_found) {
                    $new_uris[] = '/comptes/';
                    $dashboard_found = true;
                }
            } elseif ($uri === '/comptes/tresorerie/') {
                // Skip, already restored by dashboard_found
            } else {
                $new_uris[] = $uri;
            }
        }

        $data['uri'] = array_values($new_uris);

        $new_serialized = serialize($data);

        $this->db->where('role_id', $role_id)
            ->update('permissions', array('data' => $new_serialized));

        log_message('info', 'Migration 074: Restored /comptes/ broad permission for ca role');
    }
}
