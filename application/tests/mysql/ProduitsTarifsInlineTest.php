<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests HTTP du panneau tarifs intégré à produits/create et produits/edit
 * (Produits::formValidation()/post_create()/post_update()/_sync_tarifs()).
 *
 * Suit le pattern de application/tests/mysql/GvvControllerCreatePrefillTest.php :
 * requêtes HTTP réelles avec cookie de session capturé (pas de curl dans cet
 * environnement).
 */
class ProduitsTarifsInlineTest extends TestCase
{
    private $CI;
    private $cookie;
    private $produitId;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->cookie = $this->login_as_admin();
        $this->assertNotNull($this->cookie, 'La connexion admin doit renvoyer un cookie de session.');
        $this->produitId = null;
    }

    protected function tearDown(): void
    {
        if ($this->produitId) {
            $this->CI->db->where('produit_id', $this->produitId)->delete('tarifs');
            $this->CI->db->where('id', $this->produitId)->delete('produits');
        }
    }

    private function base_url()
    {
        return 'http://gvv.net/index.php/';
    }

    private function extract_session_cookie(array $headers)
    {
        $cookie = null;
        foreach ($headers as $h) {
            if (stripos($h, 'Set-Cookie:') === 0 && stripos($h, 'ci_session=') !== false) {
                $pair = trim(substr($h, strlen('Set-Cookie:')));
                $cookie = explode(';', $pair)[0];
            }
        }
        return $cookie;
    }

    private function login_as_admin()
    {
        $body = http_build_query(array('username' => 'testadmin', 'password' => 'password'));
        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'POST',
                'header'          => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content'         => $body,
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        @file_get_contents($this->base_url() . 'auth/login', false, $context);
        $headers = isset($http_response_header) ? $http_response_header : array();

        return $this->extract_session_cookie($headers);
    }

    private function http_get($url)
    {
        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'GET',
                'header'          => "Cookie: " . $this->cookie . "\r\n",
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        $body = @file_get_contents($url, false, $context);
        return $body === false ? '' : $body;
    }

    private function http_post($url, array $fields)
    {
        $body = http_build_query($fields);
        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'POST',
                'header'          => "Cookie: " . $this->cookie . "\r\nContent-Type: application/x-www-form-urlencoded\r\n",
                'content'         => $body,
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        $body = @file_get_contents($url, false, $context);
        return $body === false ? '' : $body;
    }

    /**
     * Compte éligible au sélecteur de produits::form_static_element()
     * (codec entre '7' et '8', cf. application/controllers/produits.php).
     */
    private function un_compte_valide()
    {
        $row = $this->CI->db->select('id')
            ->from('comptes')
            ->where("codec >=", "7")
            ->where("codec <", "8")
            ->limit(1)
            ->get()->row_array();
        $this->assertNotEmpty($row, 'Aucun compte éligible (codec 7x) trouvé pour le test.');
        return $row['id'];
    }

    private function base_produit_fields($reference, $compte)
    {
        return array(
            'id' => '',
            'reference' => $reference,
            'description' => 'Test panneau tarifs',
            'compte' => $compte,
            'public' => 1,
            'is_cotisation' => 0,
            'nb_personnes_max' => 1,
            'button' => 'Créer',
        );
    }

    public function testCreateWithoutTarifIsRejected()
    {
        $reference = 'ZZI_NOTARIF_' . uniqid();
        $compte = $this->un_compte_valide();

        $fields = $this->base_produit_fields($reference, $compte);
        $fields['tarifs_json'] = '';

        $body = $this->http_post($this->base_url() . 'produits/formValidation/1', $fields);

        $this->assertStringContainsString(
            'Au moins un tarif est requis',
            $body,
            'Le message de validation "au moins un tarif" doit être affiché.'
        );

        $count = $this->CI->db->where('reference', $reference)->count_all_results('produits');
        $this->assertEquals(0, $count, 'Aucun produit ne doit être créé sans tarif.');
    }

    public function testCreateWithOneTarifPersistsProductAndTarif()
    {
        $reference = 'ZZI_TARIF1_' . uniqid();
        $compte = $this->un_compte_valide();

        $fields = $this->base_produit_fields($reference, $compte);
        $fields['tarifs_json'] = json_encode(array(
            array('id' => null, 'date' => '2026-01-01', 'prix' => '42.50', 'nb_tickets' => 0),
        ));

        $this->http_post($this->base_url() . 'produits/formValidation/1', $fields);

        $produit = $this->CI->db->where('reference', $reference)->get('produits')->row_array();
        $this->assertNotEmpty($produit, 'Le produit doit avoir été créé.');
        $this->produitId = $produit['id'];

        $tarifs = $this->CI->db->where('produit_id', $this->produitId)->get('tarifs')->result_array();
        $this->assertCount(1, $tarifs);
        $this->assertEquals('42.50', number_format((float) $tarifs[0]['prix'], 2, '.', ''));
        $this->assertEquals('2026-01-01', $tarifs[0]['date']);
    }

    public function testEditPagePrefillsExistingTarifs()
    {
        $reference = 'ZZI_PREFILL_' . uniqid();
        $produitId = $this->CI->db->insert('produits', array(
            'reference' => $reference,
            'description' => 'Test prefill',
            'compte' => $this->un_compte_valide(),
            'club' => 0,
            'is_cotisation' => 0,
            'nb_personnes_max' => 1,
            'public' => 1,
        )) ? $this->CI->db->insert_id() : null;
        $this->assertNotNull($produitId);
        $this->produitId = $produitId;

        $this->CI->db->insert('tarifs', array(
            'produit_id' => $produitId,
            'date' => '2025-05-14',
            'prix' => 130.00,
            'nb_tickets' => 0,
        ));

        $body = $this->http_get($this->base_url() . 'produits/edit/' . $produitId);

        $this->assertStringContainsString('"date":"2025-05-14"', $body);
        $this->assertStringContainsString('"prix":"130.00"', $body);
    }

    public function testUpdateSyncsAddModifyAndDeleteTarifs()
    {
        $reference = 'ZZI_SYNC_' . uniqid();
        $compte = $this->un_compte_valide();

        // Création avec un tarif initial
        $fields = $this->base_produit_fields($reference, $compte);
        $fields['tarifs_json'] = json_encode(array(
            array('id' => null, 'date' => '2026-01-01', 'prix' => '10.00', 'nb_tickets' => 0),
        ));
        $this->http_post($this->base_url() . 'produits/formValidation/1', $fields);

        $produit = $this->CI->db->where('reference', $reference)->get('produits')->row_array();
        $this->produitId = $produit['id'];
        $tarif = $this->CI->db->where('produit_id', $this->produitId)->get('tarifs')->row_array();
        $tarifId = $tarif['id'];

        // Mise à jour : le tarif existant change de prix, un second est ajouté
        $updateFields = $this->base_produit_fields($reference, $compte);
        $updateFields['id'] = $this->produitId;
        $updateFields['original_id'] = $this->produitId;
        $updateFields['button'] = 'Mettre à jour';
        $updateFields['tarifs_json'] = json_encode(array(
            array('id' => $tarifId, 'date' => '2026-01-01', 'prix' => '20.00', 'nb_tickets' => 0),
            array('id' => null, 'date' => '2026-02-01', 'prix' => '30.00', 'nb_tickets' => 0),
        ));
        $this->http_post($this->base_url() . 'produits/formValidation/2', $updateFields);

        $tarifs = $this->CI->db->where('produit_id', $this->produitId)->order_by('date')->get('tarifs')->result_array();
        $this->assertCount(2, $tarifs);
        $this->assertEquals('20.00', number_format((float) $tarifs[0]['prix'], 2, '.', ''));
        $this->assertEquals('30.00', number_format((float) $tarifs[1]['prix'], 2, '.', ''));

        // Mise à jour : suppression du premier tarif (absent du JSON soumis)
        $secondTarifId = $tarifs[1]['id'];
        $dropFields = $this->base_produit_fields($reference, $compte);
        $dropFields['id'] = $this->produitId;
        $dropFields['original_id'] = $this->produitId;
        $dropFields['button'] = 'Mettre à jour';
        $dropFields['tarifs_json'] = json_encode(array(
            array('id' => $secondTarifId, 'date' => '2026-02-01', 'prix' => '30.00', 'nb_tickets' => 0),
        ));
        $this->http_post($this->base_url() . 'produits/formValidation/2', $dropFields);

        $remaining = $this->CI->db->where('produit_id', $this->produitId)->get('tarifs')->result_array();
        $this->assertCount(1, $remaining);
        $this->assertEquals($secondTarifId, $remaining[0]['id']);
    }

    public function testUpdateCannotDropToZeroTarifs()
    {
        $reference = 'ZZI_KEEP1_' . uniqid();
        $compte = $this->un_compte_valide();

        $fields = $this->base_produit_fields($reference, $compte);
        $fields['tarifs_json'] = json_encode(array(
            array('id' => null, 'date' => '2026-01-01', 'prix' => '15.00', 'nb_tickets' => 0),
        ));
        $this->http_post($this->base_url() . 'produits/formValidation/1', $fields);

        $produit = $this->CI->db->where('reference', $reference)->get('produits')->row_array();
        $this->produitId = $produit['id'];

        $updateFields = $this->base_produit_fields($reference, $compte);
        $updateFields['id'] = $this->produitId;
        $updateFields['original_id'] = $this->produitId;
        $updateFields['button'] = 'Mettre à jour';
        $updateFields['tarifs_json'] = json_encode(array());
        $body = $this->http_post($this->base_url() . 'produits/formValidation/2', $updateFields);

        $this->assertStringContainsString('Au moins un tarif est requis', $body);

        $tarifs = $this->CI->db->where('produit_id', $this->produitId)->get('tarifs')->result_array();
        $this->assertCount(1, $tarifs, 'Le tarif existant ne doit pas avoir été supprimé.');
    }
}
