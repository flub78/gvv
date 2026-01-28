<?php
/**
 * Formation Progression Library
 * 
 * Calcule la progression d'un élève dans son programme de formation
 * basé sur les évaluations des séances
 * 
 * @package libraries
 */

class Formation_progression {
    
    private $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('formation_inscription_model');
        $this->CI->load->model('formation_programme_model');
        $this->CI->load->model('formation_lecon_model');
        $this->CI->load->model('formation_sujet_model');
        $this->CI->load->model('formation_seance_model');
        $this->CI->load->model('formation_evaluation_model');
        $this->CI->load->model('membres_model');
    }
    
    /**
     * Calcule la progression complète d'une formation
     * 
     * @param int $inscription_id ID de la formation
     * @return array|false Structure avec stats, leçons et progression, ou false si erreur
     */
    public function calculer($inscription_id) {
        // Charger l'inscription
        $inscription = $this->CI->formation_inscription_model->get($inscription_id);
        if (!$inscription) {
            return false;
        }

        // Charger le programme et ses détails
        $programme = $this->CI->formation_programme_model->get($inscription['programme_id']);
        if (!$programme) {
            return false;
        }

        // Charger l'élève
        $pilote = $this->CI->membres_model->get_by_id('mlogin', $inscription['pilote_id']);
        
        // Charger toutes les leçons du programme
        $lecons = $this->CI->formation_lecon_model->get_by_programme($programme['id']);
        
        // Charger toutes les séances de cette formation
        $seances = $this->CI->formation_seance_model->get_by_inscription($inscription_id);
        
        // Initialiser les statistiques
        $stats = [
            'nb_seances' => count($seances),
            'heures_totales' => '00:00',
            'heures_totales_seconds' => 0,
            'atterrissages_totaux' => 0,
            'nb_sujets_total' => 0,
            'nb_sujets_non_abordes' => 0,
            'nb_sujets_abordes' => 0,
            'nb_sujets_a_revoir' => 0,
            'nb_sujets_acquis' => 0,
            'pourcentage_acquis' => 0,
            'date_premiere_seance' => null,
            'date_derniere_seance' => null
        ];
        
        // Calculer les totaux de séances
        foreach ($seances as $seance) {
            // Durée (format HH:MM:SS)
            if (!empty($seance['duree'])) {
                $parts = explode(':', $seance['duree']);
                $seconds = 0;
                if (count($parts) == 3) {
                    $seconds = $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
                } elseif (count($parts) == 2) {
                    $seconds = $parts[0] * 3600 + $parts[1] * 60;
                }
                $stats['heures_totales_seconds'] += $seconds;
            }
            
            // Atterrissages
            $stats['atterrissages_totaux'] += (int) $seance['nb_atterrissages'];
            
            // Dates
            if ($stats['date_premiere_seance'] === null || $seance['date_seance'] < $stats['date_premiere_seance']) {
                $stats['date_premiere_seance'] = $seance['date_seance'];
            }
            if ($stats['date_derniere_seance'] === null || $seance['date_seance'] > $stats['date_derniere_seance']) {
                $stats['date_derniere_seance'] = $seance['date_seance'];
            }
        }
        
        // Convertir heures totales en HH:MM
        $hours = floor($stats['heures_totales_seconds'] / 3600);
        $minutes = floor(($stats['heures_totales_seconds'] % 3600) / 60);
        $stats['heures_totales'] = sprintf('%02d:%02d', $hours, $minutes);
        
        // Charger toutes les évaluations de toutes les séances
        $evaluations_par_sujet = [];
        foreach ($seances as $seance) {
            $evals = $this->CI->formation_evaluation_model->get_by_seance($seance['id']);
            foreach ($evals as $eval) {
                if (!isset($evaluations_par_sujet[$eval['sujet_id']])) {
                    $evaluations_par_sujet[$eval['sujet_id']] = [];
                }
                $evaluations_par_sujet[$eval['sujet_id']][] = [
                    'niveau' => $eval['niveau'],
                    'commentaire' => $eval['commentaire'],
                    'date_seance' => $seance['date_seance'],
                    'seance_id' => $seance['id']
                ];
            }
        }
        
        // Trier les évaluations par date (plus récente en dernier)
        foreach ($evaluations_par_sujet as &$evals) {
            usort($evals, function($a, $b) {
                return strcmp($a['date_seance'], $b['date_seance']);
            });
        }
        unset($evals);
        
        // Construire l'arborescence leçons/sujets avec progression
        $lecons_progression = [];
        foreach ($lecons as $lecon) {
            $sujets = $this->CI->formation_sujet_model->get_by_lecon($lecon['id']);
            
            $sujets_progression = [];
            foreach ($sujets as $sujet) {
                $stats['nb_sujets_total']++;
                
                $historique = isset($evaluations_par_sujet[$sujet['id']]) ? 
                    $evaluations_par_sujet[$sujet['id']] : [];
                
                // Dernier niveau
                $dernier_niveau = '-';
                $date_derniere_eval = null;
                $nb_seances_sujet = count($historique);
                
                if (!empty($historique)) {
                    $derniere_eval = end($historique);
                    $dernier_niveau = $derniere_eval['niveau'];
                    $date_derniere_eval = $derniere_eval['date_seance'];
                }
                
                // Compter par niveau
                switch ($dernier_niveau) {
                    case '-':
                        $stats['nb_sujets_non_abordes']++;
                        break;
                    case 'A':
                        $stats['nb_sujets_abordes']++;
                        break;
                    case 'R':
                        $stats['nb_sujets_a_revoir']++;
                        break;
                    case 'Q':
                        $stats['nb_sujets_acquis']++;
                        break;
                }
                
                $sujets_progression[] = [
                    'id' => $sujet['id'],
                    'numero' => $sujet['numero'],
                    'titre' => $sujet['titre'],
                    'description' => $sujet['description'],
                    'objectifs' => $sujet['objectifs'],
                    'nb_seances' => $nb_seances_sujet,
                    'dernier_niveau' => $dernier_niveau,
                    'date_derniere_eval' => $date_derniere_eval,
                    'historique' => $historique
                ];
            }
            
            $lecons_progression[] = [
                'id' => $lecon['id'],
                'numero' => $lecon['numero'],
                'titre' => $lecon['titre'],
                'description' => $lecon['description'],
                'sujets' => $sujets_progression
            ];
        }
        
        // Calculer le pourcentage d'acquis
        if ($stats['nb_sujets_total'] > 0) {
            $stats['pourcentage_acquis'] = round(
                ($stats['nb_sujets_acquis'] / $stats['nb_sujets_total']) * 100, 
                1
            );
        }
        
        return [
            'inscription' => $inscription,
            'programme' => $programme,
            'pilote' => $pilote,
            'stats' => $stats,
            'lecons' => $lecons_progression
        ];
    }
    

    /**
     * Calcule le pourcentage de progression cumulatif jusqu'à une date limite
     *
     * @param int $inscription_id ID de la formation
     * @param string $date_limite Date limite (format Y-m-d)
     * @return array ['total_sujets' => int, 'sujets_acquis' => int, 'pourcentage' => float]
     */
    public function calculer_pourcentage_a_date($inscription_id, $date_limite) {
        // Charger l'inscription
        $inscription = $this->CI->formation_inscription_model->get($inscription_id);
        if (!$inscription) {
            return array('total_sujets' => 0, 'sujets_acquis' => 0, 'pourcentage' => 0);
        }

        // Compter le nombre total de sujets dans le programme
        $this->CI->db->select('COUNT(DISTINCT fs.id) as total')
            ->from('formation_lecons fl')
            ->join('formation_sujets fs', 'fl.id = fs.lecon_id', 'left')
            ->where('fl.programme_id', $inscription['programme_id']);
        $total_result = $this->CI->db->get()->row_array();
        $total_sujets = (int) ($total_result['total'] ?? 0);

        if ($total_sujets == 0) {
            return array('total_sujets' => 0, 'sujets_acquis' => 0, 'pourcentage' => 0);
        }

        // Pour chaque sujet, trouver le dernier niveau évalué avant date_limite
        // et compter ceux qui sont acquis (Q)
        $sql = "SELECT COUNT(DISTINCT fe.sujet_id) as acquis
                FROM formation_evaluations fe
                JOIN formation_seances fse ON fe.seance_id = fse.id
                WHERE fse.inscription_id = ?
                AND fse.date_seance <= ?
                AND fe.niveau = 'Q'
                AND fe.id = (
                    SELECT fe2.id FROM formation_evaluations fe2
                    JOIN formation_seances fs2 ON fe2.seance_id = fs2.id
                    WHERE fe2.sujet_id = fe.sujet_id
                    AND fs2.inscription_id = ?
                    AND fs2.date_seance <= ?
                    ORDER BY fs2.date_seance DESC, fe2.id DESC
                    LIMIT 1
                )";

        $query = $this->CI->db->query($sql, array(
            $inscription_id, $date_limite, $inscription_id, $date_limite
        ));
        $acquis_result = $query->row_array();
        $sujets_acquis = (int) ($acquis_result['acquis'] ?? 0);

        $pourcentage = round(($sujets_acquis / $total_sujets) * 100, 1);

        return array(
            'total_sujets' => $total_sujets,
            'sujets_acquis' => $sujets_acquis,
            'pourcentage' => $pourcentage
        );
    }

    /**
     * Détermine la classe CSS pour la barre de progression
     * 
     * @param float $pourcentage Pourcentage d'acquis
     * @return string Classe CSS (bg-danger, bg-warning, bg-info, bg-success)
     */
    public function get_progress_bar_class($pourcentage) {
        if ($pourcentage < 25) {
            return 'bg-danger';  // Rouge
        } elseif ($pourcentage < 50) {
            return 'bg-warning'; // Orange
        } elseif ($pourcentage < 75) {
            return 'bg-info';    // Bleu
        } else {
            return 'bg-success'; // Vert
        }
    }
    
    /**
     * Détermine la classe CSS pour le badge de niveau
     * 
     * @param string $niveau Niveau (-,  A, R, Q)
     * @return string Classe CSS
     */
    public function get_niveau_badge_class($niveau) {
        switch ($niveau) {
            case '-':
                return 'bg-secondary';  // Gris
            case 'A':
                return 'bg-info';       // Bleu
            case 'R':
                return 'bg-warning';    // Orange
            case 'Q':
                return 'bg-success';    // Vert
            default:
                return 'bg-secondary';
        }
    }
    
    /**
     * Obtient le texte du niveau
     * 
     * @param string $niveau Niveau (-,  A, R, Q)
     * @return string Texte traduit
     */
    public function get_niveau_label($niveau) {
        $this->CI->lang->load('formation');
        
        switch ($niveau) {
            case '-':
                return $this->CI->lang->line('formation_evaluation_niveau_non_aborde');
            case 'A':
                return $this->CI->lang->line('formation_evaluation_niveau_aborde');
            case 'R':
                return $this->CI->lang->line('formation_evaluation_niveau_a_revoir');
            case 'Q':
                return $this->CI->lang->line('formation_evaluation_niveau_acquis');
            default:
                return '';
        }
    }
}
