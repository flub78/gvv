<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Relances_model — Données pour la page de suivi des comptes débiteurs.
 *
 * Calcule pour chaque membre la dette totale et par section à la date
 * courante, à 6 mois et à 1 an.
 */
class Relances_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retourne la liste des membres débiteurs triés par dette totale décroissante.
     *
     * Chaque ligne contient :
     *   mlogin, mnom, mprenom, memail, total, total_6m, total_1an
     *   et une clé 'par_section' : tableau [ section_id => [ 'nom', 'acronyme', 'solde' ] ]
     *
     * @param string|null $date_ref  Date de référence JJ/MM/AAAA (null = aujourd'hui)
     * @return array
     */
    public function get_debiteurs($date_ref = null)
    {
        if ($date_ref === null) {
            $date_ref = date('Y-m-d');
        } else {
            $date_ref = date_ht2db($date_ref);
        }

        $date_6m  = date('Y-m-d', strtotime($date_ref . ' -6 months'));
        $date_1an = date('Y-m-d', strtotime($date_ref . ' -12 months'));

        // Fetch all sections (excluding cross-section id=0)
        $sections = $this->db->select('id, nom, acronyme')
            ->from('sections')
            ->where('id !=', 0)
            ->order_by('ordre_affichage, nom')
            ->get()
            ->result_array();

        // Build per-section conditional sums for current, 6m, 1y
        $section_sums     = '';
        $section_sums_6m  = '';
        $section_sums_1an = '';
        foreach ($sections as $s) {
            $sid = (int)$s['id'];
            $alias     = 's' . $sid;
            $alias_6m  = 's' . $sid . '_6m';
            $alias_1an = 's' . $sid . '_1an';

            $section_sums .= ",
  SUM(CASE WHEN c.club = $sid THEN
    (CASE WHEN e.compte1 = c.id AND e.date_op <= '$date_ref' THEN -e.montant
          WHEN e.compte2 = c.id AND e.date_op <= '$date_ref' THEN  e.montant
          ELSE 0 END)
  ELSE 0 END) AS `$alias`";

            $section_sums_6m .= ",
  SUM(CASE WHEN c.club = $sid THEN
    (CASE WHEN e.compte1 = c.id AND e.date_op <= '$date_6m' THEN -e.montant
          WHEN e.compte2 = c.id AND e.date_op <= '$date_6m' THEN  e.montant
          ELSE 0 END)
  ELSE 0 END) AS `${alias_6m}`";

            $section_sums_1an .= ",
  SUM(CASE WHEN c.club = $sid THEN
    (CASE WHEN e.compte1 = c.id AND e.date_op <= '$date_1an' THEN -e.montant
          WHEN e.compte2 = c.id AND e.date_op <= '$date_1an' THEN  e.montant
          ELSE 0 END)
  ELSE 0 END) AS `${alias_1an}`";
        }

        $sql = "
SELECT
  c.pilote                                                           AS mlogin,
  m.mnom,
  m.mprenom,
  m.memail,
  SUM(CASE WHEN e.compte1 = c.id AND e.date_op <= '$date_ref' THEN -e.montant
           WHEN e.compte2 = c.id AND e.date_op <= '$date_ref' THEN  e.montant
           ELSE 0 END)                                               AS total,
  SUM(CASE WHEN e.compte1 = c.id AND e.date_op <= '$date_6m'  THEN -e.montant
           WHEN e.compte2 = c.id AND e.date_op <= '$date_6m'  THEN  e.montant
           ELSE 0 END)                                               AS total_6m,
  SUM(CASE WHEN e.compte1 = c.id AND e.date_op <= '$date_1an' THEN -e.montant
           WHEN e.compte2 = c.id AND e.date_op <= '$date_1an' THEN  e.montant
           ELSE 0 END)                                               AS total_1an
  $section_sums
  $section_sums_6m
  $section_sums_1an
FROM comptes c
JOIN membres m ON m.mlogin = c.pilote
LEFT JOIN ecritures e ON (e.compte1 = c.id OR e.compte2 = c.id)
WHERE c.codec = '411'
  AND c.actif  = 1
  AND c.masked = 0
GROUP BY c.pilote, m.mnom, m.mprenom, m.memail
HAVING total < 0
ORDER BY total ASC
";

        $result = $this->db->query($sql);
        if (!$result) {
            return array('sections' => $sections, 'rows' => array());
        }

        $rows = array();
        foreach ($result->result_array() as $row) {
            $par_section = array();
            foreach ($sections as $s) {
                $sid   = (int)$s['id'];
                $alias = 's' . $sid;
                $par_section[$sid] = array(
                    'nom'     => $s['nom'],
                    'acronyme'=> $s['acronyme'],
                    'solde'   => (float)($row[$alias] ?? 0),
                    'solde_6m'=> (float)($row['s' . $sid . '_6m'] ?? 0),
                    'solde_1an'=> (float)($row['s' . $sid . '_1an'] ?? 0),
                );
            }
            $rows[] = array(
                'mlogin'   => $row['mlogin'],
                'mnom'     => $row['mnom'],
                'mprenom'  => $row['mprenom'],
                'memail'   => $row['memail'],
                'total'    => (float)$row['total'],
                'total_6m' => (float)$row['total_6m'],
                'total_1an'=> (float)$row['total_1an'],
                'par_section' => $par_section,
            );
        }

        return array('sections' => $sections, 'rows' => $rows);
    }
}
