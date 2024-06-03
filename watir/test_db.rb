#
# Small test to check that everything is setup for Ruby tests
# to access DB
#
require 'mysql2'
require "minitest/autorun"

$host = ENV['DB_HOST']
$database = ENV['DB_DATABASE']
$user = ENV['DB_USERNAME']
$password = ENV['DB_PASSWORD']

class TestMe < Minitest::Test
  def setup
    @client = Mysql2::Client.new(:host => $host, :database => $database,
        :username => $user, :password => $password)
  end

  def test_db_connected
    results = @client.query("SHOW TABLES")
    count = results.count()
    # puts "count: #{count} tables in #{$database} database"

    results.each do |row|
      # puts row
    end
    assert (count > 0), "several tables in database"
  end

  def test_count
    results = @client.query("select sum(id) as total from users")
    count = results.count()
    puts "users: #{count} "
    puts results.first()
  end

  def teardown
    @client.close()
  end
end

