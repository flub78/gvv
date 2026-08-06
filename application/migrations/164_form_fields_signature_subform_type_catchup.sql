-- Migration 164 catch-up script
-- =================================================================
-- Context: migration 164 (application/migrations/164_form_fields_signature_subform_type.php)
-- extends form_fields.field_type with 'signature' and 'subform'. That ALTER TABLE alone does not
-- touch existing data. Before it was applied, sync_fields_from_html() silently skipped every
-- signature/subform widget it encountered (insert failed against the old ENUM), so the
-- form_fields rows for those widgets were never created, even though the widgets themselves
-- are present and working in the published form HTML.
--
-- This script backfills the 12 missing form_fields rows for the 4 affected forms:
--   bulletin_adhesion          -> 2 signature + 4 subform widgets
--   inscription_bia            -> 4 signature widgets
--   briefing_passager_ulm      -> 1 signature widget
--   attestation_de_formation_ulm -> 1 signature widget
--
-- Requirements:
--   - MUST be run AFTER migration 164's ALTER TABLE (form_fields.field_type must already
--     accept 'signature' and 'subform', otherwise every INSERT below fails).
--   - Portable: forms/pages are looked up by `forms.code` + `form_pages.page_number`, not by
--     hardcoded numeric IDs (dev and prod databases do not share the same auto_increment values).
--   - Idempotent: every INSERT is guarded by NOT EXISTS on (form_id, name), matching the
--     UNIQUE KEY uq_form_fields_name(form_id, name). Re-running this script is a no-op the
--     second time.
--   - Safe to no-op: if a form doesn't exist in a given database (e.g. code not imported there),
--     its INSERT ... SELECT simply returns zero rows instead of erroring.
--   - sort_order is computed as MAX(existing sort_order for that page) + a per-form offset, which
--     reproduces exactly what sync_fields_from_html()/extract_html_fields() would compute: all
--     plain inputs first (already correctly present), then all signature widgets in document
--     order, then all subform widgets in document order.
-- =================================================================

-- ---------------------------------------------------------------
-- bulletin_adhesion : signature_membre (required), signature_representant_legal
-- ---------------------------------------------------------------
INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'signature_membre', 'Signature du futur membre', 'signature', 1, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'bulletin_adhesion'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'signature_membre');

INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'signature_representant_legal', 'Signature du représentant légal (pour les mineurs)', 'signature', 0, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'bulletin_adhesion'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'signature_representant_legal');

INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'inscription_bia', "Brevet d'Initiation Aéronautique (BIA)", 'subform', 0, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'bulletin_adhesion'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'inscription_bia');

INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'inscription_avion', 'Section avion', 'subform', 0, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'bulletin_adhesion'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'inscription_avion');

INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'inscription_planeur', 'Section planeur', 'subform', 0, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'bulletin_adhesion'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'inscription_planeur');

INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'inscription_ulm', 'Section ULM', 'subform', 0, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'bulletin_adhesion'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'inscription_ulm');

-- ---------------------------------------------------------------
-- inscription_bia : signature_demandeur (required), signature_representant_legal,
--                    signature_eleve, signature_representant_legal_engagement
-- ---------------------------------------------------------------
INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'signature_demandeur', 'Signature', 'signature', 1, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'inscription_bia'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'signature_demandeur');

INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'signature_representant_legal', 'Signature du représentant légal (pour les élèves mineurs)', 'signature', 0, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'inscription_bia'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'signature_representant_legal');

INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'signature_eleve', "Signature de l'élève", 'signature', 0, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'inscription_bia'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'signature_eleve');

INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'signature_representant_legal_engagement', 'Signature du représentant légal (pour les élèves mineurs)', 'signature', 0, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'inscription_bia'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'signature_representant_legal_engagement');

-- ---------------------------------------------------------------
-- briefing_passager_ulm : signature_passager (required)
-- ---------------------------------------------------------------
INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'signature_passager', 'Signature passager ou représentant légal (si mineur)', 'signature', 1, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'briefing_passager_ulm'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'signature_passager');

-- ---------------------------------------------------------------
-- attestation_de_formation_ulm : signature_instructeur
-- ---------------------------------------------------------------
INSERT INTO form_fields (form_id, page_id, name, label, field_type, is_required, is_identifier, sort_order, options_json, validation_rules, gvv_role, created_at, updated_at, created_by, updated_by)
SELECT f.id, fp.id, 'signature_instructeur', "Signature de l'instructeur habilité", 'signature', 0, 0,
       (SELECT COALESCE(MAX(ff.sort_order), 0) FROM form_fields ff WHERE ff.page_id = fp.id) + 1,
       NULL, NULL, NULL, NOW(), NOW(), 'migration_164_catchup', 'migration_164_catchup'
FROM forms f JOIN form_pages fp ON fp.form_id = f.id AND fp.page_number = 1
WHERE f.code = 'attestation_de_formation_ulm'
  AND NOT EXISTS (SELECT 1 FROM form_fields ff WHERE ff.form_id = f.id AND ff.name = 'signature_instructeur');
