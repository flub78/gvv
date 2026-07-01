<?php

/**
 * Tests d'autorisation pour l'accès aux justificatifs par les non-trésoriers.
 *
 * Un membre non trésorier doit pouvoir voir les justificatifs de ses propres écritures,
 * ne pas pouvoir en créer, et ne pas pouvoir accéder aux justificatifs d'autres comptes.
 *
 * Ces tests vérifient la présence du code d'autorisation correct dans le contrôleur.
 * Ils ÉCHOUENT si le contrôleur ne contient pas l'autorisation attendue.
 */
class ComptaAttachmentsAuthorizationTest extends PHPUnit\Framework\TestCase
{
    private $source;

    protected function setUp(): void
    {
        $this->source = file_get_contents(APPPATH . 'controllers/compta.php');
    }

    /**
     * get_attachments_section doit être dans la whitelist des méthodes
     * accessibles aux non-trésoriers (propriétaires du compte).
     * ÉCHOUE si la méthode est bloquée par le constructeur pour tous les non-trésoriers.
     */
    public function testGetAttachmentsSectionIsInNonTresorierWhitelist()
    {
        $this->assertStringContainsString(
            "'get_attachments_section'",
            $this->source,
            'get_attachments_section doit être dans la whitelist du constructeur pour les non-trésoriers propriétaires'
        );
    }

    /**
     * get_attachments_section doit vérifier que l'utilisateur est propriétaire
     * du compte avant d'afficher les justificatifs.
     * ÉCHOUE si aucun contrôle de propriété n'est implémenté.
     */
    public function testGetAttachmentsSectionChecksAccountOwnership()
    {
        // La méthode doit contenir une vérification de propriété du compte
        $this->assertRegExp(
            '/function get_attachments_section.*?comptes_model.*?user.*?deny_access/s',
            $this->source,
            'get_attachments_section doit vérifier que l\'utilisateur est propriétaire du compte avant d\'afficher les justificatifs'
        );
    }

    /**
     * Les non-trésoriers ne doivent pas voir les boutons d'action (Créer, Modifier, Supprimer).
     * PASSE même avant la correction (les boutons sont déjà conditionnels à has_role('tresorier')).
     */
    public function testNonTresorierDoesNotSeeCreateButton()
    {
        // Le bouton Créer est conditionnel au rôle tresorier
        $this->assertRegExp(
            '/has_role\(\'tresorier\'\).*?showCreateForm/s',
            $this->source,
            'Le bouton Créer doit être conditionnel au rôle tresorier'
        );
    }

    /**
     * Les boutons Modifier et Supprimer ne doivent être visibles que pour les trésoriers.
     * PASSE même avant la correction.
     */
    public function testNonTresorierDoesNotSeeEditDeleteButtons()
    {
        $this->assertRegExp(
            '/has_role\(\'tresorier\'\).*?edit-attachment-btn.*?delete-attachment-btn/s',
            $this->source,
            'Les boutons Modifier et Supprimer doivent être conditionnels au rôle tresorier'
        );
    }
}
