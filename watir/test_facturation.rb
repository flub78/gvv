# encoding: utf-8
# GVV Watir test
#
# Test de la facturation dans différents cas
# 
# TODO machines privées, rem 300
# TODO utiliser Troyes avec le 1/10 de treuillé au treuillard et la gestion des tickets
#
require './gvv_test.rb'
require File.dirname(__FILE__) + '/reset_database.rb'

class TestFacturation < GVVTest
  :alpha
  
  # --------------------------------------------------------------------------------
  # Run before every test
  # --------------------------------------------------------------------------------
  def setup
    super
    self.login('testadmin', 'password')
    self.db_connect
    @date = '18/04/2015' + "\n"
  end

  # --------------------------------------------------------------------------------
  # Executed after each test
  # --------------------------------------------------------------------------------
  def teardown
    self.db_disconnect
    self.logout()
    super
  end

  # --------------------------------------------------------------------------------
  # Selectionne une facturation
  # --------------------------------------------------------------------------------
  def set_facturation(facturation)
    @b.goto  @root_url + 'config'
    @b.text_field(:name => 'club').set(facturation)
    @b.button(:value => 'Valider').click
    check(@b.text.include?("Configuration modifi"), "Facturation = " + facturation)
  end

  
  # --------------------------------------------------------------------------------
  # Effectue un achat pour un pilote
  # --------------------------------------------------------------------------------
  def achat(compte, date, reference, prix)
    # puts "achat(compte=#{compte}, date=#{date}, ref=#{reference}, prix=#{prix})"	  
    achats_initial = self.table_count(@db, 'achats')
    solde_initial = self.solde(compte)

    # puts "achats_initial = #{achats_initial}"
    # puts "solde_initial = #{solde_initial}"
    
    @b.goto  @root_url + 'compta/journal_compte/' + compte.to_s
    # Méthode compliquée pour ouvrir le fieldset "Achats"
    fieldset = @b.legend(:xpath, "//div[@id='body']/form/fieldset/legend")
    fieldset.click
        
    @b.text_field(:name => 'date').set date
    @b.select_list(:name => 'produit').select(reference)
    @b.button(:id => 'validation_achat').click
    
    @b.alert.ok

    achats_final = self.table_count(@db, 'achats')
    solde_final = self.solde(compte)

#    puts "achats_final = #{achats_final}"
#    puts "solde_final = #{solde_final}"

    check(achats_initial + 1 == achats_final, "Achats de " + reference)
    check(solde_initial - prix == solde_final, "Compte débité de " + prix.to_s)
  end
    
  # --------------------------------------------------------------------------------
  # Teste la facturation d'Abbeville
  # --------------------------------------------------------------------------------
  def test_a_facturation_planeur_abbeville
    
    description('Abbeville glider flight billing')
    set_facturation('accabs')

    vol_normal
    vol_au_forfait
    vol_partage
  end

  # --------------------------------------------------------------------------------
  # Vol facturé
  # --------------------------------------------------------------------------------
  def vol_normal
        
    description('standard glider flight billing')
    compte = self.compte("pilote = 'asterix'")
    solde_initial = self.solde(compte)
    achats_initial = self.table_count(@db, 'achats')

    # DC
    values = [
      {name: 'vpdate', value:  @date, type: 'text_field'},
      {name: 'vpmacid', value: 'Ask21 - F-CJRG - (RG)', type: 'select'},
      {name: 'vppilid', value: 'Legaulois Astérix', type: 'select'},
      {name: 'vpdc', value: '1', type: 'checkbox'},
      {name: 'vpinst', value: 'Chef Abraracourcix', type: 'select'},
      {name: 'vpcdeb', value: '14.00', type: 'text_field'},
      {name: 'vpcfin', value: '15.30', type: 'text_field'},
      {name: 'vpautonome', id: 'Treuil', type: 'radio'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_facturation_glider1")
      
    # Parfois on a "durée invalide" sur la saisie ...
    # Le javascript sur "onchange" n'est pas activé ...

    solde = self.solde(compte)
    check(solde == (solde_initial - 8.0 - 40.5), "Une treuillé et 1h30 facturées")
    achats_count = self.table_count(@db, 'achats')
    check(achats_initial + 2 == achats_count, "Achats créés par facturation planeur")
    
    # Annulation
    id = last_id('volsp', 'vpid')
    delete = 'vols_planeur/delete/' + id.to_s
    self.delete('volsp', delete, 1)

    solde = self.solde(compte)
    check(solde == (solde_initial), "Treuillé et HDV recréditées après annulation du vol")
    achats_count = self.table_count(@db, 'achats')
    check(achats_initial == achats_count, "Achats supprimés avec suppression du vol")
    
  end
    
  # --------------------------------------------------------------------------------
  # Teste la facturation d'Abbeville
  # --------------------------------------------------------------------------------
  def vol_au_forfait
    
    description('fixed price glider flight billing')
    self.logout()    
    self.login('panoramix', 'password')
        
    compte = self.compte("pilote = 'obelix'")
    achat(compte, '01/01/2015', 'Forfait heures : 380.00', 380)
    
    solde_initial = self.solde(compte)
    achats_initial = self.table_count(@db, 'achats')
  
    # DC
    values = [
      {name: 'vpdate', value:  @date, type: 'text_field'},
      {name: 'vpmacid', value: 'PW-5 - F-CICA - (CA)', type: 'select'},
      {name: 'vppilid', value: 'Legaulois Obélix', type: 'select'},
      {name: 'vpautonome', id: 'Remorqué', type: 'radio'},
      {name: 'vpcdeb', value: '15.00', type: 'text_field'},
      {name: 'vpcfin', value: '16.00', type: 'text_field'},
      {name: 'vpaltrem', value: '600', type: 'text_field'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_facturation_forfait")
  
    solde = self.solde(compte)
    check(solde == (solde_initial - 22.0 - 10.0 - 2.0), "Remorqué + 100 m + 1h00 au forfait facturées")
    achats_count = self.table_count(@db, 'achats')
    check(achats_initial + 3 == achats_count, "3 achats créés par facturation planeur")
  end

  # --------------------------------------------------------------------------------
  # Teste la facturation d'Abbeville
  # --------------------------------------------------------------------------------
  def vol_partage
        
    description('shared glider flight billing')
    self.logout()    
    self.login('panoramix', 'password')
    
    compte_obelix = self.compte("pilote = 'obelix'")
    compte_asterix = self.compte("pilote = 'asterix'")
    
    solde_initial_obelix = self.solde(compte_obelix)
    solde_initial_asterix = self.solde(compte_asterix)
    achats_initial = self.table_count(@db, 'achats')
  
    # DC
    values = [
      {name: 'vpdate', value:  @date, type: 'text_field'},
      {name: 'vpmacid', value: 'Ask21 - F-CJRG - (RG)', type: 'select'},
      {name: 'vppilid', value: 'Legaulois Obélix', type: 'select'},
      {name: 'vpcdeb', value: '17.00', type: 'text_field'},
      {name: 'vpcfin', value: '18.00', type: 'text_field'},
      {name: 'vpautonome', id: 'Remorqué', type: 'radio'},
      {name: 'payeur', value: 'Legaulois Astérix', type: 'select'},
      {name: 'pourcentage', id: '50', type: 'radio'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_facturation_partage")
  
    @b.goto  @root_url + 'compta/journal_compte/' + compte_obelix.to_s
    screenshot("fct_partage_obelix.png")
    solde_obelix = self.solde(compte_obelix)
    check(solde_obelix == (solde_initial_obelix - 11.0 - 5.0), "Un remorqué et 1h00 partagés Obelix")

    @b.goto  @root_url + 'compta/journal_compte/' + compte_asterix.to_s
    screenshot("fct_partage_asterix.png")
    solde_asterix = self.solde(compte_asterix)
    check(solde_asterix == (solde_initial_asterix - 11.0 - 5.0), "Un remorqué et 1h00 partagés Asterix")
    
    achats_count = self.table_count(@db, 'achats')
    check(achats_initial + 4 == achats_count, "Achats créés par facturation planeur partagée")
  end

  # --------------------------------------------------------------------------------
  # Teste la facturation d'Abbeville
  # --------------------------------------------------------------------------------
  def test_b_facturation_avion_abbeville
    
    description('Abbeville club airplane billing')
    set_facturation('accabs')
    
    self.logout()    
    self.login('goudurix', 'password')

    compte = self.compte("pilote = 'asterix'")
    solde_initial = self.solde(compte)
    achats_initial = self.table_count(@db, 'achats')
  
    values = [
      {name: 'vadate', value:  @date, type: 'text_field'},
      {name: 'vamacid', value: 'F-JUFA', type: 'select'},
      {name: 'vapilid', value: 'Legaulois Astérix', type: 'select'},
      {name: 'vahdeb', value: '12.0', type: 'text_field'},
      {name: 'vahfin', value: '13.30', type: 'text_field'},
      {name: 'vacdeb', value: '2.25', type: 'text_field'},
      {name: 'vacfin', value: '3.75', type: 'text_field'},
      {name: 'valieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'valieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsa', 'vols_avion/create', values, [], 1, "fct_facturation_avion")
  
    solde = self.solde(compte)
    check(solde == (solde_initial - 150.0), "1h30 ULM facturées")
    achats_count = self.table_count(@db, 'achats')
    check(achats_initial + 1 == achats_count, "Un achats créés par facturation avion")
    
  end

  # --------------------------------------------------------------------------------
  # Teste la facturation de Troyes
  # --------------------------------------------------------------------------------
  def test_c_facturation_planeur_troyes
    
    description('Troyes club glider billing')
    set_facturation('cpta')
    
    self.logout()    
    self.login('goudurix', 'password')

    tickets_treuil_troyes
    vols_gratuits
  end

  # --------------------------------------------------------------------------------
  # Vérifie que 1/10 de treuillé est attribué pour chaque treuillé comme treuillard
  # Vérifie que tant que le compte de ticket n'est pas suffisant les treuillées sont facturées
  # Vérifie que quand le compte de ticket est suffisant les ticketes sont décomptés et pas facturés
  # --------------------------------------------------------------------------------
  def tickets_treuil_troyes
    
    description('Troyes glider flight billing with launch tickets')

    # Goudurix réalise 9 treuillés
    1.upto(8) do |i|
      date = '0' + i.to_s + '/05/2015'

      values = [
        {name: 'vpdate', value:  date, type: 'text_field'},
        {name: 'vpmacid', value: 'Ask21 - F-CJRG - (RG)', type: 'select'},
        {name: 'vppilid', value: 'Legaulois Astérix', type: 'select'},
        {name: 'vpautonome', id: 'Treuil', type: 'radio'},
        {name: 'vpcdeb', value: '14.00', type: 'text_field'},
        {name: 'vpcfin', value: '15.30', type: 'text_field'},
        {name: 'vptreuillard', value: 'Chef Goudurix', type: 'select'},
        {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
        {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
      self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_facturation_troyes")
      # sleep(1)
    end
    
    # Goudurix vol et sa treuillée est facturée
    compte_goudurix = self.compte("pilote = 'abraracourcix'")  # Goudurix vole sur le compte de son oncle
    solde_initial_goudurix = self.solde(compte_goudurix)
    
    values = [
      {name: 'vpdate', value: '10/05/2015', type: 'text_field'},
      {name: 'vpmacid', value: 'Ask21 - F-CJRG - (RG)', type: 'select'},
      {name: 'vppilid', value: 'Chef Goudurix', type: 'select'},
      {name: 'vpcdeb', value: '10.00', type: 'text_field'},
      {name: 'vpcfin', value: '10.05', type: 'text_field'},
      {name: 'vpautonome', id: 'Treuil', type: 'radio'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_facturation_troyes_sans_ticket")
    solde_goudurix = self.solde(compte_goudurix)
    puts "solde initial = #{solde_initial_goudurix}"
    puts "solde = #{solde_goudurix}"
    check(solde_goudurix == (solde_initial_goudurix - 8.0 - 2.25), "Treuillée facturée quand pas assez de tickets")
    
    # Goudurix réalise une autre treuillée
    values = [
      {name: 'vpdate', value:  '10/05/2015', type: 'text_field'},
      {name: 'vpmacid', value: 'Ask21 - F-CJRG - (RG)', type: 'select'},
      {name: 'vppilid', value: 'Legaulois Astérix', type: 'select'},
      {name: 'vpcdeb', value: '10.10', type: 'text_field'},
      {name: 'vpcfin', value: '10.15', type: 'text_field'},
      {name: 'vpautonome', id: 'Treuil', type: 'radio'},
      {name: 'vptreuillard', value: 'Chef Goudurix', type: 'select'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_facturation_troyes")
      
    # Goudurix vol et sa treuillée n'est pas facturée
    solde_initial_goudurix = self.solde(compte_goudurix)
    values = [
      {name: 'vpdate', value: '10/05/2015', type: 'text_field'},
      {name: 'vpmacid', value: 'Ask21 - F-CJRG - (RG)', type: 'select'},
      {name: 'vppilid', value: 'Chef Goudurix', type: 'select'},
      {name: 'vpcdeb', value: '10.20', type: 'text_field'},
      {name: 'vpcfin', value: '10.25', type: 'text_field'},
      {name: 'vpautonome', id: 'Treuil', type: 'radio'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_facturation_troyes_sans_ticket")
    solde_goudurix = self.solde(compte_goudurix)
    check(solde_goudurix == solde_initial_goudurix - 2.25, "Treuillée gratuite quand assez de tickets")
    
    # Goudurix vol encore et sa treuillée est facturée
    solde_initial_goudurix = self.solde(compte_goudurix)
    
    values = [
      {name: 'vpdate', value: '10/05/2015', type: 'text_field'},
      {name: 'vpmacid', value: 'Ask21 - F-CJRG - (RG)', type: 'select'},
      {name: 'vppilid', value: 'Chef Goudurix', type: 'select'},
      {name: 'vpcdeb', value: '10.30', type: 'text_field'},
      {name: 'vpcfin', value: '10.35', type: 'text_field'},
      {name: 'vpautonome', id: 'Treuil', type: 'radio'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_facturation_troyes_sans_ticket")
    solde_goudurix = self.solde(compte_goudurix)
    check(solde_goudurix == solde_initial_goudurix - 8 - 2.25, "Treuillée facturée quand pas assez de tickets")
  end

  # --------------------------------------------------------------------------------
  # Vérifie que les heures ne sont pas facturés quand
  #   * c'est un vol d'éssai
  #   * c'est un VI
  #   * c'est un privé sur son planeur banalisé
  # Vérifie qu'ils sont facturés quand ce n'est pas le proprio sur un planeur banalisé
  # --------------------------------------------------------------------------------
  def vols_gratuits
    
    description('Troyes free glider flight billing')
    compte_goudurix = self.compte("pilote = 'abraracourcix'")  # Goudurix vole sur le compte de son oncle

    # Goudurix vol et sa treuillée n'est pas facturée
    solde_initial_goudurix = self.solde(compte_goudurix)
    values = [
      {name: 'vpdate', value: '10/05/2015', type: 'text_field'},
      {name: 'vpmacid', value: 'Ask21 - F-CJRG - (RG)', type: 'select'},
      {name: 'vppilid', value: 'Chef Goudurix', type: 'select'},
      {name: 'vpcdeb', value: '18.20', type: 'text_field'},
      {name: 'vpcfin', value: '18.25', type: 'text_field'},
      {name: 'vpautonome', id: 'Treuil', type: 'radio'},
      {name: 'vpcategorie', id: "Vol d'essai", type: 'radio'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_facturation_troyes_vol_essai")
    solde_goudurix = self.solde(compte_goudurix)
    check(solde_goudurix == solde_initial_goudurix, "Vol d'essai non facturés, avant=#{solde_initial_goudurix}, après=#{solde_goudurix}")
    
    solde_initial_goudurix = self.solde(compte_goudurix)
    values = [
      {name: 'vpdate', value: '10/05/2015', type: 'text_field'},
      {name: 'vpmacid', value: 'Asw20 - F-CERP - (UP)', type: 'select'},
      {name: 'vppilid', value: 'Chef Abraracourcix', type: 'select'},
      {name: 'vpcdeb', value: '19.20', type: 'text_field'},
      {name: 'vpcfin', value: '19.25', type: 'text_field'},
      {name: 'vpautonome', id: 'Treuil', type: 'radio'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_facturation_troyes_prive")
    solde_goudurix = self.solde(compte_goudurix)
    # -8 c'est pour le prix de la treuillée
    check(solde_goudurix == solde_initial_goudurix - 8, 
      "Les propriétaires ne payent pas leur machine, avant=#{solde_initial_goudurix}, après=#{solde_goudurix}")

    solde_initial_goudurix = self.solde(compte_goudurix)
    values = [
      {name: 'vpdate', value: '10/05/2015', type: 'text_field'},
      {name: 'vpmacid', value: 'Asw20 - F-CERP - (UP)', type: 'select'},
      {name: 'vppilid', value: 'Chef Goudurix', type: 'select'},
      {name: 'vpcdeb', value: '20.20', type: 'text_field'},
      {name: 'vpcfin', value: '20.25', type: 'text_field'},
      {name: 'vpautonome', id: 'Treuil', type: 'radio'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_facturation_troyes_prive")
    solde_goudurix = self.solde(compte_goudurix)
    check(solde_goudurix == solde_initial_goudurix - 8 - 2.25, "Les membres payent les machines banalisées, avant=#{solde_initial_goudurix}, après=#{solde_goudurix}")
    
  end

end
