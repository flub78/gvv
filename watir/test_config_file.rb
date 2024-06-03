# encoding: utf-8
# GVV Watir test
#
# Ce test est une expérimentation sur la lecture et modification des fichiers de config
#
gem "minitest"
require 'minitest/autorun'
require './php_config.rb'

class TestConfigFile < MiniTest::Test
  # --------------------------------------------------------------------------------
  # Run before every test
  # --------------------------------------------------------------------------------
  def setup
    super
  end

  # --------------------------------------------------------------------------------
  # Executed after each test
  # --------------------------------------------------------------------------------
  def teardown
    super
  end


  # --------------------------------------------------------------------------------
  # Test test
  # --------------------------------------------------------------------------------
  def test_test
    
#    ENV.each do |pair|
#      puts "\$#{pair[0]} = #{pair[1]}"
#    end
    description('access and modification of the club configuration')
    
    path = File.expand_path(File.dirname(__FILE__))
    # puts "script path = #{path}"±
    
    conf = PhpConfig.new('../application/config/program.php')
    assert(conf, "configuration loaded")
    
    keys = conf.keys
    assert(keys.size == 10, "Correct number of keys") 

    assert(conf.value('new_layout') == 'true', 'Value of new_layout')
    assert(conf.value('auto_planchiste') == 'false', 'Value of auto_planchiste')
    assert(conf.value('copie_a') == '"president@free.fr; gestion@monclub.fr"', 'Value of copie_a')
    
    assert(conf.value('unknow_key') == nil, 'Value of an unknow key')
    
    conf.set('new_layout', 'false')
    assert(conf.value('new_layout') == 'false', 'Value of new_layout after change')
    conf.set('copie_a', '"max@free.fr"')
    assert(conf.value('copie_a') == '"max@free.fr"', 'Value of copie_a after change')
    
    # Save to a temporary file
    newfile = '/tmp/conf.php'
    conf.save(newfile)
    
    # Reload the file
    conf = PhpConfig.new(newfile)
    # Check that changes are persistent
    assert(conf.value('new_layout') == 'false', 'Value of new_layout after reload')
    assert(conf.value('copie_a') == '"max@free.fr"', 'Value of copie_a after reload')

    # Change back
    conf.set('new_layout', 'true')
    conf.set('copie_a', '"president@free.fr; gestion@monclub.fr"')
    # save in place
    conf.save
    # Reload 
    conf = PhpConfig.new(newfile)
        
    assert(conf.value('new_layout') == 'true', 'Value of new_layout #2')
    assert(conf.value('auto_planchiste') == 'false', 'Value of auto_planchiste #2')
    assert(conf.value('copie_a') == '"president@free.fr; gestion@monclub.fr"', 'Value of copie_a #2')
     
  end
          
end
