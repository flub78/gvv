# encoding: utf-8
# GVV Watir test
# Create minimum data to prepare others tests
#
# Normally it is not recommended for tests to be dependent on each others.
# However several GVV features like billing and accounting can only
# run when some data already exist. Before to bill anything, you must have
# some pilotes, some gliders, some prices already define.
#
# This module make sure that this minimal environment exists.
#
require './gvv_test.rb'
require File.dirname(__FILE__) + '/reset_database.rb'

class TestPassword < GVVTest
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
  # Check access to unknow members
  # --------------------------------------------------------------------------------
  def test_change_admin_password
    description('basic password changes')
    
    @b.goto @root_url + 'auth/change_password'
    str = "mot de passe"
    check(@b.text.include?(str), " #{str} detecté")
    
    pw = 'password'
    new_pw = 'new_password'
    
    @b.text_field(:id => 'old_password').set pw    
    @b.text_field(:id => 'new_password').set new_pw
    @b.text_field(:id => 'confirm_new_password').set new_pw
    
    @b.button(:name => 'change').click    
    check(@b.text.include?('mot de passe a été changé'), "Mot de passe changé")
    
    self.logout()
    self.login('testadmin', 'password', false)
    check(@b.text.include?('mot de passe est incorrect'), "Connection refusée quand le mot de passe est faux")

    # Logo on with the new password    
    self.login('testadmin', new_pw)

    # Back to the previous password
    @b.goto @root_url + 'auth/change_password'
    @b.text_field(:id => 'old_password').set new_pw    
    @b.text_field(:id => 'new_password').set pw
    @b.text_field(:id => 'confirm_new_password').set pw
    
    @b.button(:name => 'change').click    
    check(@b.text.include?('mot de passe a été changé'), "Mot de passe restauré")

  end

end
