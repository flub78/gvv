<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Interface des handlers post-soumission de formulaire (Lot 6, étape 6.3).
 *
 * Une classe déclarée dans forms.handler_class doit implémenter cette
 * interface. forms_public::submit() l'instancie et appelle after_submit()
 * juste après la création de la soumission, uniquement si handler_class
 * est renseigné.
 */
interface GvvFormHandlerInterface {

    /**
     * @param int         $submission_id Identifiant de la soumission qui vient d'être créée.
     * @param string|null $subject_type  Référence générique au sujet (ex. 'vols_decouverte'), ou null.
     * @param int|null    $subject_id    Identifiant du sujet, ou null.
     * @return array ['redirect_url' => string|null, 'error' => string|null]
     */
    public function after_submit(int $submission_id, ?string $subject_type, ?int $subject_id): array;
}
