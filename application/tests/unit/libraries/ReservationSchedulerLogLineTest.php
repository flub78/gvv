<?php

use PHPUnit\Framework\TestCase;

/**
 * @feature Réservations
 *
 * Compte rendu de la tâche planifiée des rappels de réservation et des alarmes.
 */
class ReservationSchedulerLogLineTest extends TestCase
{
    protected function setUp(): void
    {
        require_once APPPATH . 'controllers/reservation_scheduler.php';
    }

    /**
     * Scénario: Chaque passage de la tâche planifiée est daté dans son journal
     *   Étant donné la tâche planifiée qui envoie les rappels de réservation et les alarmes
     *   Quand elle rend compte d'un passage
     *   Alors chaque ligne du compte rendu commence par la date et l'heure du passage
     *   Et se termine par un retour à la ligne
     */
    public function testLogLine_IsTimestamped()
    {
        $time = (new DateTime('2026-11-03 14:05:09', new DateTimeZone('Europe/Paris')))->getTimestamp();
        $this->assertSame("2026-11-03 14:05:09 - reservation_scheduler cron: sent=2\n",
            Reservation_scheduler::log_line('reservation_scheduler cron: sent=2', $time));
    }

    /**
     * Scénario: L'heure du journal est l'heure de Paris, quel que soit le réglage du serveur
     *   Étant donné un serveur dont l'heure système est réglée en temps universel
     *   Quand la tâche planifiée rend compte d'un passage
     *   Alors l'heure inscrite est l'heure de Paris, comme dans les autres journaux du club
     */
    public function testLogLine_UsesParisTimeWhateverTheServerTimezone()
    {
        $previous = date_default_timezone_get();
        date_default_timezone_set('UTC');
        try {
            $time = gmmktime(13, 5, 9, 11, 3, 2026);   // 14:05:09 à Paris (heure d'hiver)
            $this->assertSame("2026-11-03 14:05:09 - alarms: skipped\n",
                Reservation_scheduler::log_line('alarms: skipped', $time));
        } finally {
            date_default_timezone_set($previous);
        }
    }

    /**
     * Scénario: Sans date fournie, la ligne porte l'heure courante
     *   Étant donné la tâche planifiée qui rend compte d'un passage
     *   Quand aucune date particulière n'est imposée
     *   Alors la ligne commence par la date du jour à Paris
     */
    public function testLogLine_DefaultsToNow()
    {
        $today = (new DateTime('now', new DateTimeZone('Europe/Paris')))->format('Y-m-d');
        $this->assertStringStartsWith($today . ' ', Reservation_scheduler::log_line('alarms: skipped'));
    }
}
