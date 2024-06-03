# GVV Watir test
# Test de restauration de base
#
require './gvv_test.rb'
require 'pathname'

class TestRestore < GVVTest
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
    # self.logout()
    super
  end


  # --------------------------------------------------------------------------------
  # Database restore
  # --------------------------------------------------------------------------------
  def test_db_restore
    description('database restore')

    @b.goto  @root_url + 'admin/restore'
    filename = Dir.pwd + '/../install/base_de_test_14.zip'
    filename = Pathname.new(filename).cleanpath.to_s
    puts "#\trestoring " + filename
    @b.file_field(:name => 'userfile').set(filename)
    @b.button(:name => 'button').click
    
    # Check 'depuis la sauvegarde'
    check(@b.text.include?('depuis la sauvegarde'), "Database has been restored")
    
  end

  # --------------------------------------------------------------------------------
  # Database backup
  # --------------------------------------------------------------------------------
  def test_db_backup
    #@b.goto  @root_url + 'admin/backup'
    
  end

end
