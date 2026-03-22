<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 090: Add configuration parameter defaut.aerodrome
 *
 * Adds a configuration key 'defaut.aerodrome' to store the default OACI code
 * used to pre-fill aerodrome fields when creating airplane, ULM, and discovery
 * flights.
 */
class Migration_Defaut_aerodrome extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "INSERT INTO `configuration` (`cle`, `valeur`, `lang`, `categorie`, `description`)
             VALUES ('defaut.aerodrome', NULL, NULL, 'configuration', 'Code OACI de l\\'aérodrome par défaut (vols avion, ULM et vols découverte avion/ULM)')
             ON DUPLICATE KEY UPDATE `cle` = `cle`"
        );

        log_message('info', 'Migration 090: configuration parameter defaut.aerodrome added');
    }

    public function down()
    {
        $this->db->query("DELETE FROM `configuration` WHERE `cle` = 'defaut.aerodrome'");

        log_message('info', 'Migration 090: configuration parameter defaut.aerodrome removed');
    }
}
