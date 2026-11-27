<?php

use PHPUnit\Framework\TestCase;

/**
 * @feature Authentification et autorisations
 *
 * Les suppressions des pages de gestion n'acceptent que POST (jeton CSRF) :
 * un lien piégé ou une image sur un autre site ne peut plus rien supprimer.
 *
 * Requêtes HTTP réelles sur gvv.net (GvvTestHttp). Le terrain de test est
 * créé et supprimé par le test.
 */
class SuppressionPostObligatoireTest extends TestCase
{
    const OACI = '00PHPU';

    private $CI;
    private $cookie;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->db->where('oaci', self::OACI)->delete('terrains');
        $this->CI->db->insert('terrains', array(
            'oaci' => self::OACI,
            'nom' => 'Terrain test PHPUnit',
            'comment' => 'Créé par SuppressionPostObligatoireTest',
        ));
        $this->cookie = GvvTestHttp::login();
        $this->assertNotNull($this->cookie, 'La connexion admin doit renvoyer un cookie de session.');
    }

    protected function tearDown(): void
    {
        $this->CI->db->where('oaci', self::OACI)->delete('terrains');
    }

    private function terrain_existe()
    {
        return $this->CI->db->where('oaci', self::OACI)->count_all_results('terrains') > 0;
    }

    /**
     * Scénario: Une suppression ouverte par un simple lien est refusée
     *   Étant donné un administrateur connecté
     *   Et un terrain enregistré
     *   Quand l'adresse de suppression du terrain est ouverte directement
     *   Alors la demande est refusée avec un message qui l'explique
     *   Et le terrain est toujours enregistré
     */
    public function testDeleteByGetIsRefusedAndKeepsData()
    {
        // Requête sans jeton, comme un lien piégé
        $response = GvvTestHttp::get(GvvTestHttp::base_url() . 'terrains/delete/' . self::OACI,
            $this->cookie, array('csrf' => FALSE));

        $this->assertSame(405, $response['status']);
        $this->assertStringContainsString("Aucune donnée n'a été modifiée", $response['body']);
        $this->assertTrue($this->terrain_existe());
    }

    /**
     * Scénario: Une suppression envoyée par l'application est effectuée
     *   Étant donné un administrateur connecté
     *   Et un terrain enregistré
     *   Quand il demande la suppression du terrain depuis GVV
     *   Alors le terrain est supprimé
     */
    public function testDeleteByPostWithTokenDeletes()
    {
        $response = GvvTestHttp::post(GvvTestHttp::base_url() . 'terrains/delete/' . self::OACI,
            array(), $this->cookie);

        $this->assertNotSame(405, $response['status']);
        $this->assertNotSame(403, $response['status']);
        $this->assertFalse($this->terrain_existe());
    }
}
