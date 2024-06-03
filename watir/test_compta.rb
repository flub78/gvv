# encoding: utf-8
# GVV Watir test
#
# Tests de la comptabilité
require './gvv_test.rb'
require File.dirname(__FILE__) + '/reset_database.rb'


class TestCompta < GVVTest
  # --------------------------------------------------------------------------------
  # Run before every test
  # --------------------------------------------------------------------------------
  def setup
    super
    self.login('testadmin', 'password')
    self.db_connect
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
  # Enregistrement d'une dépense
  # --------------------------------------------------------------------------------
  def test_depense
    
    description('Basic expenses operations')

    compte1 = self.compte("nom = 'Essence + Huile'")
    compte2 = 23 # self.compte("nom = 'Banque vol à voile'")     (pb encodage utf8 ?)

    solde_initial_compte1 = self.solde(compte1)
    solde_initial_compte2 = self.solde(compte2)
#    puts "solde_initial_compte1 = #{solde_initial_compte1}"
#    puts "solde_initial_compte2 = #{solde_initial_compte2}"
    
    # Remorqué
    values = [
      {name: 'date_op', value:  '16/04/2015' + "\n", type: 'text_field'},
      {name: 'compte1', value: '(606) Essence + Huile', type: 'select'},
      {name: 'compte2', value: '(512) Banque vol à voile', type: 'select'},
      {name: 'montant', value: '128.00', type: 'text_field'},
      {name: 'description', value: 'Essence 100l', type: 'text_field'},
      {name: 'num_cheque', value: 'CA7654321', type: 'text_field'}]
    self.fill_form('ecritures', 'compta/depenses', values, [], 1, "fct_depense")

    solde_final_compte1 = self.solde(compte1)
    solde_final_compte2 = self.solde(compte2)
#    puts "solde_final_compte1 = #{solde_final_compte1}"
#    puts "solde_final_compte2 = #{solde_final_compte2}"
    
    check(solde_final_compte1 = solde_initial_compte1 - 128, "compte dépense crédité")
    check(solde_final_compte2 = solde_initial_compte2 + 128, "compte bancaire débité")
    
    # Annulation
    id = last_id('ecritures', 'id')
    delete = 'compta/delete/' + id.to_s
    self.delete('ecritures', delete, 1)

    solde_final_compte1 = self.solde(compte1)
    solde_final_compte2 = self.solde(compte2)
    #    puts "solde_final_compte1 = #{solde_final_compte1}"
    #    puts "solde_final_compte2 = #{solde_final_compte2}"
    
    check(solde_final_compte1 = solde_initial_compte1, "compte dépense crédit annulé")
    check(solde_final_compte2 = solde_initial_compte2, "compte bancaire débit annulé")

  end

  # --------------------------------------------------------------------------------
  # Enregistrement d'une recette
  # --------------------------------------------------------------------------------
  def test_recette
    
    description('Basic input operations')

    compte2 = self.compte("nom = \"Vols d'intiation\"")
    compte1 = 23 # self.compte("nom = 'Banque vol à voile'")     (pb encodage utf8 ?)
    solde_initial_compte1 = self.solde(compte1)
    solde_initial_compte2 = self.solde(compte2)
    # puts "solde_initial_compte1 = #{solde_initial_compte1}"
    # puts "solde_initial_compte2 = #{solde_initial_compte2}"
    
    montant = 85
    values = [
      {name: 'date_op', value:  '16/04/2015' + "\n", type: 'text_field'},
      {name: 'compte2', value: "(706) Vols d'intiation", type: 'select'},
      {name: 'compte1', value: '(512) Banque vol à voile', type: 'select'},
      {name: 'montant', value: montant.to_s, type: 'text_field'},
      {name: 'description', value: 'VI n° 42', type: 'text_field'},
      {name: 'num_cheque', value: 'CDN7654321', type: 'text_field'}]
    self.fill_form('ecritures', 'compta/recettes', values, [], 1, "fct_recette")
  
    solde_final_compte1 = self.solde(compte1)
    solde_final_compte2 = self.solde(compte2)
    # puts "solde_final_compte1 = #{solde_final_compte1}"
    # puts "solde_final_compte2 = #{solde_final_compte2}"
    
    check(solde_final_compte1 = solde_initial_compte1 - montant, "compte bancaire crédité")
    check(solde_final_compte2 = solde_initial_compte2 + montant, "compte VI débité")
    
  end

end
