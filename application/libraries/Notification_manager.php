<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Envoi des notifications d'alarmes
 *
 * Exécuté chaque jour par alarm_scheduler. Pour chaque membre actif ayant
 * choisi au moins un canal ou le rappel SMS :
 * - retient ses occurrences (AlarmItem) propres et celles de ses cases
 *   effectives (alarme × section ou Club), sans doublon ;
 * - fréquence immediate : notifie les occurrences pas encore notifiées dans
 *   leur état courant ;
 * - fréquence weekly (le lundi) ou monthly (le premier lundi du mois) :
 *   envoie la synthèse de toutes les occurrences actives et à venir ;
 * - canaux choisis par le membre : email (liste des alarmes) et message du
 *   jour individuel ;
 * - rappel SMS le jour de synthèse (lundi ; premier lundi du mois en
 *   mensuel) : message court et lien vers « Mes alarmes » seulement.
 *
 * notification_history rend l'exécution idempotente : une seconde exécution
 * le même jour n'envoie rien de plus.
 *
 * @package libraries
 * @see doc/design_notes/gestion_alarmes_design.md
 */
class Notification_manager {

    const SOURCE_TYPE = 'alarm';

    /** @var CI_Controller */
    private $CI;

    /** @var string Date de référence (Y-m-d), modifiable pour les tests */
    private $today;

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model('alarm_definitions_model');
        $this->CI->load->model('alarm_subscriptions_model');
        $this->CI->load->model('alarm_member_settings_model');
        $this->CI->load->model('notification_history_model');
        $this->CI->load->model('motd_model');
        $this->CI->load->library('Alarm_aggregator');
        $this->CI->load->helper('email');
        $this->CI->load->config('program');
        $this->CI->lang->load('alarmes');
        $this->today = date('Y-m-d');
    }

    /**
     * Fixe la date de référence (tests)
     *
     * @param string $date Y-m-d
     */
    public function set_today($date) {
        $this->today = $date;
    }

    /**
     * Traitement quotidien
     *
     * @param string $source cron | public_url
     * @return array Nombre d'envois par canal : email, motd, sms, failed
     */
    public function run($source = 'cron') {
        $stats = array('email' => 0, 'motd' => 0, 'sms' => 0, 'failed' => 0);

        $definitions = array();
        foreach ($this->CI->alarm_definitions_model->get_all(true) as $def) {
            $definitions[$def['id']] = $def;
        }
        $items_by_def = array();
        foreach ($this->CI->alarm_aggregator->collect(array_values($definitions)) as $item) {
            $items_by_def[$item['definition_id']][] = $item;
        }

        $settings = $this->CI->alarm_member_settings_model->get_all_by_login();
        foreach ($this->members_to_notify() as $login => $cells) {
            if (empty($settings[$login])) {
                continue; // aucun canal choisi
            }
            $occurrences = $this->occurrences_for($login, $cells, $definitions, $items_by_def);
            $this->notify_member($login, $settings[$login], $occurrences, $stats);
        }

        gvv_info("ALARM scheduler source=$source email={$stats['email']} motd={$stats['motd']} "
            . "sms={$stats['sms']} failed={$stats['failed']}");
        return $stats;
    }

    /**
     * Membres à traiter et leurs cases effectives (restreint dans les tests) :
     * membres actifs ayant enregistré des réglages
     *
     * @return array login => cases effectives (voir Alarm_subscriptions_model::effective_all())
     */
    protected function members_to_notify() {
        $settings = $this->CI->alarm_member_settings_model->get_all_by_login();
        $effective = $this->CI->alarm_subscriptions_model->effective_all();
        $members = array();
        foreach ($this->CI->alarm_subscriptions_model->active_logins() as $login) {
            if (isset($settings[$login])) {
                $members[$login] = isset($effective[$login]) ? $effective[$login] : array();
            }
        }
        return $members;
    }

    /**
     * Occurrences d'un membre, sans doublon : ses occurrences propres (il en
     * est le pilote), pour toutes les alarmes, et celles de ses cases effectives
     *
     * @param string $login
     * @param array $cells definition_id => [section_id|CLUB => abonnement] cases effectives
     * @param array $definitions definition_id => ligne du catalogue
     * @param array $items_by_def definition_id => AlarmItem[]
     * @return array key => AlarmItem
     */
    public function occurrences_for($login, $cells, $definitions, $items_by_def) {
        $occurrences = array();
        foreach ($items_by_def as $def_id => $items) {
            if (empty($definitions[$def_id])) {
                continue;
            }
            $def_cells = isset($cells[$def_id]) ? $cells[$def_id] : array();
            foreach ($items as $item) {
                if (isset($occurrences[$item['key']])) {
                    continue;
                }
                if ($item['pilot_login'] === $login) {
                    if (!empty($item['link_own'])) {
                        $item['link'] = $item['link_own'];
                    }
                    $item['own'] = true;
                    $occurrences[$item['key']] = $item;
                } elseif ($def_cells && Alarm_subscriptions_model::item_in_cells($item, $def_cells)) {
                    $item['own'] = false;
                    $occurrences[$item['key']] = $item;
                }
            }
        }
        uasort($occurrences, function ($a, $b) {
            if ($a['state'] !== $b['state']) {
                return ($a['state'] === 'active') ? -1 : 1;
            }
            return strcmp((string) $a['due_date'], (string) $b['due_date']);
        });
        return $occurrences;
    }

    /**
     * Occurrences actives et à venir d'un membre (page Mes alarmes, tableau de bord)
     *
     * @param string $login
     * @return array key => AlarmItem
     */
    public function member_occurrences($login) {
        $definitions = array();
        foreach ($this->CI->alarm_definitions_model->get_all(true) as $def) {
            $definitions[$def['id']] = $def;
        }
        $items_by_def = array();
        foreach ($this->CI->alarm_aggregator->collect(array_values($definitions)) as $item) {
            $items_by_def[$item['definition_id']][] = $item;
        }
        $cells = $this->CI->alarm_subscriptions_model->effective_for($login);
        return $this->occurrences_for($login, $cells, $definitions, $items_by_def);
    }

    /**
     * Nombre d'occurrences actives d'un membre (carte du tableau de bord)
     *
     * @param string $login
     * @return int
     */
    public function active_count_for($login) {
        $count = 0;
        foreach ($this->member_occurrences($login) as $item) {
            if ($item['state'] === 'active') {
                $count++;
            }
        }
        return $count;
    }

    // ------------------------------------------------------------------
    // Calendrier
    // ------------------------------------------------------------------

    /**
     * Jour de synthèse et clé de période pour une fréquence, ou null
     *
     * @param string $frequency
     * @return string|null Ex. 2026-W43, 2026-11
     */
    public function digest_period($frequency) {
        $time = strtotime($this->today);
        if (date('N', $time) != 1) {
            return null; // pas un lundi
        }
        if ($frequency === Alarm_member_settings_model::FREQ_MONTHLY) {
            return ((int) date('j', $time) <= 7) ? date('Y-m', $time) : null;
        }
        return date('o-\WW', $time);
    }

    // ------------------------------------------------------------------
    // Envoi
    // ------------------------------------------------------------------

    private function notify_member($login, $settings, $occurrences, &$stats) {
        $member = $this->CI->db->select('mlogin, mnom, mprenom, memail, mtelm')
            ->where('mlogin', $login)->get('membres')->row_array();
        if (!$member) {
            return;
        }
        $channels = array();
        if (!empty($settings['channel_email'])) {
            $channels[] = 'email';
        }
        if (!empty($settings['channel_motd'])) {
            $channels[] = 'motd';
        }

        if ($settings['frequency'] === Alarm_member_settings_model::FREQ_IMMEDIATE) {
            $this->notify_new($member, $channels, $occurrences, $stats);
        } else {
            $period = $this->digest_period($settings['frequency']);
            if ($period && $occurrences) {
                foreach ($channels as $channel) {
                    if (!$this->CI->notification_history_model->digest_sent($login, $channel, $period)) {
                        $this->send($member, $channel, 'digest', $occurrences, $period, $settings['frequency'], $stats);
                    }
                }
            }
        }

        // Rappel SMS : lundi pour immediate et weekly, premier lundi du mois pour monthly
        if (!empty($settings['sms']) && $occurrences && $this->sms_enabled()) {
            $sms_frequency = ($settings['frequency'] === Alarm_member_settings_model::FREQ_MONTHLY)
                ? Alarm_member_settings_model::FREQ_MONTHLY : Alarm_member_settings_model::FREQ_WEEKLY;
            $period = $this->digest_period($sms_frequency);
            if ($period && !empty($member['mtelm'])
                && !$this->CI->notification_history_model->digest_sent($login, 'sms', $period)) {
                $this->send_sms($member, count($occurrences), $period, $stats);
            }
        }
    }

    /** Fréquence immédiate : occurrences pas encore notifiées dans leur état courant */
    private function notify_new($member, $channels, $occurrences, &$stats) {
        if (!$channels || !$occurrences) {
            return;
        }
        $notified = $this->CI->notification_history_model->notified_occurrences($member['mlogin']);
        $new = array();
        foreach ($occurrences as $key => $item) {
            if (empty($notified[$key][$item['state']])) {
                $new[$key] = $item;
            }
        }
        if (!$new) {
            return;
        }
        foreach ($channels as $channel) {
            $this->send($member, $channel, 'trigger', $new, date('Y-m-d', strtotime($this->today)),
                Alarm_member_settings_model::FREQ_IMMEDIATE, $stats);
        }
    }

    /**
     * Envoie une liste d'occurrences sur un canal et l'historise
     */
    private function send($member, $channel, $kind, $occurrences, $period, $frequency, &$stats) {
        $title = sprintf($this->CI->lang->line('alarm_notification_title'), count($occurrences));
        $body = $this->compose_html($member, $occurrences, $kind);
        $error = null;

        if ($channel === 'email') {
            $ok = !empty($member['memail']) && $this->deliver_email($member['memail'], $title, $body);
            if (empty($member['memail'])) {
                $error = 'no email';
            }
        } else {
            $ok = (bool) $this->CI->motd_model->generate_system_message(array(
                'title' => $title,
                'content' => $body,
                'level' => 'alerte',
                'end_date' => $this->motd_end_date($frequency),
                'target_type' => 'user',
                'target_user_login' => $member['mlogin'],
                'source_type' => self::SOURCE_TYPE,
                'source_ref' => $period,
                'created_by' => 'alarm_scheduler',
                'updated_by' => 'alarm_scheduler',
            ));
        }

        if ($kind === 'digest') {
            $this->record($member['mlogin'], $channel, $kind, null, $period, $ok, $error);
        } else {
            foreach ($occurrences as $item) {
                $this->record($member['mlogin'], $channel, $kind, $item, $period, $ok, $error);
            }
        }
        $stats[$ok ? $channel : 'failed']++;
    }

    private function send_sms($member, $count, $period, &$stats) {
        $message = sprintf($this->CI->lang->line('alarm_sms_text'), $count) . ' ' . $this->url('mes_alarmes');
        $result = $this->deliver_sms($member['mtelm'], $message);
        $this->record($member['mlogin'], 'sms', 'digest', null, $period, $result['ok'], $result['error']);
        $stats[$result['ok'] ? 'sms' : 'failed']++;
    }

    private function record($login, $channel, $kind, $item, $period, $ok, $error) {
        $this->CI->notification_history_model->record(array(
            'recipient_login' => $login,
            'alarm_definition_id' => $item ? $item['definition_id'] : null,
            'occurrence_key' => $item ? $item['key'] : null,
            'occurrence_state' => $item ? $item['state'] : null,
            'channel' => $channel,
            'kind' => $kind,
            'period_key' => $period,
            'status' => $ok ? 'sent' : 'failed',
            'error' => $error,
        ));
        if (!$ok) {
            gvv_error("ALARM envoi $channel/$kind à $login en échec" . ($error ? " : $error" : ''));
        }
    }

    /**
     * Envoi effectif d'un SMS (remplacé dans les tests)
     *
     * @return array ['ok' => bool, 'error' => string|null]
     */
    protected function deliver_sms($phone, $message) {
        $this->CI->load->library('Brevo_sms_adapter');
        return $this->CI->brevo_sms_adapter->send(test_intercept_phone($phone), $message);
    }

    /**
     * Envoi effectif d'un email (remplacé dans les tests)
     *
     * @return bool
     */
    protected function deliver_email($email, $subject, $body) {
        try {
            $this->CI->load->library('email');
            $this->CI->load->config('club', true);
            $from_email = $this->CI->config->item('email_club') ?: $this->CI->config->item('smtp_user');
            $from_name = $this->CI->config->item('nom_club') ?: 'GVV';
            $to = test_intercept_email($email, $subject);

            $this->CI->email->clear(true);
            $this->CI->email->set_mailtype('html');
            $this->CI->email->from($from_email, $from_name);
            $this->CI->email->to($to);
            $this->CI->email->subject($subject);
            $this->CI->email->message($body);
            return (bool) @$this->CI->email->send();
        } catch (Exception $e) {
            gvv_error('ALARM email exception: ' . $e->getMessage());
            return false;
        }
    }

    // ------------------------------------------------------------------
    // Composition
    // ------------------------------------------------------------------

    /**
     * Corps HTML (email et message du jour) : liste des alarmes avec liens
     *
     * @param array $member
     * @param array $occurrences
     * @param string $kind trigger | digest
     * @return string
     */
    public function compose_html($member, $occurrences, $kind) {
        $lang = $this->CI->lang;
        $intro = ($kind === 'digest') ? $lang->line('alarm_notification_digest_intro')
                                      : $lang->line('alarm_notification_trigger_intro');
        $rows = '';
        foreach ($occurrences as $item) {
            $state = $lang->line('alarm_state_' . $item['state']);
            $due = $item['due_date'] ? date('d/m/Y', strtotime($item['due_date'])) : '';
            $rows .= '<tr>'
                . '<td style="padding:4px 8px">' . htmlspecialchars($item['label']) . '</td>'
                . '<td style="padding:4px 8px">' . htmlspecialchars($item['detail']) . '</td>'
                . '<td style="padding:4px 8px">' . htmlspecialchars($state) . '</td>'
                . '<td style="padding:4px 8px">' . $due . '</td>'
                . '<td style="padding:4px 8px"><a href="' . htmlspecialchars($this->url($item['link'])) . '">'
                . htmlspecialchars($lang->line('alarm_notification_open')) . '</a></td>'
                . '</tr>';
        }
        return '<p>' . htmlspecialchars(sprintf($lang->line('alarm_notification_hello'), trim($member['mprenom']))) . '</p>'
            . '<p>' . htmlspecialchars($intro) . '</p>'
            . '<table style="border-collapse:collapse" border="1">'
            . '<tr><th>' . $lang->line('alarm_col_alarm') . '</th><th>' . $lang->line('alarm_col_concerns')
            . '</th><th>' . $lang->line('alarm_col_state') . '</th><th>' . $lang->line('alarm_col_due')
            . '</th><th></th></tr>' . $rows . '</table>'
            . '<p><a href="' . htmlspecialchars($this->url('mes_alarmes')) . '">' . $lang->line('alarm_notification_all') . '</a> — '
            . '<a href="' . htmlspecialchars($this->url('mes_alarmes')) . '#configuration">' . $lang->line('alarm_notification_settings') . '</a></p>';
    }

    private function url($path) {
        return rtrim($this->CI->config->item('base_url'), '/') . '/' . ltrim($path, '/');
    }

    private function motd_end_date($frequency) {
        $days = ($frequency === Alarm_member_settings_model::FREQ_MONTHLY) ? 35 : 7;
        return date('Y-m-d 23:59:59', strtotime($this->today . " +$days days"));
    }

    /** Interrupteur global : alarm_sms_enabled = TRUE dans program.php */
    private function sms_enabled() {
        return $this->CI->config->item('alarm_sms_enabled') === TRUE;
    }
}
