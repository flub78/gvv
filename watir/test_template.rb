# encoding: utf-8
# GVV Watir test
#
# Vérifie ....
#
require 'mysql2'
require './gvv_test.rb'

class TestTemplate < GVVTest
  # --------------------------------------------------------------------------------
  # Run before every test
  # --------------------------------------------------------------------------------
  def setup
    super
    self.db_connect
    self.login('testadmin', 'password')
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
  # Test test
  # --------------------------------------------------------------------------------
  def test_test
    @b.goto @root_url
    screenshot('scr_test.png')
    check(true, "Vérification")
  end
          
end
