<?php

require_once(__DIR__ . '/TransactionalTestCase.php');

/**
 * Regression tests for accounting authorization in new authorization mode.
 */
class ComptaEditAuthorizationTest extends TransactionalTestCase
{
    /**
     * Modification rights are centralized in Gvv_Controller so every accounting
     * controller uses the same new-auth aware section-scoped check.
     */
    public function testSharedModificationRightsHelperSupportsSectionContext()
    {
        $controller_file = APPPATH . 'libraries/Gvv_Controller.php';
        $this->assertFileExists($controller_file, 'Gvv_Controller.php must exist');

        $source = file_get_contents($controller_file);

        $this->assertRegExp(
            '/protected function has_modification_rights\(\$section_id = NULL\)\s*\{.*?if \(!isset\(\$this->modification_level\) \|\| \$this->modification_level === \'\'\) \{.*?return TRUE;.*?\}.*?if \(\$this->dx_auth->is_admin\(\)\) \{.*?return TRUE;.*?\}.*?return \$this->allow_roles\(\[\$this->modification_level\], \$section_id\);.*?\}/s',
            $source,
            'Gvv_Controller::has_modification_rights() must support explicit section context via allow_roles()'
        );
    }

    /**
     * Core compta write paths must use the helper instead of calling DX_Auth
     * directly, otherwise section-scoped tresoriers regress on edit and related UI.
     */
    public function testEditPathsUseHasModificationRightsHelper()
    {
        $controller_file = APPPATH . 'controllers/compta.php';
        $source = file_get_contents($controller_file);

        $this->assertRegExp(
            '/function edit\(.*?if \(!\$this->has_modification_rights\(\)\) \{.*?\}/s',
            $source,
            'edit() must use has_modification_rights()'
        );

        $this->assertRegExp(
            '/public function formValidation\(.*?if \(!\$this->has_modification_rights\(\)\) \{.*?\}/s',
            $source,
            'formValidation() must use has_modification_rights()'
        );

        $this->assertRegExp(
            '/function datatable_journal_compte\(.*?\$journal_section_id = isset\(\$data\[\'club\'\]\) \? \(int\) \$data\[\'club\'\] : NULL;.*?\$has_modification_rights = \$this->has_modification_rights\(\$journal_section_id\);/s',
            $source,
            'datatable_journal_compte() must use has_modification_rights() with the account section when deciding whether to render edit links'
        );

        $this->assertRegExp(
            '/function toggle_gel\(.*?\$has_modification_rights = \$this->has_modification_rights\(\);/s',
            $source,
            'toggle_gel() must use has_modification_rights()'
        );
    }

    /**
     * Account-oriented read/export routes must check treasurer rights in the
     * relevant section, not only in the current DX_Auth legacy role set.
     */
    public function testAccountRoutesUseSectionAwareModificationHelper()
    {
        $controller_file = APPPATH . 'controllers/compta.php';
        $source = file_get_contents($controller_file);

        $this->assertRegExp(
            '/function journal_compte\(.*?\$modification_section_id = isset\(\$data\[\'club\'\]\) \? \(int\) \$data\[\'club\'\] : null;.*?if \(!\$this->has_modification_rights\(\$modification_section_id\)\) \{/s',
            $source,
            'journal_compte() must use has_modification_rights() with the account section context'
        );

        $this->assertRegExp(
            '/function journal_compte\(.*?\$cross_section_ok = \$this->config->item\(\'tresorers_can_access_others_sections\'\)\s*&& \$this->has_modification_rights\(NULL\);/s',
            $source,
            'journal_compte() cross-section treasurer read access must use has_modification_rights(NULL)'
        );

        // Bureau role must grant read-only access to any account journal, matching
        // the behaviour already present in datatable_journal_compte().
        $this->assertRegExp(
            '/function journal_compte\(.*?\$is_bureau = \$this->user_has_role\(\'bureau\'\);.*?if \(\$cross_section_ok \|\| \$is_global_ca \|\| \$is_bureau\)/s',
            $source,
            'journal_compte() must grant read-only access to bureau users, same as datatable_journal_compte()'
        );

        $this->assertRegExp(
            '/function pdf\(.*?\$compte_data = \$this->comptes_model->get_by_id\(\'id\', \$compte\);.*?\$modification_section_id = .*?;.*?if \(!\$this->has_modification_rights\(\$modification_section_id\)\) \{/s',
            $source,
            'pdf() must use has_modification_rights() with the account section context'
        );

        $this->assertRegExp(
            '/function export\(.*?\$compte_data = \$this->comptes_model->get_by_id\(\'id\', \$compte\);.*?\$modification_section_id = .*?;.*?if \(!\$this->has_modification_rights\(\$modification_section_id\)\) \{/s',
            $source,
            'export() must use has_modification_rights() with the account section context'
        );
    }

    /**
     * Other accounting controllers should use the shared helper instead of
     * reintroducing legacy DX_Auth checks in their list views.
     */
    public function testOtherAccountingControllersUseSharedHelper()
    {
        $comptes_source = file_get_contents(APPPATH . 'controllers/comptes.php');
        $tarifs_source = file_get_contents(APPPATH . 'controllers/tarifs.php');

        // comptes.php now passes the section id so rights are scoped to the displayed section.
        $this->assertRegExp(
            '/\$this->data\[\'has_modification_rights\'\] = \$this->has_modification_rights\(\$[a-z_]+ \? \$[a-z_]+\[\'id\'\] : NULL\);/s',
            $comptes_source,
            'comptes controller must use the shared has_modification_rights() helper with section context'
        );

        $this->assertRegExp(
            '/\$this->data\[\'has_modification_rights\'\] = \$this->has_modification_rights\(\);/s',
            $tarifs_source,
            'tarifs controller must use the shared has_modification_rights() helper'
        );
    }
}