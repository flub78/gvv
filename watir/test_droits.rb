# encoding: utf-8
# GVV Watir test
#
# Vérifie les droits des différents classes d'utilisateur
#
require './gvv_test.rb'
require File.dirname(__FILE__) + '/reset_database.rb'

class TestDroits < GVVTest
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
  # Droits d'accès pour les membres
  # --------------------------------------------------------------------------------
  def check_rights(user, password, level, authorized_urls, forbiden_urls)
    
    self.login(user, password)
    
    must_find = ["Boissel"]
    must_not_find = ['404 Page Not Found', 'A PHP Error']
    not_authorized = ['Accès non autorisé']
   
    authorized_urls.each do |url|
      name = level + '_' + url.gsub('/', '_')
      can_access(url, name , must_find, must_not_find)      
    end
    
    forbiden_urls.each do |url|
      name = level + '_rejected_' + url.gsub('/', '_')
      can_access(url, name , not_authorized, must_not_find)      
    end
   
    self.logout()
  end

  # --------------------------------------------------------------------------------
  # Droits d'accès pour les membres
  # --------------------------------------------------------------------------------
  def test_membre
    
    description('correct access rights for members')

    authorized_urls = [
    'membre', 'membre/page', 'membre/edit', 'auth/change_password',
    'planeur',
    'avion',
    'vols_avion',
    'vols_planeur',
    'compta/mon_compte',
    'tickets/page',
    'event/stats',
    'event/page',
    'event/formation',
    'event/fai',
    # 'presences',
    'licences',
    'welcome']
       
    forbiden_urls = [
      'planeur/create', 'avion/create', 'planeur/delete', 'avion/delete', 'planeur/edit', 'avion/edit'
    ]
    
    check_rights('asterix', 'password', 'membre', authorized_urls, forbiden_urls)
  end

  # --------------------------------------------------------------------------------
  # Droits d'accès pour les planchistes
  # --------------------------------------------------------------------------------
  def test_planchiste
    description('correct access rights for board writer')
    self.login('goudurix', 'password')
    self.logout()
  end

  # --------------------------------------------------------------------------------
  # Droits d'accès pour les membres du conseil
  # --------------------------------------------------------------------------------
  def test_ca

    description('correct access rights for club administrators')

    authorized_urls = [
      'planeur/create', 'avion/create', 'planeur/edit/F-CERP', 'avion/edit/F-JUFA'
    ]
       
    forbiden_urls = [
      'config'
    ]
    
    check_rights('obelix', 'password', 'ca', authorized_urls, forbiden_urls)
    
  end

  # --------------------------------------------------------------------------------
  # Droits d'accès pour les membres du bureau
  # --------------------------------------------------------------------------------
  def test_bureau
    description('correct access rights for board members')

    self.login('bonemine', 'password')
    self.logout()
  end

  # --------------------------------------------------------------------------------
  # Droits d'accès pour les trésoriers
  # --------------------------------------------------------------------------------
  def test_tresorier
    description('correct access rights for accounters')
    self.login('panoramix', 'password')
    self.logout()
  end
          
end
