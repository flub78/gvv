<?php

use PHPUnit\Framework\TestCase;

/**
 * Régression : briefing passager impossible à enregistrer depuis un smartphone.
 *
 * Les tables form_submission* et la connexion sont en utf8 (utf8mb3) ; en mode SQL
 * strict, un caractère codé sur 4 octets (emoji du clavier mobile) faisait rejeter
 * l'INSERT et la soumission échouait avec un message générique sans détail.
 */
class FormSubmissionsFourByteCharsTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $model;
    private $form_id;
    private $submission_ids = array();

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        $CI->load->model('form_submissions_model');
        $this->model = $CI->form_submissions_model;

        $suffix = uniqid();
        $this->db->insert('forms', array(
            'code'        => 'four_byte_test_' . $suffix,
            'title'       => 'Four byte chars test',
            'public_slug' => 'four-byte-test-' . $suffix,
            'status'      => 'published',
        ));
        $this->form_id = $this->db->insert_id();
    }

    protected function tearDown(): void
    {
        foreach ($this->submission_ids as $submission_id) {
            $this->db->where('submission_id', $submission_id)->delete('form_submission_values');
            $this->db->where('id', $submission_id)->delete('form_submissions');
        }
        if ($this->form_id) {
            $this->db->where('id', $this->form_id)->delete('forms');
        }
    }

    public function testSubmissionWithEmojiIsSavedWithoutTheEmoji()
    {
        $submission_id = $this->model->create_submission(array(
            'form_id'        => $this->form_id,
            'status'         => 'submitted',
            'submitter_name' => 'Jean 😀 Dupont',
            'values'         => array(
                'nom'                 => 'Dupont',
                'personne_a_prevenir' => 'Maman ❤️😘',
                'accepte'             => array('oui 👍'),
            ),
        ));
        if ($submission_id) {
            $this->submission_ids[] = $submission_id;
        }

        $this->assertNotEmpty($submission_id, 'SQL error: ' . json_encode($this->model->last_error));
        $this->assertNull($this->model->last_error);

        $values = array();
        foreach ($this->model->get_submission_values($submission_id) as $row) {
            $values[$row['field_name']] = $row['value_text'];
        }
        $this->assertSame('Dupont', $values['nom']);
        // ❤ (U+2764) et le sélecteur de variante (U+FE0F) sont sur 3 octets : conservés.
        $this->assertSame('Maman ❤️', $values['personne_a_prevenir']);
        $this->assertSame(array('oui 👍'), json_decode($values['accepte'], true));

        $submission = $this->model->get_by_id($submission_id);
        $this->assertSame('Jean  Dupont', $submission['submitter_name']);
    }
}
