# encoding: utf-8
# GVV Watir test
#
# Génération des vols
#
# - remplir la base avec une certaine quantié de données qui ai l'air logique
# - facteur journalier
#   - une chance sur 10 (0.1) de voler en Nov, Déc, Janv, Fev
#   - une chance sur 5  (0.2) de voler en mars, avril, septembre et octobre
#   - une chance sur deux (0.5) de voler en mai, juin, juillet, aout
#   - multiplié par 1.5 les samedi dimanches
# - temps de vol = 0h15 + 10h00 * random * fj
#
require './gvv_test.rb'
require File.dirname(__FILE__) + '/reset_database.rb'

class TestFlights < GVVTest
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
  # Create glider flights
  # --------------------------------------------------------------------------------
  def test_glider_flights
    description('glider flights input')
    date = '16/04/2015' + "\n"
    # Remorqué
    values = [
      {name: 'vpdate', value:  date, type: 'date'},
      {name: 'vpmacid', value: 'Asw20 - F-CERP - (UP)', type: 'select'},
      {name: 'vppilid', value: 'Chef Abraracourcix', type: 'select'},
      {name: 'vpcdeb', value: '12.00', type: 'text_field'},
      {name: 'vpcfin', value: '12.30', type: 'text_field'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_vol_planeur")

    # Treuil
    values = [
      {name: 'vpdate', value:  date, type: 'date'},
      {name: 'vpmacid', value: 'Ask21 - F-CJRG - (RG)', type: 'select'},
      {name: 'vppilid', value: 'Chef Abraracourcix', type: 'select'},
      {name: 'vpcdeb', value: '12.45', type: 'text_field'},
      {name: 'vpcfin', value: '13.30', type: 'text_field'},
      {name: 'vpautonome', id: 'Treuil', type: 'radio'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_vol_planeur_treuil")

    # DC
    values = [
      {name: 'vpdate', value:  date, type: 'date'},
      {name: 'vpmacid', value: 'Ask21 - F-CJRG - (RG)', type: 'select'},
      {name: 'vppilid', value: 'Legaulois Astérix', type: 'select'},
      {name: 'vpdc', value: '1', type: 'checkbox'},
      {name: 'vpinst', value: 'Chef Abraracourcix', type: 'select'},
      {name: 'vpcdeb', value: '14.00', type: 'text_field'},
      {name: 'vpcfin', value: '15.30', type: 'text_field'},
      {name: 'vpautonome', id: 'Treuil', type: 'radio'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, [], 1, "fct_vol_planeur_dc")

    # Erreur
    values = []
    errors = ['Pilote est requis', 'Début est requis', 'Durée invalide']
    self.fill_form('volsp', 'vols_planeur/create', values, [], 0)

    # Pilote en vol
    # TODO: rejeter aussi planeur déjà en vol
    values = [
      {name: 'vpdate', value:  date, type: 'date'},
      {name: 'vpmacid', value: 'Ask21 - F-CJRG - (RG)', type: 'select'},
      {name: 'vppilid', value: 'Legaulois Astérix', type: 'select'},
      {name: 'vpdc', value: '1', type: 'checkbox'},
      {name: 'vpinst', value: 'Chef Abraracourcix', type: 'select'},
      {name: 'vpcdeb', value: '15.00', type: 'text_field'},
      {name: 'vpcfin', value: '15.40', type: 'text_field'},
      {name: 'vpautonome', id: 'Treuil', type: 'radio'},
      {name: 'vplieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'vplieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsp', 'vols_planeur/create', values, ['pilote est déjà en vol'], 0, "fct_vol_planeur_en_vol")

  end

  # --------------------------------------------------------------------------------
  # Create glider flights
  # --------------------------------------------------------------------------------
  def test_airplane_flights
    
    description('airplane flights input')
    date = '17/04/2015' + "\n"
    values = [
      {name: 'vadate', value:  date, type: 'date'},
      {name: 'vamacid', value: 'F-JUFA', type: 'select'},
      {name: 'vapilid', value: 'Chef Abraracourcix', type: 'select'},
      {name: 'vahdeb', value: '12.0', type: 'text_field'},
      {name: 'vahfin', value: '13.0', type: 'text_field'},
      {name: 'vacdeb', value: '2.25', type: 'text_field'},
      {name: 'vacfin', value: '3.25', type: 'text_field'},
      {name: 'valieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'valieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsa', 'vols_avion/create', values, [], 1, "fct_vol_avion")
  
    values = [
      {name: 'vadate', value:  date, type: 'date'},
      {name: 'vamacid', value: 'F-JUFA', type: 'select'},
      {name: 'vapilid', value: 'Chef Abraracourcix', type: 'select'},
      {name: 'vahdeb', value: '13.0', type: 'text_field'},
      {name: 'vahfin', value: '13.3', type: 'text_field'},
      {name: 'vacdeb', value: '3.25', type: 'text_field'},
      {name: 'vacfin', value: '4.0', type: 'text_field'},
      {name: 'valieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'valieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsa', 'vols_avion/create', values, [], 1, "fct_vol_avion_2")
  
    # DC
    values = [
      {name: 'vadate', value:  date, type: 'date'},
      {name: 'vamacid', value: 'F-JUFA', type: 'select'},
      {name: 'vapilid', value: 'Legaulois Astérix', type: 'select'},
      {name: 'vadc', value: '1', type: 'checkbox'},
      {name: 'vainst', value: 'Chef Abraracourcix', type: 'select'},
      {name: 'vahdeb', value: '13.3', type: 'text_field'},
      {name: 'vahfin', value: '13.5', type: 'text_field'},
      {name: 'vacdeb', value: '4.00', type: 'text_field'},
      {name: 'vacfin', value: '4.30', type: 'text_field'},
      {name: 'valieudeco', value: 'LFOI Abbeville', type: 'select'},
      {name: 'valieuatt', value: 'LFOI Abbeville', type: 'select'}]
    self.fill_form('volsa', 'vols_avion/create', values, [], 1, "fct_vol_avion_dc")
  
    # Erreur
    values = []
    errors = ['Pilote est requis', 'Début est requis', 'Durée invalide']
    self.fill_form('volsa', 'vols_avion/create', values, [], 0, "fct_vol_avion_erreur")
  
    # Pilote en vol
    # TODO: rejeter aussi pilote déjà en vol
#    values = [
#      {name: 'vadate', value:  date, type: 'text_field'},
#      {name: 'vamacid', value: 'F-JUFA', type: 'select'},
#      {name: 'vapilid', value: 'Legaulois Astérix', type: 'select'},
#      {name: 'vadc', value: '1', type: 'checkbox'},
#      {name: 'vainst', value: 'Chef Abraracourcix', type: 'select'},
#      {name: 'vacdeb', value: '4.30', type: 'text_field'},
#      {name: 'vacfin', value: '5.40', type: 'text_field'},
#      {name: 'valieudeco', value: 'LFOI Abbeville', type: 'select'},
#      {name: 'valieuatt', value: 'LFOI Abbeville', type: 'select'}]
#    self.fill_form('volsa', 'vols_avion/create', values, ['pilote est déjà en vol'], 0)
  
  end

end
