# ReInitialize the database to a known state
require File.dirname(__FILE__) + '/dbBackup.rb'

$host = ENV['DB_HOST']
$database = ENV['DB_DATABASE']
$user = ENV['DB_USERNAME']
$password = ENV['DB_PASSWORD']

module ResetDatabase
    
  bckp = DbBackup.new(:user => $user, :password => $password, :database => $database)
  
  filename = File.dirname(__FILE__) + '/../install/test_database_17.sql'
  bckp.drop
  bckp.create($database)
  
  bckp.restore(filename)
  puts "# database #{filename} reloaded"
 
end

