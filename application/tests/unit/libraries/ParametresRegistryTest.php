<?php

use PHPUnit\Framework\TestCase;

/**
 * @feature Configuration
 *
 * Cohérence du registre qui décrit les paramètres de la page Configuration.
 */
class ParametresRegistryTest extends TestCase
{
    private $tabs;
    private $registry;
    private $langs = array();

    protected function setUp(): void
    {
        $config = array();
        include APPPATH . 'config/parametres_registry.php';
        $this->tabs = $config['parametres_tabs'];
        $this->registry = $config['parametres_registry'];

        foreach (array('french', 'english', 'dutch') as $l) {
            $lang = array();
            include APPPATH . "language/$l/parametres_lang.php";
            $this->langs[$l] = $lang;
        }
    }

    /**
     * Scénario: Chaque paramètre est rangé dans un onglet et réservé à un profil connu
     *   Étant donné la liste des paramètres de la page Configuration
     *   Quand on examine chacun d'eux
     *   Alors il appartient à un onglet existant
     *   Et il est lisible et modifiable par un profil connu (CA, administrateur du club ou développeur)
     *   Et le profil qui le modifie peut aussi le lire
     */
    public function testEveryParameterIsConsistent()
    {
        $ranks = array('ca' => 1, 'club-admin' => 2, 'dev' => 3);
        $types = array('text', 'email', 'url', 'textarea', 'markdown', 'bool', 'int', 'password');
        foreach ($this->registry as $key => $def) {
            $this->assertArrayHasKey($def['tab'], $this->tabs, "$key : onglet inconnu");
            $this->assertArrayHasKey($def['read'], $ranks, "$key : profil de lecture inconnu");
            $this->assertArrayHasKey($def['write'], $ranks, "$key : profil de modification inconnu");
            $this->assertGreaterThanOrEqual($ranks[$def['read']], $ranks[$def['write']], "$key : modifiable mais pas lisible");
            $this->assertContains($def['type'], $types, "$key : type inconnu");
            $this->assertRegExp('/^(db|file:[a-z_]+)$/', $def['store'], "$key : stockage inconnu");
        }
    }

    /**
     * Scénario: Les onglets et paramètres sont traduits dans les trois langues
     *   Étant donné la page Configuration affichée en français, anglais ou néerlandais
     *   Quand on lit les libellés des onglets et des paramètres
     *   Alors chacun a un texte dans chaque langue
     */
    public function testLabelsAreTranslated()
    {
        $keys = array();
        foreach ($this->tabs as $tab) {
            $keys[] = $tab['label'];
            if (!empty($tab['help'])) $keys[] = $tab['help'];
        }
        foreach ($this->registry as $def) {
            $keys[] = $def['label'];
            if (!empty($def['help'])) $keys[] = $def['help'];
        }
        foreach ($this->langs as $l => $lang) {
            foreach ($keys as $k) {
                $this->assertArrayHasKey($k, $lang, "$l : libellé $k manquant");
            }
            $this->assertSame(array_keys($this->langs['french']), array_keys($lang), "$l : clés différentes du français");
        }
    }
}
