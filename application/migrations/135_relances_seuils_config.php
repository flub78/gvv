<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 135: Insert relances threshold configuration keys.
 *
 * Adds relances.seuil_alarme and relances.seuil_critique into the
 * configuration table so the Relances controller can read them.
 */
class Migration_Relances_seuils_config extends CI_Migration
{
    public function up()
    {
        $rows = array(
            array(
                'cle'         => 'relances.seuil_alarme',
                'valeur'      => '300',
                'description' => 'Seuil alarme (jaune) pour la page Relances en euros',
                'categorie'   => 'relances',
            ),
            array(
                'cle'         => 'relances.seuil_critique',
                'valeur'      => '500',
                'description' => 'Seuil critique (rouge) pour la page Relances en euros',
                'categorie'   => 'relances',
            ),
        );

        foreach ($rows as $row) {
            $exists = $this->db->where('cle', $row['cle'])->count_all_results('configuration') > 0;
            if (!$exists) {
                $this->db->insert('configuration', $row);
            }
        }
    }

    public function down()
    {
        $this->db->where_in('cle', array('relances.seuil_alarme', 'relances.seuil_critique'))
                 ->delete('configuration');
    }
}
