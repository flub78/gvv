# GVV Watir test
# Migration
#
require 'mysql2'
require './gvv_test.rb'

class TestMigration < GVVTest
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
  # Update database
  # --------------------------------------------------------------------------------
  def test_update_database
    description('the database migration mechanism', '', 'a small initial increment')
    @b.goto  @root_url + 'migration'
    check(@b.text.include?("Migration de la"), "Migration is displayed")
  
    screenshot('scr_before_migration.png')
    @b.button(:id => 'validate').click
    screenshot('scr_after_migration.png')
  end

end
