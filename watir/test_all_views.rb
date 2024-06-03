# GVV Watir test
# Test de la home page
#
require './gvv_test.rb'
require './php_config.rb'


class TestAllViews < GVVTest
  
  def test_access_with_no_error

    description('that the views are displayed without errors', '', 'logged on as admin')
    
    self.login('testadmin', 'password')
    self.screenshot 'scr_home.png'

    conf = PhpConfig.new('../application/config/club.php')
    title = conf.value('nom_club', true)
    
    check(@b.text.include?(title), "Title #{title} is displayed")
    
    must_find = [title, "Boissel"]
    must_not_find = ['Vous etes d', '404 Page Not Found', 'A PHP Error']

    can_access('membre/page', 'list_membres', must_find, must_not_find)
    can_access('licences/per_year', 'licenses', must_find, must_not_find)  
    can_access('membre/edit', 'fiche', must_find, must_not_find)
    can_access('auth/change_password', 'password', must_find, must_not_find)
    can_access('mails/page', 'courriel', must_find, must_not_find)

    can_access('planeur/create', 'planeur', must_find, must_not_find)
    can_access('avion/create', 'planeur', must_find, must_not_find)
    
    can_access('vols_planeur/page', 'planche_planeur', must_find, must_not_find)
    can_access('vols_planeur/create', 'saisie_vol_planeur', must_find, must_not_find)
    can_access('vols_planeur/plancheauto_select', 'planche_auto', must_find, must_not_find)
    can_access('planeur/page', 'planeurs', must_find, must_not_find)
    can_access('vols_planeur/statistic', 'statistics_planeur', must_find, must_not_find)
    can_access('vols_planeur/cumuls', 'statistics_planeur_annuelles', must_find, must_not_find)
    can_access('vols_planeur/histo', 'historique_planeur', must_find, must_not_find)
    can_access('vols_planeur/age', 'age_planeur', must_find, must_not_find)
    can_access('event/stats', 'formation_annuelle', must_find, must_not_find)
    can_access('event/formation', 'formation_club', must_find, must_not_find)
    can_access('event/fai', 'formation_fai', must_find, must_not_find)
    can_access('vols_planeur/par_pilote_machine', 'formation_pilotes', must_find, must_not_find)
    
    can_access('vols_avion/page', 'planche_avion', must_find, must_not_find)
    can_access('vols_avion/create', 'saisie_vol_avion', must_find, must_not_find)
    can_access('avion/page', 'avions', must_find, must_not_find)
    can_access('vols_avion/statistic', 'statistics_avion', must_find, must_not_find)
    can_access('vols_avion/cumuls', 'statistics_avion_annuelles', must_find, must_not_find)

    can_access('compta/mon_compte', 'ma_facture', must_find, must_not_find)
    can_access('reports/page', 'requets_utilisateur', must_find, must_not_find)
    can_access('rapports/ffvv', 'rapport_ffvv', must_find, must_not_find)
    can_access('tickets/page', 'tickets', must_find, must_not_find)
    can_access('tickets/solde', 'solde_tickets', must_find, must_not_find)
    
    can_access('compta/page', 'journeaux', must_find, must_not_find)
    can_access('comptes/general', 'balance', must_find, must_not_find)
    can_access('comptes/page/411', 'soldes_pilotes', must_find, must_not_find)
    can_access('comptes/resultat', 'resultat', must_find, must_not_find)
    can_access('comptes/bilan', 'bilan', must_find, must_not_find)
    can_access('achats/list_per_year', 'ventes', must_find, must_not_find)
    can_access('comptes/tresorerie', 'tresorerie', must_find, must_not_find)
    
    can_access('compta/recettes', 'ecriture_recette', must_find, must_not_find)
    can_access('compta/reglement_pilote', 'ecriture_reglement', must_find, must_not_find)
    can_access('compta/factu_pilote', 'ecriture_facturation', must_find, must_not_find)
    can_access('compta/avoir_fournisseur', 'ecriture_avoir', must_find, must_not_find)
    can_access('compta/depenses', 'ecriture_depense', must_find, must_not_find)
    can_access('compta/credit_pilote', 'ecriture_depense_par_pilote', must_find, must_not_find)
    can_access('compta/debit_pilote', 'ecriture_rembourssement_pilote', must_find, must_not_find)
    can_access('compta/utilisation_avoir_fournisseur', 'ecriture_utilisation_avoir', must_find, must_not_find)
    can_access('compta/virement', 'ecriture_virement', must_find, must_not_find)
        
    # Pages de gestion  
    can_access('calendar', 'calendar', must_find, must_not_find)
    
    # Membres du conseil
    can_access('welcome/ca', 'welcome_ca', must_find, must_not_find)
    can_access('terrains/page', 'terrains', must_find, must_not_find)
    can_access('historique', 'historique', must_find, must_not_find)
    can_access('rapports/financier', 'rapports_financier', [], must_not_find)
    can_access('rapports/comptes', 'rapports_comptes', [], must_not_find)
    can_access('vols_avion/pdf', 'vols_avion_pdf', [], must_not_find)
    can_access('vols_planeur/pdf', 'vols_planeur_pdf', [], must_not_find)
    can_access('event/page', 'certificats', must_find, must_not_find)
    
    # Trésorier
    can_access('welcome/compta', 'welcome_compta', must_find, must_not_find)
    can_access('comptes/cloture', 'cloture', must_find, must_not_find)
    can_access('facturation/config', 'config_facturation', must_find, must_not_find)
    can_access('plan_comptable/page', 'plan_comptable', must_find, must_not_find)
    can_access('tarifs/page', 'tarifs', must_find, must_not_find)
    can_access('compta/create', 'ecriture_general', must_find, must_not_find)
      
    # Admin
    can_access('admin/page', 'admin', must_find, must_not_find)
    can_access('config', 'config_club', must_find, must_not_find)
    can_access('events_types', 'events_types', must_find, must_not_find)
    can_access('admin/restore', 'restore', must_find, must_not_find)
    can_access('migration', 'migration', must_find, must_not_find)
    can_access('backend/users', 'users', must_find, must_not_find)
    can_access('backend/roles', 'roles', must_find, must_not_find)
    can_access('backend/uri_permissions', 'permissions', must_find, must_not_find)
    
    self.logout()

  end

end
