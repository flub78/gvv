# GVV Watir test
# Test l'installation
#

require './gvv_test.rb'

class TestInstall < GVVTest

  def test_reset_and_install
    
    description('reset procedure and installation from scratch', '', 'any initial context') 
    
    must_find = ["GVV", "nitialisation de GVV", "drop table"]
    must_not_find = []
    can_access(@base_url + 'install/reset.php', 'reset', must_find, must_not_find)

    must_find = []
    must_not_find = []
    can_access(@base_url + 'install', 'installation', must_find, must_not_find)

    self.login('testadmin', 'password')

    self.logout()
    
  end
end
