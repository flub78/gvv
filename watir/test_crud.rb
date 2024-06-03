# GVV Watir test
# Test CRUD: Create, Read, Update, Delete
#
# TODO create elements in all the table
#
#'pompes',
#'achats',
#'tarifs',
#'volsa',
#'volsp',
#'machinesa',
#'machinesp',
#'ecritures',
#'comptes',
#'licences',
#'membres',
#'planc',
#'categorie',
#'tickets',
#'events_types',
#'events',
#'terrains',
#'type_ticket',
#'reports',
#'membership',
#'sections',
#'mails',
#'historique',
#'migrations'

require './gvv_test.rb'

class TestCrud < GVVTest
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
  # CRUD on Asterix
  # --------------------------------------------------------------------------------
  def test_pilote_crud

    description('CRUD operation on membre table')
    
    initial_count = Hash.new
    table_list = ['membres', 'comptes', 'users']
    table_list.each do |table|
      initial_count[table] = self.table_count(@db, table)
    end

    count = self.table_count(@db, 'membres', "mlogin='asterix' ")
    if count == 0
      pilote_id = 'asterix'
    else
      pilote_id = 'asterix' + count.to_s
    end
    assert(self.table_count(@db, 'membres', "mlogin='#{pilote_id}'") == 0, "Le pilote n'existe pas")

    # Check that input with incorrect values are rejected
    incorrect_values = [
      {name: 'mlogin', value: '', type: 'text_field'},
      {name: 'mprenom', value: '', type: 'text_field'},
      {name: 'mnom', value:  '', type: 'text_field'},
      {name: 'memail', value:  'asterixfree.fr', type: 'text_field'},
      {name: 'madresse', value:  '', type: 'text_field'},
      {name: 'cp', value:  '98765', type: 'text_field'},
      {name: 'ville', value: 'Village Gaulois', type: 'text_field'},
      {name: 'pays', value: 'Gaule', type: 'text_field'},
      {name: 'profession', value: 'Guerrier', type: 'text_field'},
      {name: 'mdaten', value: '01/01/1959', type: 'text_field'}]
    error_patterns = ["Identifiant est requis", "Nom est requis", "adresse email valide", "Adresse est requis"]
    self.fill_form('membres', 'membre/create', incorrect_values, error_patterns, 0)
    
    # Create asterix
    values = [
      {name: 'mlogin', value: pilote_id, type: 'text_field'},
      {name: 'mprenom', value: 'Asterix', type: 'text_field'},
      {name: 'mnom', value:  'Legaulois', type: 'text_field'},
      {name: 'memail', value:  'asterix@free.fr', type: 'text_field'},
      {name: 'madresse', value:  'Hutte ronde', type: 'text_field'},
      {name: 'cp', value:  '98765', type: 'text_field'},
      {name: 'ville', value: 'Village Gaulois', type: 'text_field'},
      {name: 'pays', value: 'Gaule', type: 'text_field'},
      {name: 'profession', value: 'Guerrier', type: 'text_field'},
      {name: 'mdaten', value: '01/01/1959', type: 'text_field'}]
    success_patterns = ["Asterix"]
    self.fill_form('membres', 'membre/create', values, success_patterns, 1)

    # vérifie qu'il y a un membre, un utilisateur et un compte 411
    assert(self.table_count(@db, 'membres', "mlogin='#{pilote_id}'") >= 1, "Asterix existe")
    assert(self.table_count(@db, 'comptes', "pilote='#{pilote_id}'") >= 1, "Asterix a un compte")
    assert(self.table_count(@db, 'users', "username='#{pilote_id}'") >= 1, "Asterix a un identifiant de connexion")

    # modifie asterix
    values = [
      {name: 'memail', value:  'asterix@orange.fr', type: 'text_field'},
      {name: 'mniveau[]', value: '2', type: 'checkbox'},
      {name: 'mniveau[]', value: '2', type: 'checkbox-clear'},
      {name: 'mniveau[]', value: '2', type: 'checkbox'}]
    self.fill_form('membres', 'membre/edit/' + pilote_id, values, ['asterix@orange.fr'], 0)

    # Delete it    
    self.delete('membres', 'membre/delete/' + pilote_id, 1)
      
  end

  # --------------------------------------------------------------------------------
  # Check access to unknow members
  # --------------------------------------------------------------------------------
  def test_unknown_pilot
    description('access to an unknow membre element')
    
    @b.goto  @root_url + 'membre/edit/zorglub'
    check(!@b.text.include?("PHP Error"), "Pas d'erreur edition pilote inconnu")
    @b.goto  @root_url + 'membre/delete/zorglub'
    check(!@b.text.include?("PHP Error"), "Pas d'erreur destruction pilote inconnu")
  end

  # --------------------------------------------------------------------------------
  # CRUD plan comptable
  # --------------------------------------------------------------------------------
  def test_crud_plan_comptable
 
    description('CRUD operation on "planc" table')
    desc = 'Transfers de charges'
    desc2 = 'Transfers de produits'
   
    params = {
      'controler' => 'plan_comptable',
      'table' => 'planc',
      'incorrect_values' => [
         {name: 'pcode', value: '', type: 'text_field'},
         {name: 'pdesc', value: '', type: 'text_field'}],
      'error_patterns' => ["est requis"],
      'values' => [
         {name: 'pcode', value: '799', type: 'text_field'},
         {name: 'pdesc', value: desc, type: 'text_field'}],
      'success_patterns' => [],
      'changes' => [
        {name: 'pdesc', value: desc2, type: 'text_field'}],
       'create_pattern' => desc,
       'change_pattern' => desc2,
       'key_index' => "pcode"}
        
      self.crud(params)          
  end

  # --------------------------------------------------------------------------------
  # CRUD terrains
  # --------------------------------------------------------------------------------
  def test_crud_terrains

    description('CRUD operation on "terrains" table')
    desc = 'Terrain de test'
    desc2 = 'Marly'
 
    params = {
      'controler' => 'terrains',
      'table' => 'terrains',
      'incorrect_values' => [
        ],
      'error_patterns' => ["OACI est requis"],
      'values' => [
        {name: 'oaci', value: 'LFXX', type: 'text_field'},
        {name: 'nom', value: 'Mon jardin', type: 'text_field'},
        {name: 'freq1', value: '123.5', type: 'text_field'},
        {name: 'freq2', value: '122.65', type: 'text_field'},
        {name: 'comment', value: desc, type: 'textarea'}],
      'success_patterns' => [],
      'changes' => [
        {name: 'nom', value: desc2, type: 'text_field'}],
      'create_pattern' => desc,
      'change_pattern' => desc2,
      'key_index' => "oaci"}
      
    self.crud(params)          
  end

  # --------------------------------------------------------------------------------
  # CRUD comptes
  # --------------------------------------------------------------------------------
  def test_crud_comptes
    description('CRUD operation on "comptes" table')
    desc = 'Ventes de trucs'
    desc2 = 'Don de trucs'
 
    params = {
      'controler' => 'comptes',
      'table' => 'comptes',
      'incorrect_values' => [
        ],
      'error_patterns' => ["Nom du compte est requis"],
      'values' => [
        {name: 'nom', value: 'Vente diverses', type: 'text_field'},
        {name: 'codec', value: '70 Ventes', type: 'select'},
        {name: 'desc', value: desc, type: 'text_field'}],
      'success_patterns' => [],
      'changes' => [
        {name: 'desc', value: desc2, type: 'text_field'}],
      'create_pattern' => desc,
      'change_pattern' => desc2,
      'key_index' => "id"}
      
    self.crud(params)          
 end

end
