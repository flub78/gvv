# GVV Watir test
#
# Run the CIUnit tests and check that there is no error
require './gvv_test.rb'

class TestCIUnit < GVVTest
  
  # Run before every test
  def setup
    super
    self.login('testadmin', 'password')
    self.db_connect
  end

  # Executed after each test
  def teardown
    self.db_disconnect   
    # self.logout()
    super
  end
      
  # Check CI_Unit tests
  def test_all
    description('CiUnit unit tests do not report errors', 'the CIUnit test pages are displayed', 'logged on as admin')
    must_find = ["Test Name", "Passed"]
    must_not_find = ['Failed', '404 Page Not Found', 'A PHP Error']

    can_access('tests/test_helpers', 'ciunit_helpers', must_find, must_not_find)
    can_access('tests/test_libraries', 'ciunit_libraries', must_find, must_not_find)
    can_access('achats/test', 'ciunit_achats', must_find, must_not_find)
    can_access('admin/test', 'ciunit_admin', must_find, must_not_find)
    can_access('categorie/test', 'ciunit_categorie', must_find, must_not_find)
    can_access('compta/test', 'ciunit_compta', must_find, must_not_find)
    can_access('comptes/test', 'ciunit_comptes', must_find, must_not_find)
    can_access('event/test', 'ciunit_event', must_find, must_not_find)
    can_access('licences/test', 'ciunit_licences', must_find, must_not_find)
    can_access('membre/test', 'ciunit_membre', must_find, must_not_find)
    can_access('plan_comptable/test', 'ciunit_plan_comptable', must_find, must_not_find)
    # can_access('planeur/test', 'ciunit_planeur', must_find, must_not_find)
    can_access('pompes/test', 'ciunit_pompes', must_find, must_not_find)
    # can_access('presences/test', 'ciunit_presence', must_find, must_not_find)
    can_access('rapports/test', 'ciunit_rapports', must_find, must_not_find)
    can_access('tarifs/test', 'ciunit_tarifs', must_find, must_not_find)
    can_access('terrains/test', 'ciunit_terrains', must_find, must_not_find)
    can_access('tickets/test', 'ciunit_tickets', must_find, must_not_find)
    can_access('types_ticket/test', 'ciunit_types_ticket', must_find, must_not_find)
    can_access('rapports/test', 'ciunit_rapports', must_find, must_not_find)
    
    # return to a page with a logout button
    @b.goto  @root_url + 'membre/create'
  end

end
