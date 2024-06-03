# encoding: utf-8
# GVV Watir test
#
# Test le support des langues Anglais et Néerlandais.
#
# Pour le moment, le test prend juste une copie d'écran de la page d'acceuil
# et sort correctement si le boutton de sortie est traduit.
#
# Questions: 
#   Est-ce qu'il faudrait tester tout le fonctionnale dans chaque langue ?
#   c-a-d multiplier par 3 les temps d'exécution.
#   Cela démontrerai que le programme n'est pas dépendant des chaines en dur. 
#
#   Est-ce qu'il faudrait tester toutes les chaines affichées?
#   Non le contrôle visuel est suffisant.
#
#   Réponse: les deux propositions sont assez chères. Une fois qu'on a démontré qu'on savait
#            changer de langue et que le programme fonctionne dans au moins une langue et qu'on a fait
#            du contrôle visuel de chaque langue, on a déjà pas mal testé.
#
#
require './php_config.rb'
require './gvv_test.rb'
require File.dirname(__FILE__) + '/reset_database.rb'

class TestInternational < GVVTest
  
  # --------------------------------------------------------------------------------
  # Run before every test
  # --------------------------------------------------------------------------------
  def setup
    super
    self.db_connect
  end

  # --------------------------------------------------------------------------------
  # Executed after each test
  # --------------------------------------------------------------------------------
  def teardown
    self.db_disconnect
    super
  end


  # --------------------------------------------------------------------------------
  # Test of GVV in english
  # --------------------------------------------------------------------------------
  def test_english
    
    self.set_language('english')
    self.login('testadmin', 'password')
    
    @b.goto @root_url
    
    screenshot('lang_english.png')
    # puts @b.text
    
    # Puisque qu'on a changé de language, le boutton à changé    
    @b.button(:value => 'Exit').click

    self.set_language('french')
 
  end

  # --------------------------------------------------------------------------------
  # Test of GVV in dutch
  # --------------------------------------------------------------------------------
  def test_dutch
    
    self.set_language('dutch')
    self.login('testadmin', 'password')
    
    @b.goto @root_url
    screenshot('lang_dutch.png')
        
    # Puisque qu'on a changé de language, le boutton à changé    
    @b.button(:value => 'Uitloggen').click
    self.set_language('french')
     
  end
          
end
