# encoding: utf-8
# GVV unit test
# Test de la home page
#
gem "minitest"
require 'minitest/autorun'
require "minitest/ci"
require 'watir'
require './os.rb'
require 'headless' if !OS.windows?
require 'mysql2'
require './php_config.rb'

Minitest::Ci.clean = false

$host = ENV['DB_HOST']
$database = ENV['DB_DATABASE']
$user = ENV['DB_USERNAME']
$password = ENV['DB_PASSWORD']

class GVVTest < MiniTest::Test
  # --------------------------------------------------------------------------------
  # Run before every test
  # --------------------------------------------------------------------------------
  def setup
    @base_url = 'http://localhost/jenkins_bb/'
    if ENV['BASE_URL']
      @base_url = ENV['BASE_URL']
    end
    @root_url = @base_url + 'index.php/'

    if !OS.windows? && !ENV['DISPLAY_TESTS']
      @headless = Headless.new
      @headless.start
    end

    # @b = Watir::Browser.new :chrome ,headless: true
	@b = Watir::Browser.new

    @b.window.resize_to(1200, 900)
  end

  # --------------------------------------------------------------------------------
  # GVV login
  # input assertion: not logged in yet
  # --------------------------------------------------------------------------------
  def login (user, password, expected_success=true)
    @b.goto @base_url
    screenshot('scr_before_login.png')
    @b.text_field(:id => 'username').set user
    @b.text_field(:id => 'password').set password
    @b.button(:name => 'login').click
    if (expected_success)
      check(@b.text.include?(user), "utilisateur #{user} connecté")
    end
    screenshot('scr_after_login.png')
  end

  # --------------------------------------------------------------------------------
  # Display a comment during test
  # --------------------------------------------------------------------------------
  def comment(str)
    puts "# " + str
  end


  # --------------------------------------------------------------------------------
  # Display a test description
  # @param verify what is tested
  # @param pwhen on which action
  # @param given in which context
  # --------------------------------------------------------------------------------
  def description(verify="", pwhen="", given="")
    comment(self.class.name + ':' + caller[0].split(' ')[1].slice(1..-2))

    if (!verify.empty?)
      comment('   Verify ' + verify)
    end
    if (!pwhen.empty?)
      comment('   When ' + pwhen)
    end
    if (!given.empty?)
      comment('   Given ' + given)
    end
  end

  # --------------------------------------------------------------------------------
  # GVV logout
  # input assertion: logged in
  # --------------------------------------------------------------------------------
  def logout ()
    @b.button(:value => 'Sortie').click
    screenshot('scr_logged_out.png')
  end

  # --------------------------------------------------------------------------------
  # Executed after each test
  # --------------------------------------------------------------------------------
  def teardown
    @b.close
    @headless.destroy if (!OS.windows? && !ENV['DISPLAY_TESTS'])
  end

  # --------------------------------------------------------------------------------
  # Connect to the database
  # --------------------------------------------------------------------------------
  def db_connect
    @db = Mysql2::Client.new(:host => $host, :database => $database,
        :username => $user, :password => $password)
    check(@db, "connexion base OK")
  end

  # --------------------------------------------------------------------------------
  # Disonnect from the database
  # --------------------------------------------------------------------------------
  def db_disconnect
	if (@db)
      @db.close()
	end
  end

  # --------------------------------------------------------------------------------
  # Assert with traces
  # --------------------------------------------------------------------------------
  def check(assertion, description = "")
    puts "#\t assert: " + self.class.name + " #{description}"
    if (!assertion)
      self.screenshot('failed-' + DateTime.now.to_s + '-' + description + '.png')
    end
    assert(assertion, description)
  end

  # --------------------------------------------------------------------------------
  # save a screenshot
  # --------------------------------------------------------------------------------
  def screenshot(filename)
    @b.screenshot.save 'screenshots/' + filename
  end

  # --------------------------------------------------------------------------------
  # Check that the user can access to a relative url
  # url: to check
  # must_find: list of pattern that should be find in the page
  # must_not_find: list of pattern that must not be found in the page
  # --------------------------------------------------------------------------------
  def can_access(url, view_name, must_find, must_not_find)
    # Absolute url
    if url =~ /^http/
      target_url = url
    else
      target_url = @root_url + url
    end

    puts "#\t can_access #{url}"
    @b.goto target_url
    # url_name = url
    @b.screenshot.save 'screenshots/scr_' + view_name + '.png'

    must_find.each do |str|
      check(@b.text.include?(str), '"' + str + '" found in ' + view_name)
    end

    must_not_find.each do |str|
      check(!@b.text.include?(str), '"' + str + '" not found in ' + view_name)
    end

  end
  
  # --------------------------------------------------------------------------------
  # open an url
  # --------------------------------------------------------------------------------
  def goto(url, screenshot_name = "")
    @b.goto(url)
    puts "\t# url: #{url}"
    if (screenshot_name == "")
      normalized_url = url.gsub("/", "_")
      normalized_url = normalized_url.gsub(":", "")
      screenshot_name = "url_" + normalized_url + ".png"
    end
    sleep(1)
    screenshot(screenshot_name)
  end

  # --------------------------------------------------------------------------------
  # Return the number of rows in a table
  # --------------------------------------------------------------------------------
  def table_count (dbh, table, where = "")
    sql = "select * from #{table}"
    if (where != "")
      sql += " WHERE #{where}"
    end
    sql += ";"

    # puts "sql = #{sql}"
    results = dbh.query(sql)
    return results.count()
  end

  # --------------------------------------------------------------------------------
  # Retourne le solde d'un compte
  # --------------------------------------------------------------------------------
  def solde (compte, where = "")
    sql = "select SUM(montant) as debit from ecritures where compte1 = #{compte}"
    if (where != "")
      sql += " and  #{where}"
    end
    sql += ";"

    results = @db.query(sql)
    if (results.count() == 0)
      debit = 0
    else
      debit = results.first()["debit"]
      debit = 0 if debit.nil?
    end
      
    sql = "select SUM(montant) as credit from ecritures where compte2 = #{compte}"
    if (where != "")
      sql += " and  #{where}"
    end
    sql += ";"
    
    results = @db.query(sql)
    if (results.count() == 0)
      credit = 0
    else
      credit = results.first()["credit"]
      credit = 0 if credit.nil?
    end

    return credit - debit
  end

  # --------------------------------------------------------------------------------
  # Retourne l'ID d'un compte
  # --------------------------------------------------------------------------------
  def compte (where = "")
    sql = "select * from comptes "
    if (where != "")
      sql += " where  #{where}"
    end
    sql += ";"
  
    results = @db.query(sql)
    if (results.count() == 0)
      puts "sql = #{sql}"
      puts "Pas de compte #{where}" 
      return nil
    else
      compte = results.first()["id"]
      # puts "compte #{where} = #{compte}" 
      return compte
    end
      
  end
  
  # --------------------------------------------------------------------------------
  # Create an element into a table
  # --------------------------------------------------------------------------------
  def fill_form(table, url, values, must_find, created, screenshot_name = "")
    initial_count = self.table_count(@db, table)

    @b.goto  @root_url + url
    @b.wait_until {@b.text.include? "Peignot"}
    
    values.each do |field|
      type = field[:type]
      name = field[:name]
      value = field[:value]
      # puts "field #{name} value=#{value} type=#{type}"
      
      case type
      when 'date'
        @b.text_field(:name => name).clear
        @b.text_field(:name => name).send_keys value
        @b.text_field(:name => name).fire_event "onchange"

      when 'text_field'    
        @b.text_field(:name => name).clear
        @b.text_field(:name => name).set value
        
        @b.text_field(:name => name).fire_event "onchange"
      when 'textarea'
        @b.textarea(:name => name).set value
      when 'checkbox'
        @b.checkbox(:name => name).set
      when 'checkbox-clear'
        @b.checkbox(:name => name, :value => value).clear
      when 'radio'
        id = field[:id]
        @b.radio(:id => id, :name => name).set
      when 'select'
        s = @b.select_list(:name => name)
        s.select(value)
      else
      end
    end

    if (screenshot_name != "") 
      self.screenshot(screenshot_name + ".png")
    end
    
    @b.button(:id => 'validate').click

    if (screenshot_name != "") 
      self.screenshot(screenshot_name + "_validated.png")
    end
    
    must_find.each do |str|
      check(@b.text.include?(str), '"' + str + "\" found after #{table} form filled")
    end

    count = self.table_count(@db, table)
    check(count - initial_count == created, "#{created} element created in #{table}")
  end

  # --------------------------------------------------------------------------------
  # Delete an element from a table
  # --------------------------------------------------------------------------------
  def delete(table, url, deleted = 1)
    initial_count = self.table_count(@db, table)
    @b.goto  @root_url + url
    count = self.table_count(@db, table)
    check(initial_count - count == deleted, "#{deleted} element deleted in #{table} #{url}")
  end

  # --------------------------------------------------------------------------------
  # Test basic database accesses
  # --------------------------------------------------------------------------------
  def select_one(table, where = "")
    sql = "select * from #{table}"
    if (where != "")
      sql += " WHERE #{where}"
    end
    sql += ";"
    # read all
    begin
      results = @db.query(sql)
      count = results.count()

      if (count > 0)
	      return results.first()
      else
        return nil
      end

    end
  end

  # --------------------------------------------------------------------------------
  # Test basic database accesses
  # --------------------------------------------------------------------------------
  def select_last(table, where = "")
    sql = "select * from #{table}"
    if (where != "")
      sql += " WHERE #{where}"
    end
    sql += ";"
    # read all
    begin
      results = @db.query(sql) 
      count = results.count()

      if (count > 0)
	res = nil
	results.each do |row|
          res = row
	end
	return res
      else
        return nil
      end

    end
  end

  # --------------------------------------------------------------------------------
  # Return the last ID created in a table
  # --------------------------------------------------------------------------------
  def last_id(table, index=0)
    row = self.select_last(table)
    res = row[index]
    return res
  end
  
  # --------------------------------------------------------------------------------
  # Basic CRUD tests
  # --------------------------------------------------------------------------------
  def crud(params)

    create = params['controler'] + '/create'

    # Check that incorrect values are rejected
    self.fill_form(params['table'], create, params['incorrect_values'], params['error_patterns'], 0)

    # Create
    self.fill_form(params['table'], create, params['values'], params['success_patterns'], 1)

    last_elt = self.select_last(params['table'])
    id = last_elt[params['key_index']]
    edit = params['controler'] + '/edit/' + id.to_s
    delete = params['controler'] + '/delete/' + id.to_s

    # Read
    @b.goto  @root_url + edit
    check(@b.html.include?(params['create_pattern']), '"' + params['create_pattern'] + "\" found in form after creation" + params['table'])

    # Update
    self.fill_form(params['table'], edit, params['changes'], params['success_patterns'], 0)

    # Read
    @b.goto  @root_url + edit
    check(@b.html.include?(params['change_pattern']), '"' + params['change_pattern'] + "\" found in form after modification" + params['table'])

    # Delete
    self.delete(params['table'], delete, 1)

  end
  
  # --------------------------------------------------------------------------------
  # Set the language
  # --------------------------------------------------------------------------------
  def set_language(lang)
    
    # it is not a good idea to use a relative pathvi
    configfile = '../application/config/config.php'
    configfile = '/var/lib/jenkins/workspace/GVV_watir/application/config/config.php'
    conf = PhpConfig.new(configfile)
    assert(conf, "configuration loaded")
    
    # puts "keys = #{conf.keys}"
    
    conf.set('language', "'#{lang}'")    
    conf.save
    # Reload 
    conf = PhpConfig.new(configfile)
    assert(conf.value('language') == "'#{lang}'", 'language set to ' + "'#{lang}'")       
  end

end
