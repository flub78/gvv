# Bootstrap Buttons Migration Plan (Create, Edit, Delete)

... (content unchanged above) ...

Phase 1 — Inventory of views to update
- [x] Search for all tables/DataTables that list resources (candidate patterns: `$this->gvvmetadata->table(`, `<table`, `dataTable`, `datatable`, action columns with edit/delete links).
- [x] Produce a checklist (file path, controller, metadata-driven? create route?)
- [x] Explicitly list exceptions not driven by metadata.

... (other phases unchanged) ...

Appendix — Inventory (Phase 1 findings)

Metadata-driven list views (use $this->gvvmetadata->table)
- Confirmed many views pass actions => ['edit','delete'] to metadata:
  - mails, categorie, achats, historique, vols_avion (variable $actions), compta (journal, journalCompte), volsdécouverte (with extra actions), sections, membre (list and embedded tables), attachments, pompes, vols_planeur, planeur, tickets, associations_ecriture, terrains, licences, configuration, associations_of, associations_releve, avion, tarifs (with clone), user_roles_per_section, comptes (vue_comptes), reports (extra actions), events, types_ticket, plan_comptable
- Create routes inferred/present (see previous list with routes). Most core resources have create() in their controllers; a few need confirmation (sections, membre, plan_comptable, user_roles_per_section).

Non-metadata/custom tables (need direct view updates following roles)
- authorization/roles — baseline reference OK
- authorization/user_roles — no Create button; manage mappings
- authorization/role_permissions — no Create; mapping add/remove only
- authorization/data_access_rules — no Create; mapping add/remove only
- authorization/audit_log — no Create; log viewer
- procedures — already Bootstrap-styled; action buttons present
- backend/users, backend/roles — custom helpers/classes; review for unification

Exceptions: lists without Create button
- Authorization mapping and audit pages listed above
- Reconciliation/check tools (dbchecks, rapprochements, certain openflyers pages) — Create not applicable

Confirmation: centralization point for action buttons
- MetaData::action() and related table render methods handle action cell HTML and delete confirm. We will update this to output Bootstrap-styled buttons with Font Awesome icons consistently.

Phase 1 status
- Completed. Inventory, routes, and exceptions are identified; metadata centralization point confirmed.
